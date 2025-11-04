<?php

namespace App\Observers\Matching;

use App\Models\ApiCustomer;
use App\Models\Api\UserPropertyRequest;
use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Services\Matching\MatchingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UsersPropertyRequestObserver
{
    public function created(UserPropertyRequest $model): void
    {
        Log::info('UsersPropertyRequestObserver.created fired', ['id' => $model->id]);

        // Auto-create customer if setting is enabled
        $this->autoCreateCustomer($model);

        // Generate property matches
        app(MatchingService::class)->generateMatchesForRequest('web', $model->id, 25, true);
    }

    public function updated(UserPropertyRequest $model): void
    {
        Log::info('UsersPropertyRequestObserver.updated fired', ['id' => $model->id, 'changes' => $model->getChanges()]);
        app(MatchingService::class)->generateMatchesForRequest('web', $model->id, 25, true);
    }

    /**
     * Auto-create customer from property request if setting is enabled
     */
    private function autoCreateCustomer(UserPropertyRequest $propertyRequest): void
    {
        try {
            // Get settings for this tenant
            $settings = PropertyRequestAutoCustomerSetting::where('user_id', $propertyRequest->user_id)->first();

            // Check if auto-create is enabled
            if (!$settings || !$settings->auto_create_customer || !$settings->default_stage_id) {
                Log::info('Auto-create customer disabled or no stage set', [
                    'property_request_id' => $propertyRequest->id,
                    'user_id' => $propertyRequest->user_id,
                ]);
                return;
            }

            // Check if customer already exists with this phone number
            $existingCustomer = ApiCustomer::where('user_id', $propertyRequest->user_id)
                ->where('phone_number', $propertyRequest->phone)
                ->first();

            if ($existingCustomer) {
                Log::info('Customer already exists with this phone number', [
                    'property_request_id' => $propertyRequest->id,
                    'customer_id' => $existingCustomer->id,
                    'phone_number' => $propertyRequest->phone,
                ]);
                return;
            }

            // Create customer
            $customer = ApiCustomer::create([
                'user_id' => $propertyRequest->user_id,
                'name' => $propertyRequest->full_name,
                'phone_number' => $propertyRequest->phone,
                'email' => null, // Property requests don't capture email
                'stage_id' => $settings->default_stage_id,
                'city_id' => $propertyRequest->city_id,
                'district_id' => $propertyRequest->districts_id,
                'note' => $this->buildCustomerNote($propertyRequest),
                'password' => bcrypt(Str::random(16)), // Generate random password
                'type_id' => null,
                'priority_id' => null,
                'procedure_id' => null,
            ]);

            Log::info('Customer auto-created from property request', [
                'property_request_id' => $propertyRequest->id,
                'customer_id' => $customer->id,
                'stage_id' => $settings->default_stage_id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to auto-create customer from property request', [
                'property_request_id' => $propertyRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Build a note from property request details
     */
    private function buildCustomerNote(UserPropertyRequest $pr): string
    {
        $note = "تم إنشاء العميل تلقائياً من طلب عقار.\n\n";

        if ($pr->property_type) {
            $note .= "نوع العقار: {$pr->property_type}\n";
        }

        if ($pr->region) {
            $note .= "المنطقة: {$pr->region}\n";
        }

        if ($pr->budget_from || $pr->budget_to) {
            $budgetFrom = $pr->budget_from ? number_format($pr->budget_from) : '';
            $budgetTo = $pr->budget_to ? number_format($pr->budget_to) : '';
            $note .= "الميزانية: {$budgetFrom} - {$budgetTo}\n";
        }

        if ($pr->area_from || $pr->area_to) {
            $note .= "المساحة: {$pr->area_from} - {$pr->area_to} متر مربع\n";
        }

        if ($pr->seriousness) {
            $note .= "الجدية: {$pr->seriousness}\n";
        }

        if ($pr->purchase_goal) {
            $note .= "الهدف من الشراء: {$pr->purchase_goal}\n";
        }

        if ($pr->purchase_method) {
            $note .= "طريقة الدفع: {$pr->purchase_method}\n";
        }

        if ($pr->contact_on_whatsapp) {
            $note .= "يفضل التواصل عبر واتساب: نعم\n";
        }

        if ($pr->notes) {
            $note .= "\nملاحظات إضافية:\n{$pr->notes}\n";
        }

        return $note;
    }
}


