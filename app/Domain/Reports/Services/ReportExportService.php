<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTOs\ReportFilters;
use App\Domain\Reports\Exports\ReportExcelExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportExportService
{
    public function __construct(
        private readonly WhatsAppReportService  $whatsApp,
        private readonly CustomersReportService $customers,
        private readonly ProjectsReportService  $projects,
        private readonly PropertiesReportService $properties,
        private readonly PlatformReportService  $platform,
    ) {}

    public function download(
        string $group,
        string $format,
        int $userId,
        ReportFilters $filters
    ): BinaryFileResponse|StreamedResponse|\Illuminate\Http\Response {
        $data     = $this->collectData($group, $userId, $filters);
        $filename = "report-{$group}-{$filters->date->startDate->toDateString()}";

        if ($format === 'pdf') {
            return $this->downloadPdf($group, $data, $filename);
        }

        return $this->downloadExcel($data, $filename);
    }

    private function collectData(string $group, int $userId, ReportFilters $filters): array
    {
        $date = $filters->date;

        return match ($group) {
            'whatsapp'   => [
                'summary'       => $this->whatsApp->summary($userId, $filters),
                'campaigns'     => $this->whatsApp->campaignDelivery($userId, $filters),
                'automations'   => $this->whatsApp->automationTriggers($userId, $filters),
                'agents'        => $this->whatsApp->agentPerformance($userId, $filters, 1, 200),
                'numbers'       => $this->whatsApp->numberPerformance($userId, $filters, 1, 200),
            ],
            'customers'  => [
                'summary'       => $this->customers->summary($userId, $date),
                'funnel'        => $this->customers->pipelineFunnel($userId, $date),
                'top_deals'     => $this->customers->topDeals($userId, $date),
                'agents'        => $this->customers->agentPerformance($userId, $date, 1, 200),
            ],
            'projects'   => [
                'summary'       => $this->projects->summary($userId, $date),
                'projects_list' => $this->projects->projectsList($userId, $date, 1, 200),
            ],
            'properties' => [
                'summary'         => $this->properties->summary($userId, $filters),
                'import_history'  => $this->properties->importHistory($userId, $filters),
                'top_listings'    => $this->properties->topListings($userId, $filters),
                'agents'          => $this->properties->agentPerformance($userId, $filters, 1, 200),
            ],
            'platform'   => [
                'summary'         => $this->platform->summary($userId, $date),
                'financial'       => $this->platform->financialSummary($userId),
                'employees'       => $this->platform->employees($userId, 1, 200, null, $filters->search),
            ],
            default => [],
        };
    }

    private function downloadExcel(array $data, string $filename): BinaryFileResponse
    {
        return Excel::download(new ReportExcelExport($data), "{$filename}.xlsx");
    }

    private function downloadPdf(string $group, array $data, string $filename): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        $view = 'reports.export.' . $group;

        // Fallback to generic view if specific one doesn't exist
        if (! view()->exists($view)) {
            $view = 'reports.export.generic';
        }

        $pdf = PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false])
            ->loadView($view, ['data' => $data, 'group' => $group]);

        return $pdf->download("{$filename}.pdf");
    }
}
