<?php

namespace App\Http\Resources\Admin\Inquiry;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

/**
 * Inquiry Resource
 *
 * Transforms Inquiry model into JSON response
 */
class InquiryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant' => [
                'id' => $this->user_id,
                'name' => $this->user ? trim($this->user->first_name . ' ' . $this->user->last_name) : null,
                'email' => $this->user?->email,
            ],
            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->customer?->name,
                'phone' => $this->customer?->phone,
                'email' => $this->customer?->email ?? $this->user?->email,
            ],
            'contact' => [
                'phone_number' => $this->phone_number,
            ],
            'inquiry' => [
                // 'message' => $this->message,
                'type' => $this->inquiry_type,
                'property_type' => $this->property_type,
                
            ],
            'property_details' => [
                'budget' => $this->budget ? (float) $this->budget : null,
                'currency' => $this->currency,
                'bedrooms' => $this->bedrooms,
                'bathrooms' => $this->bathrooms,
                'min_area_sqm' => $this->min_area_sqm ? (float) $this->min_area_sqm : null,
                'max_area_sqm' => $this->max_area_sqm ? (float) $this->max_area_sqm : null,
                'furnished' => (bool) $this->furnished,
            ],
            'location' => [
                'location_text' => $this->location,
                'country_code' => $this->country_code,
                'region_code' => $this->region_code,
                'region_name' => $this->region_name,
                'city' => $this->city,
                'district' => $this->district,
                'latitude' => $this->latitude ? (float) $this->latitude : null,
                'longitude' => $this->longitude ? (float) $this->longitude : null,
                'confidence' => $this->location_confidence ? (float) $this->location_confidence : null,
            ],
            'metadata' => [
                'source_channel' => $this->source_channel,
                'language' => $this->lang,
                'detected_entities' => $this->detected_entities_json,
            ],
            'dates' => [
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'created_at_arabic' => $this->getArabicDate(),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ],
            'assigned_to' => $this->getAssignedToInfo(),
        ];
    }

    /**
     * Get status with Arabic label
     *
     * @return array
     */
    private function getStatus(): array
    {
        $table = $this->resource->getTable();
        
        // Check if status column exists
        if (Schema::hasColumn($table, 'status') && $this->status) {
            $statusValue = $this->status;
        } else {
            // Derive status from created_at (new if < 24h old)
            $statusValue = $this->created_at && $this->created_at->diffInHours(now()) < 24 
                ? 'new' 
                : 'in_progress';
        }

        $statusMap = [
            'new' => 'جديد',
            'in_progress' => 'قيد المعالجة',
            'closed' => 'مغلق',
            'open' => 'جديد',
            'resolved' => 'مغلق',
        ];

        return [
            'value' => $statusValue,
            'label' => $statusMap[$statusValue] ?? 'جديد',
        ];
    }

    /**
     * Get category label in Arabic
     *
     * @return string|null
     */
    private function getCategoryLabel(): ?string
    {
        $inquiryType = mb_strtolower(trim($this->inquiry_type ?? ''));
        $propertyType = mb_strtolower(trim($this->property_type ?? ''));

        // Map inquiry_type to category
        $inquiryTypeMap = [
            'buy' => 'مبيعات',
            'purchase' => 'مبيعات',
            'sale' => 'مبيعات',
            'rent' => 'مبيعات',
            'rental' => 'مبيعات',
            'lease' => 'مبيعات',
            'support' => 'دعم فني',
            'technical' => 'دعم فني',
            'feature' => 'طلب ميزة',
            'feature_request' => 'طلب ميزة',
            'general' => 'عام',
            'inquire' => 'عام',
            'inquiry' => 'عام',
            'question' => 'عام',
        ];

        // Check inquiry_type first
        if (!empty($inquiryType) && isset($inquiryTypeMap[$inquiryType])) {
            return $inquiryTypeMap[$inquiryType];
        }

        // Fallback to property_type mapping
        $propertyTypeMap = [
            'residential' => 'عام',
            'commercial' => 'عام',
            'industrial' => 'عام',
            'agricultural' => 'عام',
        ];

        if (!empty($propertyType) && isset($propertyTypeMap[$propertyType])) {
            return $propertyTypeMap[$propertyType];
        }

        // Default category
        return 'عام';
    }

    /**
     * Get priority label in Arabic with level
     *
     * @return array
     */
    private function getPriorityLabel(): array
    {
        $urgency = mb_strtolower(trim($this->urgency ?? 'medium'));

        $priorityMap = [
            'high' => ['label' => 'عالية', 'level' => 'high'],
            'urgent' => ['label' => 'عالية', 'level' => 'high'],
            'medium' => ['label' => 'متوسطة', 'level' => 'medium'],
            'normal' => ['label' => 'متوسطة', 'level' => 'medium'],
            'low' => ['label' => 'منخفضة', 'level' => 'low'],
        ];

        $priority = $priorityMap[$urgency] ?? $priorityMap['medium'];

        return [
            'value' => $urgency,
            'label' => $priority['label'],
            'level' => $priority['level'],
        ];
    }

    /**
     * Get priority level (high/medium/low)
     *
     * @return string
     */
    private function getPriorityLevel(): string
    {
        return $this->getPriorityLabel()['level'];
    }

    /**
     * Get Arabic formatted date
     *
     * @return string|null
     */
    private function getArabicDate(): ?string
    {
        if (!$this->created_at) {
            return null;
        }

        try {
            return $this->created_at->locale('ar')->isoFormat('D MMMM YYYY');
        } catch (\Exception $e) {
            // Fallback to standard format if Arabic formatting fails
            return $this->created_at->format('Y-m-d');
        }
    }


    /**
     * Get assigned admin info if column exists
     *
     * @return array|null
     */
    private function getAssignedToInfo(): ?array
    {
        $table = $this->resource->getTable();

        // Check if assigned_to column exists
        if (!Schema::hasColumn($table, 'assigned_to')) {
            return null;
        }

        // If column exists but no assignment, return null
        if (!$this->assigned_to) {
            return null;
        }

        // Load assigned admin relationship if not already loaded
        if (!$this->relationLoaded('assignedAdmin')) {
            $this->load('assignedAdmin');
        }

        if (!$this->assignedAdmin) {
            return null;
        }

        return [
            'id' => $this->assignedAdmin->id,
            'name' => trim(($this->assignedAdmin->first_name ?? '') . ' ' . ($this->assignedAdmin->last_name ?? '')),
            'email' => $this->assignedAdmin->email,
        ];
    }
}

