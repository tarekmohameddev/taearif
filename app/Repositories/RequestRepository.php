<?php

namespace App\Repositories;

use App\Models\Api\UserPropertyRequest;
use App\Models\Api\ApiCustomerInquiry;
use App\Support\DTO\UnifiedRequest;

class RequestRepository
{
    public function getUnified(string $source, int $id): ?UnifiedRequest
    {
        if ($source === 'web') {
            $row = UserPropertyRequest::find($id);
            if (!$row) return null;
            $u = new UnifiedRequest('web', $row->id);
            $u->userId = $row->user_id;
            $u->phone = $row->phone ?? null;
            $u->customerName = $row->full_name ?? null;
            $u->propertyType = $row->property_type;
            $u->categoryId = $row->category_id;
            $u->region = $row->region;
            $u->cityId = $row->city_id;
            $u->districtId = $row->districts_id;
            $u->budgetFrom = $row->budget_from;
            $u->budgetTo = $row->budget_to;
            $u->purchaseMethod = $row->purchase_method;
            $u->areaFrom = $row->area_from;
            $u->areaTo = $row->area_to;
            $u->seriousness = $row->seriousness;
            $u->notes = $row->notes;
            return $u;
        }

        if ($source === 'whatsapp') {
            $row = ApiCustomerInquiry::find($id);
            if (!$row) return null;
            $u = new UnifiedRequest('whatsapp', $row->id);
            $u->userId = $row->user_id;
            $u->phone = $row->phone_number ?? null;
            $u->customerName = null;
            $u->propertyType = $row->property_type;
            $u->bedrooms = $row->bedrooms;
            $u->bathrooms = $row->bathrooms;
            $u->budget = $row->budget;
            $u->currency = $row->currency;
            $u->minAreaSqm = $row->min_area_sqm;
            $u->maxAreaSqm = $row->max_area_sqm;
            $u->cityName = $row->city;
            $u->districtName = $row->district;
            $u->latitude = $row->latitude;
            $u->longitude = $row->longitude;
            $u->furnished = $row->furnished;
            $u->urgency = $row->urgency;
            $u->message = $row->message;
            $u->lang = $row->lang;
            return $u;
        }

        return null;
    }
}




