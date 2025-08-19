<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User\UserDistrict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\User\UserCity;

class UserDistrictController extends Controller
{
    /**
     * Return distinct cities from user_districts (for the City dropdown)
     */
    public function cities()
    {
        $rows = UserCity::query()
            ->select('id', 'name_ar', 'name_en')
            // ->orderBy(App::getLocale() === 'ar' ? 'name_ar' : 'name_en')
            ->get();

        return response()->json(
            $rows->map(fn ($c) => [
                'id'      => (int) $c->id,
                'name_ar' => $c->name_ar,
                'name_en' => $c->name_en,
            ])
        );
    }


    /**
     * Return all districts within a city (for the District dropdown)
     */
    public function districtsByCity($cityId)
    {
        $city = UserCity::with('districts')->findOrFail($cityId);

        return response()->json(
            $city->districts->map(fn ($d) => [
                'id'       => (int) $d->id,
                'city_id'  => (int) $d->city_id,
                'name_ar'  => $d->name_ar,
                'name_en'  => $d->name_en,
            ])
        );
    }


    /**
     * Optional: searchable/paginatable districts list
     */
    public function index(Request $request)
    {
        $request->validate([
            'q'        => 'nullable|string|max:255',
            'city_id'  => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $q = UserDistrict::with('city');

        if ($request->filled('city_id')) {
            $q->where('city_id', (int) $request->city_id);
        }

        if ($term = trim((string) $request->q)) {
            $like = "%{$term}%";
            $q->where(function ($s) use ($like) {
                $s->where('name_ar', 'like', $like)
                  ->orWhere('name_en', 'like', $like)
                  ->orWhereHas('city', function ($c) use ($like) {
                      $c->where('name_ar', 'like', $like)
                        ->orWhere('name_en', 'like', $like);
                  });
            });
        }


        $q->orderBy(App::getLocale() === 'ar' ? 'name_ar' : 'name_en');
        $perPage = (int) ($request->per_page ?: 20);
        $p = $q->paginate($perPage);

        $items = $p->getCollection()->map(fn ($r) => [
            'id'           => (int) $r->id,
            'name_ar'      => $r->name_ar,
            'name_en'      => $r->name_en,
            'city_id'      => (int) $r->city_id,
            'city_name_ar' => $r->city?->name_ar,
            'city_name_en' => $r->city?->name_en,
        ])->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'districts'  => $items,
                'pagination' => [
                    'total'        => $p->total(),
                    'per_page'     => $p->perPage(),
                    'current_page' => $p->currentPage(),
                    'last_page'    => $p->lastPage(),
                    'from'         => $p->firstItem(),
                    'to'           => $p->lastItem(),
                ],
            ],
        ]);
    }
}
