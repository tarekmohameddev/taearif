<?php

namespace App\Services\Matching;

use App\Repositories\PropertyRepository;
use App\Support\DTO\UnifiedRequest;
use Illuminate\Database\Eloquent\Builder;

class PropertySearchService
{
    public function __construct(private PropertyRepository $properties)
    {
    }

    /**
     * Build a pre-filtered query for candidate properties based on hard constraints.
     */
    public function buildCandidatesQuery(UnifiedRequest $request): Builder
    {
        $q = $this->properties->baseQuery();

        // Scope to the same owner/tenant
        if ($request->userId) {
            $q->where('user_id', $request->userId);
        }

        // Location: region/city/district
        if ($request->regionId) {
            $q->where('region_id', $request->regionId);
        }
        if ($request->cityId) {
            $q->whereHas('contents', function ($qq) use ($request) {
                $qq->where('city_id', $request->cityId);
            });
        }
        // District: stored as state_id on property_contents
        if ($request->districtId) {
            $q->whereHas('contents', function ($qq) use ($request) {
                $qq->where('state_id', $request->districtId);
            });
        }

        // Type/category/purpose
        if ($request->categoryId) {
            $q->where('category_id', $request->categoryId);
        }
        if ($request->propertyType) {
            $q->where('property_type', $request->propertyType);
        }
        if ($request->purpose) {
            $q->where('purpose', $request->purpose);
        }

        // Bedrooms/Bathrooms
        if ($request->bedrooms) {
            $q->where('beds', '>=', $request->bedrooms);
        }
        if ($request->bathrooms) {
            $q->where('bath', '>=', $request->bathrooms);
        }

        // Size/area
        if ($request->areaFrom) {
            $q->where('area', '>=', $request->areaFrom);
        }
        if ($request->areaTo) {
            $q->where('area', '<=', $request->areaTo);
        }
        if ($request->minAreaSqm) {
            $q->where('area', '>=', $request->minAreaSqm);
        }
        if ($request->maxAreaSqm) {
            $q->where('area', '<=', $request->maxAreaSqm);
        }

        // Budget: web uses range; whatsapp may be single value
        if ($request->budgetFrom) {
            $q->where('price', '>=', $request->budgetFrom);
        }
        if ($request->budgetTo) {
            $q->where('price', '<=', $request->budgetTo);
        }
        if ($request->budget && !$request->budgetFrom && !$request->budgetTo) {
            $q->whereBetween('price', [$request->budget * 0.9, $request->budget * 1.1]);
        }

        // Only available
        $q->where(function ($qq) {
            $qq->whereNull('property_status')->orWhere('property_status', 'available');
        });

        return $q;
    }

    /**
     * Build a text-based query for properties when structured location data is unavailable.
     * Searches property content (title, address) using keywords from the inquiry message.
     */
    public function buildTextBasedQuery(UnifiedRequest $request): Builder
    {
        $q = $this->properties->baseQuery();

        // Scope to the same owner/tenant
        if ($request->userId) {
            $q->where('user_id', $request->userId);
        }

        // Extract location keywords from message
        $searchText = $this->extractLocationKeywords($request);
        
        \Log::info('PropertySearchService: text-based search keywords', [
            'request_id' => $request->id,
            'search_text' => $searchText,
            'has_message' => !empty($request->message),
            'city_name' => $request->cityName,
            'district_name' => $request->districtName,
        ]);
        
        if (!empty($searchText)) {
            // Normalize Arabic text for better matching
            $normalized = $this->normalizeArabicText($searchText);
            
            \Log::info('PropertySearchService: normalized search text', [
                'request_id' => $request->id,
                'original' => $searchText,
                'normalized' => $normalized,
            ]);
            
            // Search in property content (title and address)

            $q->whereHas('contents', function ($qq) use ($normalized, $searchText) {
                $qq->where(function ($qqq) use ($normalized, $searchText) {
                    // Try normalized text first
                    $qqq->where('title', 'like', "%{$normalized}%")
                        ->orWhere('address', 'like', "%{$normalized}%");
                    
                    // Also try original text in case normalization changed meaning
                    if ($normalized !== $searchText) {
                        $qqq->orWhere('title', 'like', "%{$searchText}%")
                            ->orWhere('address', 'like', "%{$searchText}%");
                    }
                });
            });
        }

        // Property type filter is intentionally more lenient in text-based fallback
        // We prioritize location match over exact type match since the user
        // may use colloquial terms (e.g., "apartment") that don't match database values
        // The AI scorer will handle type compatibility in the scoring phase
        // if ($request->propertyType) {
        //     $q->where('type', $request->propertyType);
        // }

        // Apply purpose filter if available (buy/rent is usually clear)
        if ($request->purpose) {

            $q->where('purpose', $request->purpose);
        }

        // Apply bedrooms filter if available
        if ($request->bedrooms) {
            $q->where('beds', '>=', $request->bedrooms);
        }

        // Apply bathrooms filter if available
        if ($request->bathrooms) {
            $q->where('bath', '>=', $request->bathrooms);
        }

        // Apply area filters if available
        if ($request->minAreaSqm) {
            $q->where('area', '>=', $request->minAreaSqm);
        }
        if ($request->maxAreaSqm) {
            $q->where('area', '<=', $request->maxAreaSqm);
        }

        // Apply budget filters if available
        if ($request->budget && !$request->budgetFrom && !$request->budgetTo) {
            $q->whereBetween('price', [$request->budget * 0.9, $request->budget * 1.1]);
        }

        // Only available properties
        $q->where(function ($qq) {
            $qq->whereNull('property_status')->orWhere('property_status', 'available');
        });

        return $q;
    }

    /**
     * Extract location keywords from the unified request.
     * Priority: specific locations in message > city/district names
     */
    private function extractLocationKeywords(UnifiedRequest $request): string
    {
        // Priority 1: Parse message for specific location markers (streets, neighborhoods)
        // These are more specific than city names and should be tried first
        if ($request->source === 'whatsapp' && !empty($request->message)) {
            $message = $request->message;
            
            // Street and neighborhood markers are most specific
            $specificMarkers = ['شارع', 'حي', 'منطقة'];
            
            foreach ($specificMarkers as $marker) {
                $pos = mb_strpos($message, $marker);
                if ($pos !== false) {
                    // Extract text after the marker using mb_substr for proper Arabic handling
                    $afterMarker = mb_substr($message, $pos + mb_strlen($marker));
                    $afterMarker = trim($afterMarker);
                    
                    if (!empty($afterMarker)) {
                        // Take multiple words (street names can be 2-4 words)
                        // Split on Arabic and Latin punctuation
                        $words = preg_split('/[\s،؛.]+/u', $afterMarker, 5);
                        
                        // For street names, take 2-4 words typically
                        $extracted = [];
                        for ($i = 0; $i < min(4, count($words)); $i++) {
                            $word = trim($words[$i]);
                            if (mb_strlen($word) > 1) {
                                $extracted[] = $word;
                            }
                        }
                        
                        if (!empty($extracted)) {
                            $fullLocation = implode(' ', $extracted);
                            \Log::info('PropertySearchService: extracted specific location', [
                                'marker' => $marker,
                                'extracted' => $fullLocation,
                            ]);
                            return $fullLocation;
                        }
                    }
                }
            }
            
            // Try general location markers
            $generalMarkers = ['في', 'بـ', 'ب'];
            foreach ($generalMarkers as $marker) {
                $pos = mb_strpos($message, $marker);
                if ($pos !== false) {
                    $afterMarker = mb_substr($message, $pos + mb_strlen($marker));
                    $afterMarker = trim($afterMarker);
                    
                    if (!empty($afterMarker)) {
                        $words = preg_split('/[\s،؛.]+/u', $afterMarker, 5);
                        
                        if (!empty($words) && mb_strlen(trim($words[0])) > 2) {
                            // Look ahead to see if there's a street/neighborhood marker
                            $lookahead = mb_substr($afterMarker, 0, min(50, mb_strlen($afterMarker)));
                            foreach ($specificMarkers as $specificMarker) {
                                if (mb_strpos($lookahead, $specificMarker) !== false) {
                                    // Skip this general marker, let specific marker handle it
                                    continue 2;
                                }
                            }
                            // No specific marker found, use what we have
                            return trim($words[0]);
                        }
                    }
                }
            }
        }
        
        // Priority 2: Use district name if available (more specific than city)
        if (!empty($request->districtName)) {
            return $request->districtName;
        }
        
        // Priority 3: Use city name as last resort
        if (!empty($request->cityName)) {
            return $request->cityName;
        }

        // For web requests, use region or notes
        if ($request->region) {
            return $request->region;
        }

        return '';
    }



    /**
     * Normalize Arabic text for better matching.
     * Based on the normalization logic used in ChatController.
     */
    private function normalizeArabicText(string $text): string
    {
        $replacements = [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ى' => 'ي',
            'ئ' => 'ي',
            'ؤ' => 'و',
            'ة' => 'ه',
            'ٱ' => 'ا',
            'گ' => 'ك',
            'چ' => 'ج',
            'ژ' => 'ز',
            'ڤ' => 'ف',
            'پ' => 'ب',
        ];
        
        return strtr($text, $replacements);
    }

    /**
     * Build a query safe for bot use: applies buildCandidatesQuery plus the canonical
     * bot availability scope. Always use this method from bot tools, never the raw query.
     */
    public function buildBotQuery(UnifiedRequest $request): Builder
    {
        $q = $this->buildCandidatesQuery($request);
        // Apply canonical availability scope
        $q->where('status', 1)
            ->where(function ($qq) {
                $qq->whereNull('publish_status')->orWhere('publish_status', 'published');
            })
            ->where(function ($qq) {
                $qq->whereNull('property_status')->orWhere('property_status', 'available');
            })
            ->where(function ($qq) {
                $qq->whereNull('unit_status')->orWhere('unit_status', 'available');
            })
            ->where(function ($qq) {
                $qq->whereNull('purpose')->orWhereNotIn('purpose', ['rented', 'sold']);
            });
        return $q;
    }
}



