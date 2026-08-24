<?php

namespace App\Imports\Support;

use Illuminate\Support\Str;

trait ImportSummary
{
    protected int $imported = 0;

    protected int $updated = 0;

    protected int $skipped = 0;

    /** @var array<int, array{row: int|null, message: string}> */
    protected array $importErrors = [];

    /** @var array<int, array{row: int|null, message: string}> */
    protected array $warnings = [];

    protected bool $validationFailuresAppended = false;

    protected function recordError(?int $row, string $message): void
    {
        $this->importErrors[] = [
            'row' => $row,
            'message' => $message,
        ];
    }

    public function recordWarning(?int $row, string $message): void
    {
        $this->warnings[] = [
            'row' => $row,
            'message' => $message,
        ];
    }

    /**
     * Merge Maatwebsite SkipsFailures + SkipsErrors results into the summary.
     * Safe to call more than once (idempotent).
     */
    protected function appendValidationFailures(): void
    {
        if ($this->validationFailuresAppended) {
            return;
        }
        $this->validationFailuresAppended = true;

        $failedRows = [];
        $errorsWithoutRow = 0;

        if (method_exists($this, 'failures')) {
            foreach ($this->failures() as $failure) {
                $attribute = $failure->attribute();
                $errorList = implode(', ', $failure->errors());
                $message = ($attribute !== null && $attribute !== '')
                    ? "Validation Error [{$attribute}]: {$errorList}"
                    : "Validation Error: {$errorList}";

                $this->importErrors[] = [
                    'row' => $failure->row(),
                    'message' => $message,
                ];
                $failedRows[$failure->row()] = true;
            }
        }

        if (method_exists($this, 'errors')) {
            foreach ($this->errors() as $error) {
                $row = $this->resolveErrorRow($error);
                $message = $error->getMessage() !== ''
                    ? $error->getMessage()
                    : class_basename($error);

                $this->importErrors[] = [
                    'row' => $row,
                    'message' => $message,
                ];

                if ($row !== null) {
                    $failedRows[$row] = true;
                } else {
                    $errorsWithoutRow++;
                }
            }
        }

        $this->skipped += count($failedRows) + $errorsWithoutRow;
    }

    /**
     * Best-effort row extraction from a SkipsOnError Throwable.
     */
    protected function resolveErrorRow(\Throwable $error): ?int
    {
        if (method_exists($error, 'getRow')) {
            $row = $error->getRow();

            return is_numeric($row) ? (int) $row : null;
        }

        if (method_exists($error, 'row')) {
            $row = $error->row();

            return is_numeric($row) ? (int) $row : null;
        }

        return null;
    }

    public function summary(): array
    {
        $this->appendValidationFailures();

        return [
            'imported' => $this->imported,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->importErrors,
            'warnings' => $this->warnings,
        ];
    }

    protected function relPath(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $base = rtrim(asset(''), '/');
        $rel = ltrim(str_replace($base, '', $url), '/');

        // Keep external URLs (YouTube/Vimeo, etc.) as-is.
        return Str::startsWith($rel, 'http') ? $url : $rel;
    }
}
