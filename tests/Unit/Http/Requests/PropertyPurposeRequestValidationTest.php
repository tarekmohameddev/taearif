<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Api\Property\StorePropertyRequest;
use App\Http\Requests\Api\Property\UpdatePropertyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PropertyPurposeRequestValidationTest extends TestCase
{
    /**
     * @dataProvider requestAndAcceptedPurposeProvider
     *
     * @param  class-string<StorePropertyRequest|UpdatePropertyRequest>  $requestClass
     */
    public function test_property_requests_accept_supported_purpose_values(string $requestClass, string $purpose): void
    {
        $request = $requestClass::create('/', 'POST', ['purpose' => $purpose]);
        $rules = $request->rules();

        $validator = Validator::make(
            ['purpose' => $purpose],
            ['purpose' => $rules['purpose']]
        );

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray()));
    }

    /**
     * @dataProvider requestProvider
     *
     * @param  class-string<StorePropertyRequest|UpdatePropertyRequest>  $requestClass
     */
    public function test_property_requests_reject_unsupported_purpose_value(string $requestClass): void
    {
        $request = $requestClass::create('/', 'POST', ['purpose' => 'lease']);
        $rules = $request->rules();

        $validator = Validator::make(
            ['purpose' => 'lease'],
            ['purpose' => $rules['purpose']]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('purpose', $validator->errors()->toArray());
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function requestAndAcceptedPurposeProvider(): array
    {
        $cases = [];

        foreach ([StorePropertyRequest::class, UpdatePropertyRequest::class] as $requestClass) {
            $requestName = class_basename($requestClass);
            foreach (['sale', 'rent', 'sold', 'rented'] as $purpose) {
                $cases[$requestName . '-' . $purpose] = [$requestClass, $purpose];
            }
        }

        return $cases;
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function requestProvider(): array
    {
        return [
            'store' => [StorePropertyRequest::class],
            'update' => [UpdatePropertyRequest::class],
        ];
    }
}
