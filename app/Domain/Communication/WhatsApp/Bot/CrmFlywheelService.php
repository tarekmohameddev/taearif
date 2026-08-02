<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Models\AiCustomerProfile;
use App\Models\ApiCustomer;
use App\Models\Api\UserPropertyRequest;
use App\Models\WaConversationAiState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * After a productive bot conversation, write structured facts to the CRM:
 * - Create or update ApiCustomer (lead) with phone + name from bot facts
 * - Create UserPropertyRequest if intent + location/type are known
 * - Fire matching observers so existing automation runs
 */
final class CrmFlywheelService
{
    public function sync(
        int $tenantId,
        string $customerPhone,
        WaConversationAiState $aiState,
    ): void {
        $facts = $aiState->facts ?? [];
        if (empty($facts)) { return; }

        try {
            DB::transaction(function () use ($tenantId, $customerPhone, $aiState, $facts) {
                $customer = $this->upsertCustomer($tenantId, $customerPhone, $facts);
                if ($customer === null) { return; }

                $this->upsertPropertyRequest($tenantId, $customer, $facts, $aiState);
            });
        } catch (\Throwable $e) {
            Log::warning('bot.crm_flywheel.failed', [
                'tenant' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function upsertCustomer(int $tenantId, string $phone, array $facts): ?ApiCustomer
    {
        $name = $facts['name'] ?? null;

        $customer = ApiCustomer::where('user_id', $tenantId)
            ->where('phone_number', $this->normalizePhone($phone))
            ->first();

        if ($customer !== null) {
            // Update name if we learned it from the bot
            if ($name !== null && ($customer->name === null || $customer->name === '')) {
                $customer->update(['name' => $name]);
            }
            return $customer;
        }

        // Create new lead
        $customer = ApiCustomer::create([
            'user_id'      => $tenantId,
            'phone_number' => $this->normalizePhone($phone),
            'name'         => $name ?? 'عميل واتساب',
            'source'       => 'whatsapp_bot',
        ]);

        return $customer;
    }

    private function upsertPropertyRequest(
        int $tenantId,
        ApiCustomer $customer,
        array $facts,
        WaConversationAiState $state,
    ): void {
        // Only create a request if we have at least intent and one location/type signal
        $intent = $facts['intent'] ?? null;
        if ($intent === null) { return; }

        $hasLocation = ! empty($facts['city']) || ! empty($facts['district']);
        $hasType     = ! empty($facts['type']);
        if (! $hasLocation && ! $hasType) { return; }

        // Check if a recent request already exists for this customer (within 30 days)
        $existing = UserPropertyRequest::where('user_id', $tenantId)
            ->where('api_customer_id', $customer->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->first();

        if ($existing !== null) {
            // Update budget if improved
            if (! empty($facts['budget_max']) && (float) $facts['budget_max'] > 0) {
                $existing->update(['max_price' => (float) $facts['budget_max']]);
            }
            return;
        }

        $purposeMap = ['rent' => 'rent', 'sale' => 'sell', 'inquiry' => 'sell'];
        $typeMap = [
            'apartment' => 'apartment', 'villa' => 'villa',
            'office' => 'office', 'land' => 'land', 'warehouse' => 'warehouse',
        ];

        $requestData = [
            'user_id'         => $tenantId,
            'api_customer_id' => $customer->id,
            'source'          => 'whatsapp_bot',
            'purpose'         => $purposeMap[$intent] ?? 'sell',
            'note'            => $this->buildNote($facts, $state),
        ];

        if (! empty($facts['type']))     { $requestData['property_type'] = $typeMap[$facts['type']] ?? $facts['type']; }
        if (! empty($facts['bedrooms'])) { $requestData['rooms'] = (int) $facts['bedrooms']; }
        if (! empty($facts['budget_max'])) { $requestData['max_price'] = (float) $facts['budget_max']; }

        try {
            UserPropertyRequest::create($requestData);
        } catch (\Throwable $e) {
            Log::warning('bot.crm_flywheel.request_create_failed', ['error' => $e->getMessage()]);
        }
    }

    private function buildNote(array $facts, WaConversationAiState $state): string
    {
        $parts = ['[من بوت واتساب]'];
        if (! empty($facts['city']))     { $parts[] = 'المدينة: ' . $facts['city']; }
        if (! empty($facts['district'])) { $parts[] = 'الحي: ' . $facts['district']; }
        if (! empty($facts['budget_max'])) { $parts[] = 'الميزانية: ' . number_format((float) $facts['budget_max']) . ' ريال'; }
        if (! empty($facts['urgency']))  { $parts[] = 'الاستعجال: ' . $facts['urgency']; }
        if ($state->requirements)        { $parts[] = 'المتطلبات: ' . $state->requirements; }
        return implode(' | ', $parts);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-()]+/', '', $phone) ?? $phone;
        $phone = ltrim($phone, '+');
        if ($phone !== '' && $phone[0] !== '0' && ! str_starts_with($phone, '966')) {
            $phone = '+' . $phone;
        }
        return $phone;
    }
}
