<?php

namespace App\Domain\DataExport\Services;

use App\Imports\AmenitiesImportSheet;
use App\Imports\CrmSettingsImportSheet;
use App\Imports\CustomersDataImportSheet;
use App\Imports\ProjectsDataImportSheet;
use App\Imports\PropertiesDataImportSheet;
use App\Imports\PropertyRequestsDataImportSheet;
use App\Imports\Support\IdRemapper;
use App\Imports\Support\MissingDefaultLanguageException;
use App\Imports\Support\SheetPicker;
use App\Imports\Support\WorkbookSheetInspector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class TenantDataImportService
{
    private const ROW_LIMIT = 5000;

    private const HEADER_MISMATCH = 'Sheet headers do not match the expected export format — sheet skipped';

    /**
     * Import a User Data Export workbook into the given tenant.
     *
     * Sheet importers are run in dependency order (projects → customers →
     * properties → requests), sharing one IdRemapper across passes.
     * Each sheet is isolated so setup/header/missing-sheet failures do not
     * abort the remaining sheets.
     *
     * @return array{
     *     projects: array{imported: int, updated: int, skipped: int, errors: array, warnings: array},
     *     customers: array{imported: int, updated: int, skipped: int, errors: array, warnings: array},
     *     properties: array{imported: int, updated: int, skipped: int, errors: array, warnings: array},
     *     requests: array{imported: int, updated: int, skipped: int, errors: array, warnings: array},
     *     note?: string
     * }
     */
    public function import(int $ownerId, UploadedFile $file, bool $updateExisting = false): array
    {
        return DB::transaction(function () use ($ownerId, $file, $updateExisting) {
            $path = $file->getRealPath() ?: $file->getPathname();
            $inspector = new WorkbookSheetInspector($path);
            $remap = new IdRemapper();

            try {
                $summaries = [];
                $totalDataRows = 0;

                foreach ($this->sheetPasses() as $pass) {
                    $index = $pass['index'];
                    $key = $pass['key'];

                    if ($inspector->hasSheet($index)) {
                        $totalDataRows += $inspector->dataRowCount($index);
                    }

                    $summaries[$key] = $this->importSheet(
                        $inspector,
                        $remap,
                        $ownerId,
                        $file,
                        $updateExisting,
                        $pass
                    );
                }

                $result = [
                    'crm_settings' => $summaries['crm_settings'],
                    'amenities' => $summaries['amenities'],
                    'projects' => $summaries['projects'],
                    'customers' => $summaries['customers'],
                    'properties' => $summaries['properties'],
                    'requests' => $summaries['requests'],
                ];

                if ($totalDataRows === 0) {
                    $result['note'] = 'No data found in file';
                }

                return $result;
            } finally {
                $inspector->disconnect();
            }
        });
    }

    /**
     * Dependency order + export sheet indexes (UserDataExport):
     * Properties=0, Projects=1, Customers=2, Requests=3.
     *
     * @return list<array{key: string, index: int, required_headers: list<string>, factory: callable}>
     */
    private function sheetPasses(): array
    {
        return [
            [
                'key' => 'crm_settings',
                'index' => 4,
                'optional' => true, // older export files won't have this sheet
                'required_headers' => ['kind', 'name'],
                'factory' => fn (int $ownerId, IdRemapper $remap, bool $updateExisting) => new CrmSettingsImportSheet(
                    $ownerId,
                    $remap,
                    $updateExisting,
                    self::ROW_LIMIT
                ),
            ],
            [
                'key' => 'amenities',
                'index' => 5,
                'optional' => true, // older export files won't have this sheet
                'required_headers' => ['name'],
                'factory' => fn (int $ownerId, IdRemapper $remap, bool $updateExisting) => new AmenitiesImportSheet(
                    $ownerId,
                    $remap,
                    $updateExisting,
                    self::ROW_LIMIT
                ),
            ],
            [
                'key' => 'projects',
                'index' => 1,
                'required_headers' => ['title'],
                'factory' => fn (int $ownerId, IdRemapper $remap, bool $updateExisting) => new ProjectsDataImportSheet(
                    $ownerId,
                    $remap,
                    $updateExisting,
                    self::ROW_LIMIT
                ),
            ],
            [
                'key' => 'customers',
                'index' => 2,
                'required_headers' => ['name', 'phone_number'],
                'factory' => fn (int $ownerId, IdRemapper $remap, bool $updateExisting) => new CustomersDataImportSheet(
                    $ownerId,
                    $remap,
                    $updateExisting,
                    self::ROW_LIMIT
                ),
            ],
            [
                'key' => 'properties',
                'index' => 0,
                'required_headers' => ['title', 'price'],
                'factory' => fn (int $ownerId, IdRemapper $remap, bool $updateExisting) => new PropertiesDataImportSheet(
                    $ownerId,
                    $remap,
                    $updateExisting,
                    self::ROW_LIMIT
                ),
            ],
            [
                'key' => 'requests',
                'index' => 3,
                'required_headers' => ['full_name', 'phone'],
                'factory' => fn (int $ownerId, IdRemapper $remap, bool $updateExisting) => new PropertyRequestsDataImportSheet(
                    $ownerId,
                    $remap,
                    $updateExisting,
                    self::ROW_LIMIT
                ),
            ],
        ];
    }

    /**
     * @param  array{key: string, index: int, required_headers: list<string>, factory: callable}  $pass
     */
    private function importSheet(
        WorkbookSheetInspector $inspector,
        IdRemapper $remap,
        int $ownerId,
        UploadedFile $file,
        bool $updateExisting,
        array $pass
    ): array {
        $index = $pass['index'];
        $sheetLabel = $index + 1; // 1-based for human-readable messages

        if (!$inspector->hasSheet($index)) {
            return $this->emptySummary(
                !empty($pass['optional']) ? null : "Sheet {$sheetLabel} not found in file"
            );
        }

        if (!$inspector->headersMatch($index, $pass['required_headers'])) {
            return $this->emptySummary(self::HEADER_MISMATCH);
        }

        $dataRows = $inspector->dataRowCount($index);

        // Header-only sheet: nothing to import (not a failure). Skip the read,
        // which would otherwise throw "Start row (2) is beyond highest row (1)".
        if ($dataRows === 0) {
            return $this->emptySummary();
        }

        try {
            /** @var object $importer */
            $importer = ($pass['factory'])($ownerId, $remap, $updateExisting);

            Excel::import(new SheetPicker($importer, $index), $file);

            if ($dataRows > self::ROW_LIMIT && method_exists($importer, 'recordWarning')) {
                $importer->recordWarning(
                    null,
                    'Only first ' . self::ROW_LIMIT . " rows imported (file had {$dataRows})"
                );
            }

            return $importer->summary();
        } catch (MissingDefaultLanguageException $e) {
            return $this->emptySummary($e->getMessage());
        } catch (Throwable $e) {
            return $this->emptySummary(
                'Sheet import failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * @return array{imported: int, updated: int, skipped: int, errors: array, warnings: array}
     */
    private function emptySummary(?string $error = null): array
    {
        $summary = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'warnings' => [],
        ];

        if ($error !== null) {
            $summary['errors'][] = [
                'row' => null,
                'message' => $error,
            ];
        }

        return $summary;
    }
}
