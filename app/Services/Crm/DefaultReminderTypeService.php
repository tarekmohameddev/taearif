<?php

namespace App\Services\Crm;

use App\Models\ReminderType;
use Illuminate\Support\Collection;

class DefaultReminderTypeService
{
    /**
     * Get all default reminder types (hardcoded)
     *
     * @return array
     */
    public static function getDefaultTypes(): array
    {
        return [
            [
                'id' => -1,
                'name' => 'Phone Call',
                'name_ar' => 'مكالمه',
                'color' => '#10b981',
                'icon' => 'Phone',
                'order' => 0,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'id' => -2,
                'name' => 'Office Visit',
                'name_ar' => 'زياره مكتب',
                'color' => '#3b82f6',
                'icon' => 'Building',
                'order' => 1,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'id' => -3,
                'name' => 'Site Inspection',
                'name_ar' => 'معاينه موقع',
                'color' => '#f59e0b',
                'icon' => 'MapPin',
                'order' => 2,
                'is_active' => true,
                'is_default' => true,
            ],
        ];
    }

    /**
     * Get a specific default type by ID
     *
     * @param int $id
     * @return array|null
     */
    public static function getDefaultTypeById(int $id): ?array
    {
        $defaults = self::getDefaultTypes();
        
        foreach ($defaults as $type) {
            if ($type['id'] === $id) {
                return $type;
            }
        }
        
        return null;
    }

    /**
     * Check if an ID is a default type ID
     *
     * @param int $id
     * @return bool
     */
    public static function isDefaultTypeId(int $id): bool
    {
        return $id < 0 && self::getDefaultTypeById($id) !== null;
    }

    /**
     * Get or create a default type in the database for a tenant
     *
     * @param int $tenantId
     * @param int $defaultId
     * @return ReminderType
     */
    public static function getOrCreateDefaultType(int $tenantId, int $defaultId): ReminderType
    {
        $defaultType = self::getDefaultTypeById($defaultId);
        
        if (!$defaultType) {
            throw new \InvalidArgumentException("Invalid default type ID: {$defaultId}");
        }

        // Check if default type already exists in database for this tenant
        // We check by name_ar to avoid duplicates
        $existingType = ReminderType::forUser($tenantId)
            ->where('name_ar', $defaultType['name_ar'])
            ->where('is_default', true)
            ->first();

        if ($existingType) {
            return $existingType;
        }

        // Create the default type in database
        return ReminderType::create([
            'user_id' => $tenantId,
            'name' => $defaultType['name'],
            'name_ar' => $defaultType['name_ar'],
            'color' => $defaultType['color'],
            'icon' => $defaultType['icon'],
            'order' => $defaultType['order'],
            'is_active' => $defaultType['is_active'],
            'is_default' => true,
        ]);
    }

    /**
     * Get default type identifier (mapping from ID to identifier)
     *
     * @param int $id
     * @return string|null
     */
    public static function getDefaultTypeIdentifier(int $id): ?string
    {
        return match ($id) {
            -1 => 'phone_call',
            -2 => 'office_visit',
            -3 => 'site_inspection',
            default => null,
        };
    }

    /**
     * Get default type ID from identifier
     *
     * @param string $identifier
     * @return int|null
     */
    public static function getDefaultTypeIdFromIdentifier(string $identifier): ?int
    {
        return match ($identifier) {
            'phone_call' => -1,
            'office_visit' => -2,
            'site_inspection' => -3,
            default => null,
        };
    }
}
