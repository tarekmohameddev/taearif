<?php

namespace App\Support\DTO;

class UnifiedRequest
{
    /** web | whatsapp */
    public string $source;
    public int $id;
    public ?int $userId = null;

    // Core needs
    public ?string $propertyType = null;      // e.g., villa/apartment
    public ?int $categoryId = null;           // internal category
    public ?string $purpose = null;           // buy/rent if available

    // Location
    public ?string $region = null;            // free text (web)
    public ?int $regionId = null;             // from properties
    public ?int $cityId = null;
    public ?int $districtId = null;
    public ?string $cityName = null;
    public ?string $districtName = null;
    public ?float $latitude = null;
    public ?float $longitude = null;

    // Budget
    public ?float $budgetFrom = null;
    public ?float $budgetTo = null;
    public ?float $budget = null;             // whatsapp single value
    public ?string $currency = null;          // SAR, USD, etc.
    public ?string $purchaseMethod = null;    // cash/mortgage (web)

    // Size
    public ?int $areaFrom = null;
    public ?int $areaTo = null;
    public ?int $minAreaSqm = null;           // whatsapp
    public ?int $maxAreaSqm = null;

    // Features
    public ?int $bedrooms = null;
    public ?int $bathrooms = null;
    public ?bool $furnished = null;

    // Priority / meta
    public ?string $seriousness = null;       // web
    public ?string $urgency = null;           // whatsapp
    public ?string $lang = null;              // ar/en if present

    // Free text
    public ?string $notes = null;             // web notes
    public ?string $message = null;           // whatsapp message

    public function __construct(string $source, int $id)
    {
        $this->source = $source;
        $this->id = $id;
    }
}



