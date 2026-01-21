<?php

namespace App\Services;

use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Support\Facades\DB;

class PropertyConflictDetectionService
{
    /**
     * Check for conflicts before completing a property
     * 
     * @param Property $property
     * @param array $data
     * @return array Array of conflicts with severity levels
     */
    public function detectConflicts(Property $property, array $data): array
    {
        $conflicts = [];
        
        // Check for duplicate title + address combination
        if (isset($data['title']) && isset($data['address'])) {
            $duplicate = PropertyContent::where('title', $data['title'])
                ->where('address', $data['address'])
                ->whereHas('property', function($q) use ($property) {
                    $q->where('user_id', $property->user_id)
                      ->where('id', '!=', $property->id)
                      ->where('completion_status', 'complete');
                })
                ->first();
            
            if ($duplicate) {
                $conflicts[] = [
                    'type' => 'duplicate',
                    'field' => 'title+address',
                    'message' => 'A property with the same title and address already exists',
                    'severity' => 'error'
                ];
            }
        }
        
        // Validate required fields are present
        $requiredFields = ['title', 'price', 'address', 'description', 'purpose', 'type', 'area'];
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '') || $data[$field] === null) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $conflicts[] = [
                'type' => 'missing_fields',
                'fields' => $missing,
                'message' => 'Required fields are missing: ' . implode(', ', $missing),
                'severity' => 'error'
            ];
        }
        
        // Validate data formats
        if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'price',
                'message' => 'Price must be a positive number',
                'severity' => 'error'
            ];
        }
        
        if (isset($data['area']) && (!is_numeric($data['area']) || $data['area'] < 1)) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'area',
                'message' => 'Area must be at least 1',
                'severity' => 'error'
            ];
        }
        
        if (isset($data['purpose']) && !in_array($data['purpose'], ['sale', 'rent'])) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'purpose',
                'message' => 'Purpose must be either "sale" or "rent"',
                'severity' => 'error'
            ];
        }
        
        if (isset($data['type']) && !in_array($data['type'], ['residential', 'commercial'])) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'type',
                'message' => 'Type must be either "residential" or "commercial"',
                'severity' => 'error'
            ];
        }
        
        // Validate title length
        if (isset($data['title']) && (strlen($data['title']) < 3 || strlen($data['title']) > 255)) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'title',
                'message' => 'Title must be between 3 and 255 characters',
                'severity' => 'error'
            ];
        }
        
        // Validate address length
        if (isset($data['address']) && (strlen($data['address']) < 5 || strlen($data['address']) > 500)) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'address',
                'message' => 'Address must be between 5 and 500 characters',
                'severity' => 'error'
            ];
        }
        
        // Validate description length
        if (isset($data['description']) && strlen($data['description']) < 10) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'description',
                'message' => 'Description must be at least 10 characters',
                'severity' => 'error'
            ];
        }
        
        return $conflicts;
    }
    
    /**
     * Check if property can be completed (no errors)
     * 
     * @param Property $property
     * @param array $data
     * @return bool
     */
    public function canComplete(Property $property, array $data): bool
    {
        $conflicts = $this->detectConflicts($property, $data);
        
        // Only allow completion if there are no errors
        foreach ($conflicts as $conflict) {
            if ($conflict['severity'] === 'error') {
                return false;
            }
        }
        
        return true;
    }
}
