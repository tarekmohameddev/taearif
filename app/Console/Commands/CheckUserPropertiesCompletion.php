<?php

namespace App\Console\Commands;

use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Console\Command;

/**
 * Check that for a given user_id, all properties with completion_status = 'complete'
 * have no missing required fields in user_properties and user_property_contents.
 *
 * Usage: php artisan app:check-user-properties-completion 1037
 */
class CheckUserPropertiesCompletion extends Command
{
    protected $signature = 'app:check-user-properties-completion 
                            {user_id : The user ID to check}
                            {--only-complete : Only check completion_status = "complete" (default)}';

    protected $description = 'Verify complete properties for a user have no missing required fields (user_properties + user_property_contents)';

    protected array $requiredFields = ['title', 'price', 'address', 'description', 'purpose', 'type', 'area'];

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');

        $query = Property::where('user_id', $userId)
            ->where('completion_status', 'complete')
            ->select(['id', 'user_id', 'price', 'purpose', 'type', 'area', 'completion_status']);

        $properties = $query->get();

        if ($properties->isEmpty()) {
            $this->info("User {$userId}: no properties with completion_status = 'complete'. Nothing to check.");
            return self::SUCCESS;
        }

        $this->info("User {$userId}: checking {$properties->count()} property(ies) with completion_status = 'complete'.");
        $this->newLine();

        $problems = [];

        foreach ($properties as $property) {
            $missing = $this->getMissingFields($property);
            if (!empty($missing)) {
                $problems[] = [
                    'property_id' => $property->id,
                    'missing_fields' => $missing,
                ];
            }
        }

        if (empty($problems)) {
            $this->info('All complete properties have required fields (user_properties + user_property_contents for default language).');
            return self::SUCCESS;
        }

        $this->warn('Some complete properties have missing required fields:');
        $this->newLine();
        $rows = [];
        foreach ($problems as $p) {
            $rows[] = [$p['property_id'], implode(', ', $p['missing_fields'])];
        }
        $this->table(['Property ID', 'Missing fields'], $rows);
        $this->newLine();
        $this->info('Required: title, price, address, description, purpose, type, area.');
        $this->info('Content fields come from user_property_contents for the owner default language.');

        return self::FAILURE;
    }

    protected function getMissingFields(Property $property): array
    {
        $defaultLanguage = Language::where('user_id', $property->user_id)
            ->where('is_default', 1)
            ->first();

        if (!$defaultLanguage) {
            return $this->requiredFields;
        }

        $content = PropertyContent::where('property_id', $property->id)
            ->where('language_id', $defaultLanguage->id)
            ->first();

        $currentData = [
            'title' => $content?->title,
            'price' => $property->price,
            'address' => $content?->address,
            'description' => $content?->description,
            'purpose' => $property->purpose,
            'property_type' => $property->property_type,
            'area' => $property->area,
        ];

        $missing = [];
        foreach ($this->requiredFields as $field) {
            $value = $currentData[$field] ?? null;
            if (is_null($value) || (is_string($value) && trim($value) === '') || $value === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }
}
