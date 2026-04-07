<?php

namespace App\Services;

class PropertyTranslationService
{
    /**
     * Translate property type to Arabic
     */
    public function translateType(?string $type): ?string
    {
        return match($type) {
            'residential' => 'سكني',
            'commercial' => 'تجاري',
            default => $type,
        };
    }

    /**
     * Translate property purpose to Arabic
     */
    public function translatePurpose(?string $purpose): ?string
    {
        return match($purpose) {
            'rent' => 'للإيجار',
            'sale' => 'للبيع',
            default => $purpose,
        };
    }

    /**
     * Translate payment method to Arabic
     */
    public function translatePaymentMethod(?string $paymentMethod): ?string
    {
        return match($paymentMethod) {
            'monthly' => 'شهري',
            'quarterly' => 'ربع سنوي',
            'semi_annual' => 'نصف سنوي',
            'annual' => 'سنوي',
            default => $paymentMethod,
        };
    }

    /**
     * Get all translations for a property
     * Useful for bulk translation
     */
    public function translateAll(array $data): array
    {
        return [
            'type_ar' => $this->translateType($data['property_type'] ?? ($data['type'] ?? null)),
            'purpose_ar' => $this->translatePurpose($data['purpose'] ?? null),
            'payment_method_ar' => $this->translatePaymentMethod($data['payment_method'] ?? null),
        ];
    }
}

