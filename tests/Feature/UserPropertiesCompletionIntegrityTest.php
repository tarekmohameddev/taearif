<?php

namespace Tests\Feature;

use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Verifies that for user_id = 1037, all properties with completion_status = 'complete'
 * have no missing required fields in user_properties and user_property_contents.
 *
 * Required fields: title, price, address, description, purpose, property_type, area
 * - From user_properties: price, purpose, property_type, area
 * - From user_property_contents (default language): title, address, description
 *
 * Skips when user_properties does not exist (e.g. empty taearif_testing). To check
 * user 1037 against your main DB, use:
 *   php artisan app:check-user-properties-completion 1037
 */
class UserPropertiesCompletionIntegrityTest extends TestCase
{
    protected array $requiredFields = ['title', 'price', 'address', 'description', 'purpose', 'property_type', 'area'];

    /**
     * For user_id 1037, complete properties must not have missing required fields.
     *
     * @test
     */
    public function user_1037_complete_properties_have_no_missing_required_fields(): void
    {
        if (! Schema::hasTable('user_properties')) {
            $this->markTestSkipped('user_properties table does not exist. Run: php artisan app:check-user-properties-completion 1037');
        }

        $userId = 1037;

        $properties = Property::where('user_id', $userId)
            ->where('completion_status', 'complete')
            ->select(['id', 'user_id', 'price', 'purpose', 'property_type', 'area', 'completion_status'])
            ->get();

        if ($properties->isEmpty()) {
            $this->assertTrue(true, 'User 1037 has no properties with completion_status=complete; nothing to validate.');
            return;
        }

        $problems = [];

        foreach ($properties as $property) {
            $missing = $this->getMissingFieldsForProperty($property);
            if (!empty($missing)) {
                $problems[] = [
                    'property_id' => $property->id,
                    'missing_fields' => $missing,
                ];
            }
        }

        $message = "User {$userId}: some complete properties have missing required fields. "
            . "Properties and related user_property_contents (default language) were checked.\n";
        foreach ($problems as $p) {
            $message .= "  - Property ID {$p['property_id']}: missing " . implode(', ', $p['missing_fields']) . "\n";
        }

        $this->assertEmpty($problems, $message);
    }

    /**
     * Same logic as FixLegacyIncompleteProperties::getMissingFields.
     */
    protected function getMissingFieldsForProperty(Property $property): array
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
            if (is_null($value) || (is_string($value) && trim((string) $value) === '') || $value === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }
}
