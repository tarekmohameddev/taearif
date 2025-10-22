<?php

namespace App\Repositories;

use App\Models\User\RealestateManagement\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PropertyRepository
{
    public function findById(int $id): ?Property
    {
        return Property::with(['contents', 'UserPropertyCharacteristics'])->find($id);
    }

    /**
     * Basic candidate query builder for pre-filtering; consumers can extend.
     */
    public function baseQuery()
    {
        return Property::query()->where('status', 1);
    }
}



