<?php

namespace App\Http\Resources\Crm;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReminderTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        // Handle both model instances and array/object data (for hardcoded defaults)
        $resource = $this->resource;
        $isArray = is_array($resource);
        
        // Helper to get value from resource (works for both arrays and objects)
        $get = function ($key, $default = null) use ($resource, $isArray) {
            if ($isArray) {
                return $resource[$key] ?? $default;
            }
            return $resource->$key ?? $default;
        };

        // Helper to check if property exists
        $has = function ($key) use ($resource, $isArray) {
            if ($isArray) {
                return isset($resource[$key]);
            }
            return isset($resource->$key);
        };
        
        $id = $get('id');
        $userId = $get('user_id');
        $name = $get('name');
        $nameAr = $get('name_ar');
        $description = $get('description');
        $color = $get('color', '#6366f1');
        $icon = $get('icon', 'Bell');
        $order = $get('order', 0);
        $isActive = $get('is_active', true);
        $isDefault = $get('is_default', false);
        
        // Handle timestamps - only available for DB models
        $createdAt = null;
        $updatedAt = null;
        if (!$isArray && isset($resource->created_at)) {
            // Check if it's a Carbon instance (for DB models) or null/other (for stdClass objects)
            if (is_object($resource->created_at) && method_exists($resource->created_at, 'toIso8601String')) {
                $createdAt = $resource->created_at->toIso8601String();
            }
        }
        if (!$isArray && isset($resource->updated_at)) {
            // Check if it's a Carbon instance (for DB models) or null/other (for stdClass objects)
            if (is_object($resource->updated_at) && method_exists($resource->updated_at, 'toIso8601String')) {
                $updatedAt = $resource->updated_at->toIso8601String();
            }
        }
        
        // Handle reminders_count
        $remindersCount = 0;
        if ($has('reminders_count')) {
            $remindersCount = $get('reminders_count', 0);
        } elseif (!$isArray && method_exists($resource, 'reminders')) {
            $remindersCount = $resource->reminders()->count();
        }
        
        return [
            'id' => $id,
            'user_id' => $userId,
            'name' => $name,
            'name_ar' => $nameAr,
            'description' => $description,
            'color' => $color,
            'icon' => $icon,
            'order' => $order,
            'is_active' => $isActive,
            'is_default' => $isDefault,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'reminders_count' => $remindersCount,
        ];
    }
}
