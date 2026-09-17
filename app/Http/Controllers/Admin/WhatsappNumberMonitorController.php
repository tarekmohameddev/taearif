<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReconcileWhatsappWabaRequest;
use App\Services\Admin\WhatsappNumberDiagnosticsService;
use App\Services\Admin\WhatsappNumberMonitorService;
use App\Services\Admin\WhatsappNumberWabaReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp number monitoring and explicitly confirmed WABA reconciliation.
 */
class WhatsappNumberMonitorController extends Controller
{
    public function __construct(
        private WhatsappNumberMonitorService $service,
        private WhatsappNumberDiagnosticsService $diagnostics,
        private WhatsappNumberWabaReconciliationService $wabaReconciliation
    ) {}

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

    public function diagnose(Request $request, $id)
    {
        $number = $this->service->find((int) $id);

        if ($number === null) {
            abort(404);
        }

        try {
            $result = $this->diagnostics->diagnose((int) $id);

            $result = $this->formatDiagnosticsForFlash($result);

            return redirect()
                ->route('admin.whatsapp-numbers.monitor.show', $id)
                ->with('diagnostics', $result);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.whatsapp-numbers.monitor.show', $id)
                ->with('error', $e->getMessage());
        }
    }

    public function reconcileWaba(ReconcileWhatsappWabaRequest $request, $id)
    {
        if ($this->service->find((int) $id) === null) {
            abort(404);
        }

        try {
            $result = $this->wabaReconciliation->reconcile(
                (int) $id,
                [
                    'phone_id' => $request->validated('expected_phone_id'),
                    'stored_waba_id' => $request->validated('expected_stored_waba_id'),
                    'verified_waba_id' => $request->validated('expected_verified_waba_id'),
                ],
                (int) auth('admin')->id()
            );

            $redirect = redirect()
                ->route('admin.whatsapp-numbers.monitor.show', $id)
                ->with('diagnostics', $this->formatDiagnosticsForFlash($result['diagnostics'] ?? []));

            if (! ($result['ok'] ?? false)) {
                return $redirect->with('error', $this->reconciliationBlockedMessage(
                    (string) ($result['reason_code'] ?? 'blocked')
                ));
            }

            if (($result['result'] ?? '') === 'subscription_warning') {
                return $redirect->with(
                    'warning',
                    __('WABA linkage was corrected, but Meta webhook subscription failed. Review diagnostics before using the number.')
                );
            }

            return $redirect->with(
                'success',
                __('WABA linkage was reconciled and Meta webhook subscription completed.')
            );
        } catch (\Throwable $e) {
            Log::error('admin.whatsapp_waba_reconciliation.failed', [
                'whatsapp_user_id' => (int) $id,
                'admin_id' => (int) auth('admin')->id(),
                'exception' => get_class($e),
            ]);

            return redirect()
                ->route('admin.whatsapp-numbers.monitor.show', $id)
                ->with('error', __('WABA reconciliation failed without applying a partial database update.'));
        }
    }

    private function formatDiagnosticsForFlash(array $diagnostics): array
    {
        if (($diagnostics['checked_at'] ?? null) instanceof Carbon) {
            $diagnostics['checked_at'] = $diagnostics['checked_at']->format('Y-m-d H:i');
        }

        return $diagnostics;
    }

    private function reconciliationBlockedMessage(string $reasonCode): string
    {
        return match ($reasonCode) {
            'stale_confirmation', 'stale_state' => __('The WhatsApp connection changed after diagnostics. Diagnose again before retrying.'),
            'phone_not_found' => __('The stored phone ID was not found in any WABA accessible to this token. Reconnect through Meta.'),
            'ambiguous_phone_ownership' => __('The phone matched more than one WABA. Automatic reconciliation is blocked.'),
            'meta_lookup_incomplete' => __('Meta could not verify every accessible WABA. No database changes were applied.'),
            'owner_mismatch' => __('The matching Communication number belongs to another tenant. Automatic reconciliation is blocked.'),
            'number_not_active' => __('Only active WhatsApp numbers can be reconciled.'),
            'token_not_usable', 'token_missing', 'token_debug_failed' => __('The access token is missing, invalid, or expired. Reconnect through Meta.'),
            default => __('This connection is not eligible for automatic WABA reconciliation.'),
        };
    }
}
