<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\WhatsappNumberMonitorService;
use Illuminate\Http\Request;

class WhatsappNumberMonitorController extends Controller
{
    public function __construct(private WhatsappNumberMonitorService $service) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'health', 'sync', 'q', 'sort', 'order']);

        $numbers = $this->service->list($filters);
        $summary = $this->service->summary();

        $statusOptions = ['active', 'inactive', 'blocked', 'not_linked'];
        $healthOptions = WhatsappNumberMonitorService::healthOptions();
        $syncOptions = WhatsappNumberMonitorService::syncOptions();
        $staleHours = $this->service->staleHours();

        return view('admin.whatsapp_numbers.monitor', compact(
            'numbers',
            'summary',
            'statusOptions',
            'healthOptions',
            'syncOptions',
            'filters',
            'staleHours'
        ));
    }

    public function show(Request $request, $id)
    {
        $number = $this->service->find((int) $id);

        if ($number === null) {
            abort(404);
        }

        $messages = $this->service->recentMessages((int) $number->tenant_owner_id, 20);

        return view('admin.whatsapp_numbers.monitor_show', compact('number', 'messages'));
    }
}
