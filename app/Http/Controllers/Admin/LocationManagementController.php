<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User\Region;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationManagementController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = 25;
        $cityPage = max(1, (int) $request->query('city_page', 1));

        $cityGroupsQuery = DB::table('user_districts')->whereNotNull('city_id');

        if ($request->filled('city_search')) {
            $term = $request->query('city_search');
            $cityGroupsQuery->where(function ($q) use ($term) {
                $q->where('city_name_ar', 'like', '%' . $term . '%')
                    ->orWhere('city_name_en', 'like', '%' . $term . '%')
                    ->orWhere('country_name_ar', 'like', '%' . $term . '%')
                    ->orWhere('country_name_en', 'like', '%' . $term . '%');
                if (ctype_digit((string) $term)) {
                    $q->orWhere('city_id', (int) $term);
                }
            });
        }

        $totalCities = (int) (clone $cityGroupsQuery)
            ->selectRaw('COUNT(DISTINCT city_id) as aggregate')
            ->value('aggregate');

        $cityGroups = (clone $cityGroupsQuery)
            ->selectRaw('
                city_id,
                MAX(city_name_ar) as city_name_ar,
                MAX(city_name_en) as city_name_en,
                MAX(country_name_ar) as country_name_ar,
                MAX(country_name_en) as country_name_en,
                COUNT(*) as districts_count
            ')
            ->groupBy('city_id')
            ->orderByRaw('MAX(city_name_ar)')
            ->offset(($cityPage - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $cityIdsOnPage = $cityGroups->pluck('city_id')->all();
        $syncedCityIds = DB::table('user_cities')
            ->whereIn('id', $cityIdsOnPage)
            ->pluck('id')
            ->flip()
            ->all();

        $districtPreviewByCityId = [];
        if (!empty($cityIdsOnPage)) {
            $districtRows = DB::table('user_districts')
                ->whereIn('city_id', $cityIdsOnPage)
                ->select('city_id', 'name_ar', 'name_en')
                ->orderBy('city_id')
                ->orderBy('name_ar')
                ->get();

            foreach ($districtRows as $dr) {
                $cid = (int) $dr->city_id;
                if (!isset($districtPreviewByCityId[$cid])) {
                    $districtPreviewByCityId[$cid] = [];
                }
                if (count($districtPreviewByCityId[$cid]) < 3) {
                    $districtPreviewByCityId[$cid][] = [
                        'name_ar' => $dr->name_ar,
                        'name_en' => $dr->name_en,
                    ];
                }
            }
        }

        foreach ($cityGroups as $row) {
            $row->in_user_cities = isset($syncedCityIds[$row->city_id]);
            $row->district_preview = $districtPreviewByCityId[(int) $row->city_id] ?? [];
        }

        $citiesPaginator = new LengthAwarePaginator(
            $cityGroups,
            $totalCities,
            $perPage,
            $cityPage,
            [
                'path' => $request->url(),
                'pageName' => 'city_page',
            ]
        );
        $citiesPaginator->withQueryString();

        $districtQuery = UserDistrict::query()->orderBy('city_id')->orderBy('name_ar');

        if ($request->filled('filter_city_id')) {
            $districtQuery->where('city_id', (int) $request->query('filter_city_id'));
        }

        if ($request->filled('district_search')) {
            $term = $request->query('district_search');
            $districtQuery->where(function ($q) use ($term) {
                $q->where('name_ar', 'like', '%' . $term . '%')
                    ->orWhere('name_en', 'like', '%' . $term . '%');
            });
        }

        $districts = $districtQuery->paginate(30)->withQueryString();

        $cityOptions = DB::table('user_districts')
            ->whereNotNull('city_id')
            ->selectRaw('city_id, MAX(city_name_ar) as city_name_ar, MAX(city_name_en) as city_name_en')
            ->groupBy('city_id')
            ->orderByRaw('MAX(city_name_ar)')
            ->limit(500)
            ->get();

        $regions = Region::query()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);

        $countries = collect();
        if (Schema::hasTable('user_countries')) {
            $countries = DB::table('user_countries')
                ->select('id', 'name')
                ->orderBy('id')
                ->limit(1000)
                ->get();
        }

        return view('admin.location-management.index', [
            'citiesPaginator' => $citiesPaginator,
            'districts' => $districts,
            'cityOptions' => $cityOptions,
            'regions' => $regions,
            'countries' => $countries,
        ]);
    }

    public function storeCity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'form_context' => ['nullable', 'string', 'max:32'],
            'city_id' => [
                'nullable',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    if (DB::table('user_districts')->where('city_id', (int) $value)->exists()) {
                        $fail(__('This city ID is already in use in user_districts.'));
                    }
                },
            ],
            'city_name_ar' => ['required', 'string', 'max:255'],
            'city_name_en' => ['required', 'string', 'max:255'],
            'country_name_ar' => ['required', 'string', 'max:255'],
            'country_name_en' => ['required', 'string', 'max:255'],
            'district_name_ar' => ['required', 'string', 'max:255'],
            'district_name_en' => ['required', 'string', 'max:255'],
        ]);

        if ($this->cityGroupNameExists(
            $validated['country_name_ar'],
            $validated['country_name_en'],
            $validated['city_name_ar'],
            $validated['city_name_en']
        )) {
            return back()->withInput()->withErrors([
                'city_name_ar' => __('A city with the same names already exists for this country snapshot.'),
            ]);
        }

        $cityId = isset($validated['city_id']) && $validated['city_id'] !== null
            ? (int) $validated['city_id']
            : $this->nextAvailableCityId();

        if (UserDistrict::where('city_id', $cityId)->where('name_ar', $validated['district_name_ar'])->exists()) {
            return back()->withInput()->withErrors([
                'district_name_ar' => __('A district with this Arabic name already exists in this city.'),
            ]);
        }

        UserDistrict::create([
            'name_ar' => $validated['district_name_ar'],
            'name_en' => $validated['district_name_en'],
            'city_id' => $cityId,
            'city_name_ar' => $validated['city_name_ar'],
            'city_name_en' => $validated['city_name_en'],
            'country_name_ar' => $validated['country_name_ar'],
            'country_name_en' => $validated['country_name_en'],
        ]);

        Session::flash('success', __('City and first district created successfully.'));

        return back();
    }

    public function updateCity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'form_context' => ['nullable', 'string', 'max:32'],
            'city_id' => ['required', 'integer', 'min:1', Rule::exists('user_districts', 'city_id')],
            'city_name_ar' => ['required', 'string', 'max:255'],
            'city_name_en' => ['required', 'string', 'max:255'],
            'country_name_ar' => ['required', 'string', 'max:255'],
            'country_name_en' => ['required', 'string', 'max:255'],
        ]);

        $cityId = (int) $validated['city_id'];

        if ($this->cityGroupNameExists(
            $validated['country_name_ar'],
            $validated['country_name_en'],
            $validated['city_name_ar'],
            $validated['city_name_en'],
            $cityId
        )) {
            return back()->withInput()->withErrors([
                'city_name_ar' => __('Another city group already uses this country and city name combination.'),
            ]);
        }

        DB::transaction(function () use ($validated, $cityId): void {
            UserDistrict::where('city_id', $cityId)->update([
                'city_name_ar' => $validated['city_name_ar'],
                'city_name_en' => $validated['city_name_en'],
                'country_name_ar' => $validated['country_name_ar'],
                'country_name_en' => $validated['country_name_en'],
            ]);

            if (UserCity::where('id', $cityId)->exists()) {
                UserCity::where('id', $cityId)->update([
                    'name_ar' => $validated['city_name_ar'],
                    'name_en' => $validated['city_name_en'],
                ]);
            }
        });

        Session::flash('success', __('City updated for all related districts.'));

        return back();
    }

    public function syncCity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'form_context' => ['nullable', 'string', 'max:32'],
            'city_id' => ['required', 'integer', 'min:1', Rule::exists('user_districts', 'city_id')],
            'country_id' => ['required', 'integer', 'min:1'],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
            'latitude' => ['nullable', 'string', 'max:255'],
            'longitude' => ['nullable', 'string', 'max:255'],
        ]);

        $cityId = (int) $validated['city_id'];

        $sample = UserDistrict::where('city_id', $cityId)->firstOrFail();

        UserCity::updateOrCreate(
            ['id' => $cityId],
            [
                'name_ar' => $sample->city_name_ar,
                'name_en' => $sample->city_name_en,
                'country_id' => (int) $validated['country_id'],
                'region_id' => (int) $validated['region_id'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]
        );

        Session::flash('success', __('City synced to user_cities successfully.'));

        return back();
    }

    public function storeDistrict(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'form_context' => ['nullable', 'string', 'max:32'],
            'city_id' => ['required', 'integer', 'min:1', Rule::exists('user_districts', 'city_id')],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
        ]);

        $cityId = (int) $validated['city_id'];

        $sample = UserDistrict::where('city_id', $cityId)->firstOrFail();

        if (UserDistrict::where('city_id', $cityId)->where('name_ar', $validated['name_ar'])->exists()) {
            return back()->withInput()->withErrors([
                'name_ar' => __('A district with this Arabic name already exists in this city.'),
            ]);
        }

        UserDistrict::create([
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'],
            'city_id' => $cityId,
            'city_name_ar' => $sample->city_name_ar,
            'city_name_en' => $sample->city_name_en,
            'country_name_ar' => $sample->country_name_ar,
            'country_name_en' => $sample->country_name_en,
        ]);

        Session::flash('success', __('District added successfully.'));

        return back();
    }

    public function updateDistrict(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'form_context' => ['nullable', 'string', 'max:32'],
            'district_id' => ['required', 'integer', Rule::exists('user_districts', 'id')],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
        ]);

        $district = UserDistrict::findOrFail((int) $validated['district_id']);

        if (UserDistrict::where('city_id', $district->city_id)
            ->where('name_ar', $validated['name_ar'])
            ->where('id', '!=', $district->id)
            ->exists()) {
            return back()->withInput()->withErrors([
                'name_ar' => __('Another district in this city already uses this Arabic name.'),
            ]);
        }

        $district->update([
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'],
        ]);

        Session::flash('success', __('District updated successfully.'));

        return back();
    }

    private function nextAvailableCityId(): int
    {
        $maxDistrictCity = (int) DB::table('user_districts')->max('city_id');
        $maxCityId = (int) DB::table('user_cities')->max('id');

        return max($maxDistrictCity, $maxCityId) + 1;
    }

    private function cityGroupNameExists(
        string $countryAr,
        string $countryEn,
        string $cityAr,
        string $cityEn,
        ?int $exceptCityId = null
    ): bool {
        $q = DB::table('user_districts')
            ->where('country_name_ar', $countryAr)
            ->where('country_name_en', $countryEn)
            ->where('city_name_ar', $cityAr)
            ->where('city_name_en', $cityEn)
            ->when($exceptCityId !== null, fn ($sub) => $sub->where('city_id', '!=', $exceptCityId));

        return $q->exists();
    }
}
