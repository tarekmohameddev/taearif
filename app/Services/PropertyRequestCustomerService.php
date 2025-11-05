<?php

namespace App\Services;

use App\Models\ApiCustomer;
use App\Models\Api\UserPropertyRequest;
use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\Api\UserApiCustomerType;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerProcedure;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class PropertyRequestCustomerService
{
    /**
     * Attempt to auto-create a customer from property request
     */
    public function autoCreateFromRequest(UserPropertyRequest $propertyRequest): ?ApiCustomer
    {
        try {
            // Get settings (with caching)
            $settings = $this->getSettings($propertyRequest->user_id);

            // Check if feature is enabled
            if (!$this->shouldCreateCustomer($settings)) {
                Log::debug('Auto-create customer skipped - feature disabled', [
                    'property_request_id' => $propertyRequest->id,
                ]);
                return null;
            }

            // Normalize phone number
            $normalizedPhone = PhoneNormalizer::normalize($propertyRequest->phone);

            // Check for existing customer (with normalized phone)
            if ($this->customerExists($propertyRequest->user_id, $normalizedPhone)) {
                Log::info('Customer already exists with phone number', [
                    'property_request_id' => $propertyRequest->id,
                    'phone_number' => $normalizedPhone,
                ]);
                return null;
            }

            // Create customer in transaction
            return $this->createCustomer($propertyRequest, $settings, $normalizedPhone);

        } catch (\Throwable $e) {
            Log::error('Failed to auto-create customer from property request', [
                'property_request_id' => $propertyRequest->id,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 2000),
            ]);
            return null;
        }
    }

    /**
     * Get settings with caching
     */
    private function getSettings(int $userId): ?PropertyRequestAutoCustomerSetting
    {
        $cacheKey = "property_request_auto_customer_settings:{$userId}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($userId) {
            return PropertyRequestAutoCustomerSetting::where('user_id', $userId)->first();
        });
    }

    /**
     * Clear settings cache
     */
    public static function clearSettingsCache(int $userId): void
    {
        Cache::forget("property_request_auto_customer_settings:{$userId}");
        Cache::forget("customer_defaults:{$userId}");
    }

    /**
     * Get default customer attributes for a user (cached)
     * Returns array with default type_id, priority_id, procedure_id
     */
    private function getDefaultCustomerAttributes(int $userId): array
    {
        $cacheKey = "customer_defaults:{$userId}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($userId) {
            return [
                'type_id' => UserApiCustomerType::where('user_id', $userId)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->value('id'),
                
                'priority_id' => UserApiCustomerPriority::where('user_id', $userId)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->value('id'),
                
                'procedure_id' => UserApiCustomerProcedure::where('user_id', $userId)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->value('id'),
            ];
        });
    }

    /**
     * Check if customer creation should proceed
     */
    private function shouldCreateCustomer(?PropertyRequestAutoCustomerSetting $settings): bool
    {
        return $settings
            && $settings->auto_create_customer
            && $settings->default_stage_id;
    }

    /**
     * Check if customer already exists
     */
    private function customerExists(int $userId, string $normalizedPhone): bool
    {
        return ApiCustomer::where('user_id', $userId)
            ->where('phone_number', $normalizedPhone)
            ->exists();
    }

    /**
     * Create customer with all data
     */
    private function createCustomer(
        UserPropertyRequest $propertyRequest,
        PropertyRequestAutoCustomerSetting $settings,
        string $normalizedPhone
    ): ApiCustomer {
        return DB::transaction(function () use ($propertyRequest, $settings, $normalizedPhone) {
            try {
                // Get cached default attributes (avoids 3 queries per customer creation)
                $defaults = $this->getDefaultCustomerAttributes($propertyRequest->user_id);

                $customer = ApiCustomer::create([
                    'user_id' => $propertyRequest->user_id,
                    'name' => $propertyRequest->full_name,
                    'phone_number' => $normalizedPhone,
                    'email' => null,
                    'stage_id' => $settings->default_stage_id,
                    'city_id' => $propertyRequest->city_id,
                    'district_id' => $propertyRequest->districts_id,
                    'note' => $this->buildCustomerNote($propertyRequest),
                    'password' => bcrypt(Str::random(16)),
                    'type_id' => $defaults['type_id'],
                    'priority_id' => $defaults['priority_id'],
                    'procedure_id' => $defaults['procedure_id'],
                    'created_by_type' => 'system',
                    'created_by_id' => null,
                ]);

                Log::info('Customer auto-created from property request', [
                    'property_request_id' => $propertyRequest->id,
                    'customer_id' => $customer->id,
                    'stage_id' => $settings->default_stage_id,
                    'type_id' => $defaults['type_id'],
                    'priority_id' => $defaults['priority_id'],
                    'phone' => $normalizedPhone,
                ]);

                // TODO: Dispatch event for external integrations
                // event(new CustomerAutoCreated($customer, $propertyRequest));

                return $customer;

            } catch (QueryException $e) {
                // Handle duplicate key constraint violation gracefully (race condition)
                // Error code 23000 = Integrity constraint violation (MySQL, PostgreSQL, SQLite)
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                    Log::info('Customer creation skipped - unique constraint violation (race condition)', [
                        'property_request_id' => $propertyRequest->id,
                        'phone_number' => $normalizedPhone,
                    ]);

                    // Return existing customer instead of failing
                    return ApiCustomer::where('user_id', $propertyRequest->user_id)
                        ->where('phone_number', $normalizedPhone)
                        ->firstOrFail();
                }
                throw $e;
            }
        });
    }

    /**
     * Build a formatted note from property request details
     */
    private function buildCustomerNote(UserPropertyRequest $pr): string
    {
        $lines = ["تم إنشاء العميل تلقائياً من طلب عقار (#{$pr->id}).", ""];

        $fields = [
            'property_type' => 'نوع العقار',
            'region' => 'المنطقة',
            'seriousness' => 'الجدية',
            'purchase_goal' => 'الهدف من الشراء',
            'purchase_method' => 'طريقة الدفع',
        ];

        foreach ($fields as $field => $label) {
            if ($pr->$field) {
                $lines[] = "{$label}: {$pr->$field}";
            }
        }

        if ($pr->budget_from || $pr->budget_to) {
            $budgetFrom = $pr->budget_from ? number_format($pr->budget_from, 0, '.', ',') : '';
            $budgetTo = $pr->budget_to ? number_format($pr->budget_to, 0, '.', ',') : '';
            $lines[] = "الميزانية: {$budgetFrom} - {$budgetTo} ريال";
        }

        if ($pr->area_from || $pr->area_to) {
            $lines[] = "المساحة: {$pr->area_from} - {$pr->area_to} متر مربع";
        }

        if ($pr->contact_on_whatsapp) {
            $lines[] = "يفضل التواصل عبر واتساب: نعم";
        }

        if ($pr->notes) {
            $lines[] = "";
            $lines[] = "ملاحظات إضافية:";
            $lines[] = $pr->notes;
        }

        return implode("\n", $lines);
    }
}

