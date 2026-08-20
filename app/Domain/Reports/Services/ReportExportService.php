<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTOs\ReportDateFilter;
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
        ReportDateFilter $filter
    ): BinaryFileResponse|StreamedResponse|\Illuminate\Http\Response {
        $data     = $this->collectData($group, $userId, $filter);
        $filename = "report-{$group}-{$filter->startDate->toDateString()}";

        if ($format === 'pdf') {
            return $this->downloadPdf($group, $data, $filename);
        }

        return $this->downloadExcel($data, $filename);
    }

    private function collectData(string $group, int $userId, ReportDateFilter $filter): array
    {
        return match ($group) {
            'whatsapp'   => [
                'summary'       => $this->whatsApp->summary($userId, $filter),
                'campaigns'     => $this->whatsApp->campaignDelivery($userId, $filter),
                'automations'   => $this->whatsApp->automationTriggers($userId, $filter),
                'agents'        => $this->whatsApp->agentPerformance($userId, $filter, 1, 200),
                'numbers'       => $this->whatsApp->numberPerformance($userId, $filter, 1, 200),
            ],
            'customers'  => [
                'summary'       => $this->customers->summary($userId, $filter),
                'funnel'        => $this->customers->pipelineFunnel($userId, $filter),
                'top_deals'     => $this->customers->topDeals($userId, $filter),
                'agents'        => $this->customers->agentPerformance($userId, $filter, 1, 200),
            ],
            'projects'   => [
                'summary'       => $this->projects->summary($userId, $filter),
                'projects_list' => $this->projects->projectsList($userId, $filter, 1, 200),
            ],
            'properties' => [
                'summary'         => $this->properties->summary($userId, $filter),
                'import_history'  => $this->properties->importHistory($userId, $filter),
                'top_listings'    => $this->properties->topListings($userId, $filter),
                'agents'          => $this->properties->agentPerformance($userId, $filter, 1, 200),
            ],
            'platform'   => [
                'summary'         => $this->platform->summary($userId, $filter),
                'financial'       => $this->platform->financialSummary($userId),
                'employees'       => $this->platform->employees($userId, 1, 200),
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
