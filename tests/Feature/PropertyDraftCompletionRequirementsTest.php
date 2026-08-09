<?php

namespace Tests\Feature;

use App\Models\User\RealestateManagement\Property;
use App\Services\PropertyConflictDetectionService;
use App\Support\PropertyCompletionRequirements;
use Tests\TestCase;

/**
 * Draft completion is gated on five fields only:
 * title, address, description, featured_image, property_type.
 *
 * price / purpose / area are optional — supplying them is validated for format,
 * omitting them must not block completion. Read-only: no rows are created.
 */
class PropertyDraftCompletionRequirementsTest extends TestCase
{
    /** A user id no fixture uses, so the duplicate lookup can never match. */
    private const ORPHAN_USER_ID = 999999999;

    private function draft(): Property
    {
        $property = new Property();
        $property->user_id = self::ORPHAN_USER_ID;

        return $property;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function completeData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'شقة فاخرة في القلب التجاري',
            'address' => 'شارع الملك فهد، حي العليا',
            'description' => 'شقة ثلاث غرف نوم بإطلالة مدينة ومصعد وأمن',
            'featured_image' => 'https://example.com/img1.jpg',
            'property_type' => 'residential',
        ], $overrides);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function errors(array $data): array
    {
        $conflicts = (new PropertyConflictDetectionService())->detectConflicts($this->draft(), $data);

        return array_values(array_filter($conflicts, fn ($c) => $c['severity'] === 'error'));
    }

    /** @test */
    public function the_five_required_fields_are_enough_to_complete(): void
    {
        $this->assertSame([], $this->errors($this->completeData()));
    }

    /** @test */
    public function price_purpose_and_area_are_not_required(): void
    {
        // The regression this guards: these three used to be required, so a
        // draft carrying only the five could never be completed.
        $errors = $this->errors($this->completeData([
            'price' => null,
            'purpose' => null,
            'area' => null,
        ]));

        $this->assertSame([], $errors);
    }

    /** @test */
    public function blank_optional_values_are_treated_as_absent_not_invalid(): void
    {
        // Empty strings survive isset(), so they used to trip the format checks.
        $errors = $this->errors($this->completeData([
            'price' => '',
            'area' => '',
            'purpose' => '',
        ]));

        $this->assertSame([], $errors);
    }

    /** @test */
    public function a_missing_featured_image_blocks_completion(): void
    {
        $errors = $this->errors($this->completeData(['featured_image' => null]));

        $this->assertCount(1, $errors);
        $this->assertSame('missing_fields', $errors[0]['type']);
        $this->assertSame(['featured_image'], $errors[0]['fields']);
    }

    /** @test */
    public function a_missing_property_type_blocks_completion(): void
    {
        $errors = $this->errors($this->completeData(['property_type' => null]));

        $this->assertCount(1, $errors);
        $this->assertSame(['property_type'], $errors[0]['fields']);
    }

    /** @test */
    public function the_legacy_type_key_still_satisfies_property_type(): void
    {
        $data = $this->completeData();
        unset($data['property_type']);
        $data['type'] = 'residential';

        $this->assertSame([], $this->errors($data));
    }

    /** @test */
    public function all_four_canonical_property_types_are_accepted(): void
    {
        foreach (['residential', 'commercial', 'agricultural', 'industrial'] as $type) {
            $errors = $this->errors($this->completeData(['property_type' => $type]));
            $this->assertSame([], $errors, "Type '{$type}' should be accepted");
        }
    }

    /** @test */
    public function an_unknown_property_type_is_rejected(): void
    {
        $errors = $this->errors($this->completeData(['property_type' => 'spaceship']));

        $this->assertCount(1, $errors);
        $this->assertSame('property_type', $errors[0]['field']);
    }

    /** @test */
    public function a_supplied_but_malformed_optional_value_is_still_rejected(): void
    {
        $errors = $this->errors($this->completeData(['price' => 'not-a-number']));

        $this->assertCount(1, $errors);
        $this->assertSame('price', $errors[0]['field']);
    }

    /** @test */
    public function a_blank_required_field_is_reported_once_not_twice(): void
    {
        // '' would fail both the missing check and the length check; only the
        // missing check should fire.
        $errors = $this->errors($this->completeData(['title' => '   ']));

        $this->assertCount(1, $errors);
        $this->assertSame('missing_fields', $errors[0]['type']);
        $this->assertSame(['title'], $errors[0]['fields']);
    }

    /** @test */
    public function arabic_titles_are_measured_in_characters_not_bytes(): void
    {
        // 200 Arabic chars = 400 bytes; strlen() would have rejected this as >255.
        $errors = $this->errors($this->completeData(['title' => str_repeat('ش', 200)]));

        $this->assertSame([], $errors);
    }

    /** @test */
    public function missing_fields_are_reported_as_canonical_english_keys(): void
    {
        $this->assertSame(
            ['title', 'address', 'description', 'featured_image', 'property_type'],
            PropertyCompletionRequirements::missingFrom([])
        );
    }
}
