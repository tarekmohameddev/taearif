<?php

namespace App\Http\Controllers\Api\V1\Crm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Controllers\Api\ApiController;
use App\Models\Api\Crm\CrmRequest;
use App\Models\Api\Crm\CrmCard;
use App\Models\Api\UserApiCustomerStage;
use App\Models\User\RealestateManagement\Property as UserProperty;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\UserPropertyCharacteristic;
use App\Models\User\RealestateManagement\ApiUserCategory;

class CrmRequestController extends ApiController
{
	/**
	 * GET /api/v1/crm/requests
	 */
	public function index(Request $request)
	{
		$user = $request->user();
		$userId = $request->user()->tenantOwnerId();

		// Helper function to convert to int array
		$toIntArray = function ($v): array {
			if (is_null($v) || $v === '') return [];
			if (is_int($v) || (is_string($v) && is_numeric($v))) return [(int)$v];
			if (is_string($v)) return array_values(array_filter(array_map('intval', explode(',', $v))));
			if (is_array($v))  return array_values(array_filter(array_map('intval', $v)));
			return [];
		};
		$catIds  = $toIntArray($request->input('interested_category_ids'));
		$propIds = $toIntArray($request->input('interested_property_ids'));

		// Validate all filters
		$request->validate([
			'q' => 'nullable|string|max:255',
			'customer_id' => 'nullable|integer',
			'name' => 'nullable|string|max:255',
			'email' => 'nullable|string|max:255',
			'phone_number' => 'nullable|string|max:20',
			'city_id' => 'nullable|integer',
			'district_id' => 'nullable|integer',
			'type_id' => 'nullable|integer',
			'priority_id' => 'nullable|integer',
			'procedure_id' => 'nullable|integer',
			'stage_id' => 'nullable|integer',
			'responsible_employee_id' => 'nullable|integer',
			'employee_whatsapp_number' => 'nullable|string|max:20',
			'interested_category_ids' => 'nullable',
			'interested_property_ids' => 'nullable',
			'has_property' => 'nullable|in:0,1',
			'sort_by' => 'nullable|in:position,created_at,id',
			'sort_dir' => 'nullable|in:asc,desc',
		]);

		// Statistics (4 cards) - calculate before filtering
		$totalRequests     = CrmRequest::query()->forUser($userId)->count();
		$withProperty      = CrmRequest::query()->forUser($userId)->whereNotNull('property_id')->count();
		$withoutProperty   = CrmRequest::query()->forUser($userId)->whereNull('property_id')->count();
		$totalCards        = CrmCard::query()->forUser($userId)->count();

		$statistics = [
			'total_requests'   => $totalRequests,
			'with_property'    => $withProperty,
			'without_property' => $withoutProperty,
			'total_cards'      => $totalCards,
		];

		// Build base query with customer filters
		$baseQuery = CrmRequest::query()
			->forUser($userId);

		// Apply direct customer_id filter on requests table (if provided)
		// This is more efficient than using whereHas when filtering by customer_id
		if ($request->filled('customer_id')) {
			$baseQuery->where('customer_id', (int)$request->input('customer_id'));
		}

		// Apply customer filters via whereHas (if any customer-related filters are present)
		// Note: If customer_id is also provided, these filters will work within that constraint
		$hasCustomerFilters = $request->filled('q') || 
			$request->filled('name') || 
			$request->filled('email') || 
			$request->filled('phone_number') || 
			$request->filled('city_id') || 
			$request->filled('district_id') || 
			$request->filled('type_id') || 
			$request->filled('priority_id') || 
			$request->filled('procedure_id') || 
			$request->filled('responsible_employee_id') || 
			$request->filled('employee_whatsapp_number') || 
			!empty($catIds) || 
			!empty($propIds);

		if ($hasCustomerFilters) {
			$baseQuery->whereHas('customer', function ($customerQuery) use ($request, $catIds, $propIds) {
			// General search (q)
			if ($request->filled('q')) {
				$qText = trim($request->input('q'));
				$customerQuery->where(function ($sub) use ($qText) {
					$sub->where('name', 'like', "%{$qText}%")
						->orWhere('email', 'like', "%{$qText}%")
						->orWhere('phone_number', 'like', "%{$qText}%");
				});
			}

			// Specific customer filters
			if ($request->filled('name')) {
				$customerQuery->where('name', 'like', '%' . trim($request->input('name')) . '%');
			}
			if ($request->filled('email')) {
				$customerQuery->where('email', 'like', '%' . trim($request->input('email')) . '%');
			}
			if ($request->filled('phone_number')) {
				$customerQuery->where('phone_number', 'like', '%' . trim($request->input('phone_number')) . '%');
			}
			if ($request->filled('city_id')) {
				$customerQuery->where('city_id', (int)$request->input('city_id'));
			}
			if ($request->filled('district_id')) {
				$customerQuery->where('district_id', (int)$request->input('district_id'));
			}
			if ($request->filled('type_id')) {
				$customerQuery->where('type_id', (int)$request->input('type_id'));
			}
			if ($request->filled('priority_id')) {
				$customerQuery->where('priority_id', (int)$request->input('priority_id'));
			}
			if ($request->filled('procedure_id')) {
				$customerQuery->where('procedure_id', (int)$request->input('procedure_id'));
			}
			if ($request->filled('responsible_employee_id')) {
				$customerQuery->where('responsible_employee_id', (int)$request->input('responsible_employee_id'));
			}
			if ($request->filled('employee_whatsapp_number')) {
				$customerQuery->whereHas('responsibleEmployee.activeWhatsappUser', function ($sub) use ($request) {
					$sub->where('number', 'like', '%' . $request->input('employee_whatsapp_number') . '%');
				});
			}

			// Interested categories filter
			if (!empty($catIds)) {
				$customerQuery->whereExists(function ($sub) use ($catIds) {
					$sub->select(DB::raw(1))
						->from('api_customer_property_interested as ac1')
						->whereColumn('ac1.customer_id', 'api_customers.id')
						->whereIn('ac1.category_id', $catIds);
				});
			}

			// Interested properties filter
			if (!empty($propIds)) {
				$customerQuery->whereExists(function ($sub) use ($propIds) {
					$sub->select(DB::raw(1))
						->from('api_customer_property_interested as ac2')
						->whereColumn('ac2.customer_id', 'api_customers.id')
						->whereIn('ac2.property_id', $propIds);
				});
			}
			});
		}

		// Apply request-level filters
		if ($request->filled('stage_id')) {
			$baseQuery->where('stage_id', (int)$request->input('stage_id'));
		}
		if ($request->filled('has_property')) {
			if ($request->input('has_property') === '1') {
				$baseQuery->whereNotNull('property_id');
			} elseif ($request->input('has_property') === '0') {
				$baseQuery->whereNull('property_id');
			}
		}

		// Get all filtered request IDs first (for grouping by stage)
		$filteredRequestIds = $baseQuery->pluck('id');

		// Get stages with filtered requests
		$stages = UserApiCustomerStage::query()
			->where('user_id', $userId)
			->where('is_active', true)
			->orderBy('order', 'asc')
			->get(['id', 'stage_name', 'color', 'icon', 'order'])
			->map(function ($stage) use ($userId, $filteredRequestIds, $request) {
				$reqQuery = CrmRequest::query()
					->forUser($userId)
					->where('stage_id', $stage->id)
					->whereIn('id', $filteredRequestIds);

				// Apply sorting
				$sortBy = $request->input('sort_by', 'position');
				$sortDir = $request->input('sort_dir', 'asc');
				
				if ($sortBy === 'position') {
					$reqQuery->orderBy('position', $sortDir)->orderByDesc('id');
				} else {
					$reqQuery->orderBy($sortBy, $sortDir);
				}

				$requests = $reqQuery->get();

				// Build property basic info map for this stage to avoid N+1
				$propertyIds = $requests->pluck('property_id')->filter()->unique()->values();
				$properties = collect();
				if ($propertyIds->isNotEmpty()) {
					$properties = UserProperty::with(['contents'])
						->where('user_id', $userId)
						->whereIn('id', $propertyIds)
						->get()
						->keyBy('id');
				}

				$formatBasic = function ($prop) {
					$content = optional($prop->contents)->first();
					return [
						'id' => $prop->id,
						'title' => optional($content)->title ?? '',
						'address' => optional($content)->address ?? '',
						'price' => $prop->price ?? null,
						'pricePerMeter' => $prop->pricePerMeter ?? null,
						'purpose' => $prop->purpose ?? null,
						'type' => $prop->type ?? null,
						'beds' => $prop->beds ?? null,
						'bath' => $prop->bath ?? null,
						'area' => $prop->area ?? null,
						'featured_image' => $prop->featured_image ? asset($prop->featured_image) : null,
					];
				};

				// Preload customers for this batch
				$customerIds = $requests->pluck('customer_id')->filter()->unique()->values();
				$customers = collect();
				if ($customerIds->isNotEmpty()) {
					$customers = \App\Models\ApiCustomer::with('responsibleEmployee.activeWhatsappUser')
						->whereIn('id', $customerIds)
						->get(['id','name','phone_number','email','stage_id','priority_id','type_id', 'responsible_employee_id', 'city_id', 'district_id'])
						->keyBy('id');
				}

				$requests = $requests->map(function ($r) use ($properties, $formatBasic, $customers) {
					$basic = null;
					if (!empty($r->property_id) && $properties->has($r->property_id)) {
						$basic = $formatBasic($properties->get($r->property_id));
					} elseif (!empty($r->property_specifications) && is_array($r->property_specifications)) {
						$spec = $r->property_specifications['basic_information'] ?? null;
						if (is_array($spec)) {
							$basic = [
								'id' => null,
								'title' => null,
								'address' => $spec['address'] ?? null,
								'price' => $spec['price'] ?? null,
								'pricePerMeter' => $spec['price_per_sqm'] ?? null,
								'purpose' => $spec['listing_type'] ?? null,
								'type' => $spec['property_type'] ?? null,
								'beds' => null,
								'bath' => null,
								'area' => $spec['area'] ?? null,
								'featured_image' => null,
							];
						}
					}

					$customer = null;
					if (!empty($r->customer_id) && $customers->has($r->customer_id)) {
						$c = $customers->get($r->customer_id);
						$customer = [
							'id' => $c->id,
							'name' => $c->name,
							'phone_number' => $c->phone_number,
							'email' => $c->email,
							'stage_id' => $c->stage_id,
							'priority_id' => $c->priority_id,
							'type_id' => $c->type_id,
							'responsible_employee' => $c->responsibleEmployee ? [
								'id' => $c->responsibleEmployee->id,
								'name' => trim(($c->responsibleEmployee->first_name ?? '') . ' ' . ($c->responsibleEmployee->last_name ?? '')),
								'email' => $c->responsibleEmployee->email,
								'whatsapp_number' => $c->responsibleEmployee->activeWhatsappUser ? $c->responsibleEmployee->activeWhatsappUser->number : null,
							] : null,
						];
					}

					return array_merge($r->toArray(), [
						'has_property' => (bool) $r->property_id,
						'property_source' => $r->property_id ? 'existing_property' : 'specifications',
						'property_basic' => $basic,
						'customer' => $customer,
					]);
				});

				return [
					'id'         => $stage->id,
					'stage_name' => $stage->stage_name,
					'color'      => $stage->color,
					'icon'       => $stage->icon,
					'order'      => $stage->order,
					'requests'   => $requests,
				];
			})
			->values();

		return $this->success([
			'statistics' => $statistics,
			'stages'     => $stages,
		]);
	}

	/**
	 * POST /api/v1/crm/requests
	 */
	public function store(Request $request)
	{
		$user = $request->user();

		$validator = Validator::make($request->all(), [
			'customer_name'             => ['required', 'string', 'max:255'],
			'customer_phone'            => ['required', 'string', 'max:32'],
			'stage_id'                  => ['nullable', 'integer', 'exists:users_api_customers_stages,id'],
			'property_id'               => ['nullable', 'integer', 'required_without:property_specifications'],
			'property_specifications'   => ['nullable', 'array', 'required_without:property_id'],

			// Nested validation (optional keys)
			'property_specifications.basic_information' => ['nullable', 'array'],
			'property_specifications.basic_information.address' => ['nullable', 'string'],
			'property_specifications.basic_information.building' => ['nullable'],
			'property_specifications.basic_information.price' => ['nullable', 'numeric'],
			'property_specifications.basic_information.payment_method' => ['nullable'],
			'property_specifications.basic_information.price_per_sqm' => ['nullable', 'numeric'],
			'property_specifications.basic_information.listing_type' => ['nullable'],
			'property_specifications.basic_information.property_category' => ['nullable'],
			'property_specifications.basic_information.project' => ['nullable'],
			'property_specifications.basic_information.city' => ['nullable'],
			'property_specifications.basic_information.district' => ['nullable'],
			'property_specifications.basic_information.area' => ['nullable'],
			'property_specifications.basic_information.property_type' => ['nullable'],

			'property_specifications.details' => ['nullable', 'array'],
			'property_specifications.details.features' => ['nullable', 'array'],

			'property_specifications.attributes' => ['nullable', 'array'],
			'property_specifications.attributes.area_sqft' => ['nullable', 'numeric'],
			'property_specifications.attributes.year_built' => ['nullable', 'integer'],

			'property_specifications.facilities' => ['nullable', 'array'],
			'property_specifications.facilities.bedrooms' => ['nullable', 'integer'],
			'property_specifications.facilities.bathrooms' => ['nullable', 'integer'],
			'property_specifications.facilities.rooms' => ['nullable', 'integer'],
			'property_specifications.facilities.floors' => ['nullable', 'integer'],
			'property_specifications.facilities.floor_number' => ['nullable', 'integer'],
			'property_specifications.facilities.drivers_room' => ['nullable', 'boolean'],
			'property_specifications.facilities.maids_room' => ['nullable', 'boolean'],
			'property_specifications.facilities.dining_room' => ['nullable', 'boolean'],
			'property_specifications.facilities.living_room' => ['nullable', 'boolean'],
			'property_specifications.facilities.majlis' => ['nullable', 'boolean'],
			'property_specifications.facilities.storage_room' => ['nullable', 'boolean'],
			'property_specifications.facilities.basement' => ['nullable', 'boolean'],
			'property_specifications.facilities.swimming_pool' => ['nullable', 'boolean'],
			'property_specifications.facilities.kitchen' => ['nullable', 'boolean'],
			'property_specifications.facilities.balcony' => ['nullable', 'boolean'],
			'property_specifications.facilities.garden' => ['nullable', 'boolean'],
			'property_specifications.facilities.annex' => ['nullable', 'boolean'],
			'property_specifications.facilities.elevator' => ['nullable', 'boolean'],
			'property_specifications.facilities.parking_space' => ['nullable', 'integer'],
		]);

		$validator->after(function ($v) use ($request) {
			if ($request->filled('property_id') && $request->filled('property_specifications')) {
				$v->errors()->add('property_specifications', 'property_id and property_specifications cannot be used together.');
			}
		});

		$validated = $validator->validate();

		// Find or create customer for this tenant by phone
		$customer = \App\Models\ApiCustomer::firstOrCreate(
			[
				'user_id' => $user->id,
				'phone_number' => $validated['customer_phone'],
			],
			[
				'name' => $validated['customer_name'],
				'password' => bcrypt('12345678'),
				'remember_token' => Str::random(60),
			]
		);
		// If exists but name is different and non-empty, update name
		if (!empty($validated['customer_name']) && $customer->name !== $validated['customer_name']) {
			$customer->name = $validated['customer_name'];
			$customer->save();
		}

		$data = $validated;
		unset($data['customer_name'], $data['customer_phone']);
		$data['user_id'] = $user->id;
		$data['customer_id'] = $customer->id;

		// If stage provided, compute position at end of that stage for this user
		if (!empty($data['stage_id'])) {
			$max = CrmRequest::query()
				->forUser($user->id)
				->where('stage_id', $data['stage_id'])
				->max('position');
			$data['position'] = is_null($max) ? 1 : ($max + 1);
		} else {
			$data['position'] = 0;
		}

		$requestModel = CrmRequest::create($data);

		return response()->json(['status' => true, 'data' => $requestModel], 201);
	}

	/**
	 * GET /api/v1/crm/requests/{id}
	 */
	public function show(Request $request, int $id)
	{
		$model = CrmRequest::forUser($request->user()->id)->findOrFail($id);
		return $this->success(['request' => $model]);
	}

	/**
	 * GET /api/v1/crm/requests/{id}/details
	 * Returns the request plus its cards (updates).
	 */
	public function details(Request $request, int $id)
	{
		$userId = $request->user()->id;

		$model = CrmRequest::forUser($userId)->findOrFail($id);

		$customer = null;
		if (!empty($model->customer_id)) {
			$c = \App\Models\ApiCustomer::with('responsibleEmployee.activeWhatsappUser')->find($model->customer_id);
			if ($c) {
				$customer = [
					'id' => $c->id,
					'name' => $c->name,
					'phone_number' => $c->phone_number,
					'stage_id' => $c->stage_id,
					'priority_id' => $c->priority_id,
					'type_id' => $c->type_id,
					'responsible_employee' => $c->responsibleEmployee ? [
						'id' => $c->responsibleEmployee->id,
						'name' => trim(($c->responsibleEmployee->first_name ?? '') . ' ' . ($c->responsibleEmployee->last_name ?? '')),
						'email' => $c->responsibleEmployee->email,
						'whatsapp_number' => $c->responsibleEmployee->activeWhatsappUser ? $c->responsibleEmployee->activeWhatsappUser->number : null,
					] : null,
				];
			}
		}

		$cards = CrmCard::query()
			->forUser($userId)
			->where('card_request_id', $model->id)
			->orderByDesc('card_date')
			->orderByDesc('id')
			->get();

		$property = null;
		if (!empty($model->property_id)) {
			$prop = UserProperty::with([
				'category',
				'user',
				'contents',
				'galleryImages',
				'proertyAmenities.amenity',
				'UserPropertyCharacteristics',
			])
				->where('id', $model->property_id)
				->where('user_id', $userId)
				->first();

			if ($prop) {
				$content = $prop->contents->first();
				$characteristics = optional($prop->UserPropertyCharacteristics)->toArray() ?? [];

				$property = array_merge([
					'id' => $prop->id,
					'project_id' => $prop->project_id,
					'payment_method' => $prop->payment_method,
					'title' => optional($content)->title ?? '',
					'address' => optional($content)->address ?? '',
					'price' => $prop->price ?? '0.00',
					'pricePerMeter' => $prop->pricePerMeter,
					'purpose' => $prop->purpose,
					'type' => $prop->type ?? '',
					'beds' => $prop->beds,
					'bath' => $prop->bath,
					'area' => $prop->area,
					'features' => $prop->features ?? [],
					'status' => (int) $prop->status,
					'featured_image' => $prop->featured_image ? asset($prop->featured_image) : null,
					'floor_planning_image' => collect($prop->floor_planning_image)->map(fn($img) => asset($img))->toArray(),
					'gallery' => $prop->galleryImages->pluck('image')->map(fn($image) => asset($image))->toArray(),
					'description' => optional($content)->description ?? '',
					'latitude' => $prop->latitude ? (float) $prop->latitude : null,
					'longitude' => $prop->longitude ? (float) $prop->longitude : null,
					'featured' => (bool) $prop->featured,
					'city_id' => optional($content)->city_id,
					'state_id' => optional($content)->state_id,
					'video_url' => $prop->video_url ? asset($prop->video_url) : null,
					'virtual_tour' => $prop->virtual_tour ? asset($prop->virtual_tour) : null,
					'video_image' => $prop->video_image ? asset($prop->video_image) : null,
					'category_id' => $prop->category_id,
					'size' => $prop->size ?? null,
					'faqs' => $prop->faqs ?? [],
					'building' => $prop->building,
					'water_meter_number' => $prop->water_meter_number,
					'electricity_meter_number' => $prop->electricity_meter_number,
					'deed_number' => $prop->deed_number ? asset($prop->deed_number) : null,
					'created_at' => $prop->created_at?->toISOString(),
					'updated_at' => $prop->updated_at?->toISOString(),
				], $characteristics);
			}
		}

		// Get stage information if stage_id exists
		$stage = null;
		if (!empty($model->stage_id)) {
			$stageModel = UserApiCustomerStage::where('id', $model->stage_id)
				->where('user_id', $userId)
				->first(['id', 'stage_name', 'color']);
			
			if ($stageModel) {
				$stage = [
					'id' => $stageModel->id,
					'name' => $stageModel->stage_name,
					'color' => $stageModel->color,
				];
			}
		}

		// Convert model to array and replace stage_id with stage object
		$requestData = $model->toArray();
		unset($requestData['stage_id']);
		$requestData['stage'] = $stage;

		$payload = [
			'request' => $requestData,
			'customer' => $customer,
			'cards'   => $cards,
			'property_source' => $model->property_id ? 'existing_property' : 'specifications',
		];
		if ($property) {
			$payload['property'] = $property;
		} else {
			$payload['property_specifications'] = $model->property_specifications;
		}

		return $this->success($payload);
	}

	/**
	 * PATCH /api/v1/crm/requests/{id}
	 */
	public function update(Request $request, int $id)
	{
		$user = $request->user();
		$userId = method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id;
		$model = CrmRequest::forUser($userId)->findOrFail($id);

		// Base request + property specification validation
		$rules = [
			'stage_id'                  => ['sometimes', 'nullable', 'integer', 'exists:users_api_customers_stages,id'],
			'customer_name'             => ['sometimes', 'required', 'string', 'max:255'],
			'customer_phone'            => ['sometimes', 'required', 'string', 'max:32'],
			'property_id'               => ['nullable', 'integer'],
			'property_specifications'   => ['nullable', 'array'],

			// Nested validation (optional keys)
			'property_specifications.basic_information' => ['nullable', 'array'],
			'property_specifications.basic_information.address' => ['nullable', 'string'],
			'property_specifications.basic_information.building' => ['nullable'],
			'property_specifications.basic_information.price' => ['nullable', 'numeric'],
			'property_specifications.basic_information.payment_method' => ['nullable'],
			'property_specifications.basic_information.price_per_sqm' => ['nullable', 'numeric'],
			'property_specifications.basic_information.listing_type' => ['nullable'],
			'property_specifications.basic_information.property_category' => ['nullable'],
			'property_specifications.basic_information.project' => ['nullable'],
			'property_specifications.basic_information.city' => ['nullable'],
			'property_specifications.basic_information.district' => ['nullable'],
			'property_specifications.basic_information.area' => ['nullable'],
			'property_specifications.basic_information.property_type' => ['nullable'],

			'property_specifications.details' => ['nullable', 'array'],
			'property_specifications.details.features' => ['nullable', 'array'],

			'property_specifications.attributes' => ['nullable', 'array'],
			'property_specifications.attributes.area_sqft' => ['nullable', 'numeric'],
			'property_specifications.attributes.year_built' => ['nullable', 'integer'],

			'property_specifications.facilities' => ['nullable', 'array'],
			'property_specifications.facilities.bedrooms' => ['nullable', 'integer'],
			'property_specifications.facilities.bathrooms' => ['nullable', 'integer'],
			'property_specifications.facilities.rooms' => ['nullable', 'integer'],
			'property_specifications.facilities.floors' => ['nullable', 'integer'],
			'property_specifications.facilities.floor_number' => ['nullable', 'integer'],
			'property_specifications.facilities.drivers_room' => ['nullable', 'boolean'],
			'property_specifications.facilities.maids_room' => ['nullable', 'boolean'],
			'property_specifications.facilities.dining_room' => ['nullable', 'boolean'],
			'property_specifications.facilities.living_room' => ['nullable', 'boolean'],
			'property_specifications.facilities.majlis' => ['nullable', 'boolean'],
			'property_specifications.facilities.storage_room' => ['nullable', 'boolean'],
			'property_specifications.facilities.basement' => ['nullable', 'boolean'],
			'property_specifications.facilities.swimming_pool' => ['nullable', 'boolean'],
			'property_specifications.facilities.kitchen' => ['nullable', 'boolean'],
			'property_specifications.facilities.balcony' => ['nullable', 'boolean'],
			'property_specifications.facilities.garden' => ['nullable', 'boolean'],
			'property_specifications.facilities.annex' => ['nullable', 'boolean'],
			'property_specifications.facilities.elevator' => ['nullable', 'boolean'],
			'property_specifications.facilities.parking_space' => ['nullable', 'integer'],

			'position'                  => ['nullable', 'integer', 'min:0'],
		];

		// If property data is present or request already has a property, allow updating property fields.
		$propertyDataKeys = [
			'payment_method', 'price', 'pricePerMeter', 'purpose', 'type', 'beds', 'bath', 'area', 'status',
			'latitude', 'longitude', 'project_id', 'region_id', 'category_id', 'features', 'building_id',
			'water_meter_number', 'electricity_meter_number', 'deed_number', 'video_url', 'virtual_tour', 'size',
			'address', 'title', 'description', 'city_id', 'state_id',
			'facade_id', 'length', 'width', 'street_width_north', 'street_width_south', 'street_width_east', 'street_width_west',
			'building_age', 'rooms', 'bathrooms', 'floors', 'floor_number', 'driver_room', 'maid_room', 'dining_room',
			'living_room', 'majlis', 'storage_room', 'basement', 'swimming_pool', 'kitchen', 'balcony', 'garden',
			'annex', 'elevator', 'private_parking',
		];

		$propertyInputPresent = $request->filled('property_id') || $model->property_id || collect($propertyDataKeys)->some(function ($k) use ($request) {
			return $request->has($k);
		});

		if ($propertyInputPresent) {
			$rules = array_merge($rules, [
				'payment_method' => ['nullable'],
				'price' => ['nullable', 'numeric'],
				'pricePerMeter' => ['nullable', 'numeric'],
				'purpose' => ['nullable'],
				'type' => ['nullable'],
				'beds' => ['nullable', 'integer'],
				'bath' => ['nullable', 'integer'],
				'area' => ['nullable', 'numeric'],
				'status' => ['nullable', 'integer'],
				'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
				'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
				'project_id' => ['nullable', 'integer'],
				'region_id' => ['nullable', 'integer'],
				'category_id' => ['nullable', 'integer'],
				'features' => ['nullable', 'array'],
				'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
				'water_meter_number' => ['nullable', 'string'],
				'electricity_meter_number' => ['nullable', 'string'],
				'deed_number' => ['nullable', 'string'],
				'video_url' => ['nullable', 'string'],
				'virtual_tour' => ['nullable', 'string'],
				'size' => ['nullable', 'numeric'],

				// Property content fields
				'address' => ['nullable', 'string'],
				'title' => ['nullable', 'string', 'max:255'],
				'description' => ['nullable', 'string'],
				'city_id' => ['nullable', 'integer'],
				'state_id' => ['nullable', 'integer'],

				// Property characteristics
				'facade_id' => ['nullable', 'numeric'],
				'length' => ['nullable', 'numeric'],
				'width' => ['nullable', 'numeric'],
				'street_width_north' => ['nullable', 'numeric'],
				'street_width_south' => ['nullable', 'numeric'],
				'street_width_east' => ['nullable', 'numeric'],
				'street_width_west' => ['nullable', 'numeric'],
				'building_age' => ['nullable', 'integer'],
				'rooms' => ['nullable', 'integer'],
				'bathrooms' => ['nullable', 'integer'],
				'floors' => ['nullable', 'integer'],
				'floor_number' => ['nullable', 'integer'],
				'driver_room' => ['nullable', 'integer'],
				'maid_room' => ['nullable', 'integer'],
				'dining_room' => ['nullable', 'integer'],
				'living_room' => ['nullable', 'integer'],
				'majlis' => ['nullable', 'integer'],
				'storage_room' => ['nullable', 'integer'],
				'basement' => ['nullable', 'integer'],
				'swimming_pool' => ['nullable', 'integer'],
				'kitchen' => ['nullable', 'integer'],
				'balcony' => ['nullable', 'integer'],
				'garden' => ['nullable', 'integer'],
				'annex' => ['nullable', 'integer'],
				'elevator' => ['nullable', 'integer'],
				'private_parking' => ['nullable', 'integer'],
			]);
		}

		$validator = Validator::make($request->all(), $rules);

		$validator->after(function ($v) use ($request) {
			if ($request->filled('property_id') && $request->filled('property_specifications')) {
				$v->errors()->add('property_specifications', 'property_id and property_specifications cannot be used together.');
			}
		});

		$validated = $validator->validate();

		// Handle mutual exclusivity: if one is present non-null, nullify the other
		if (array_key_exists('property_id', $validated) && !is_null($validated['property_id'])) {
			$validated['property_specifications'] = null;
		}
		if (array_key_exists('property_specifications', $validated) && !is_null($validated['property_specifications'])) {
			$validated['property_id'] = null;
		}

		// If stage changes directly via update, move to end of new stage by default (unless position provided)
		if (array_key_exists('stage_id', $validated)) {
			$newStageId = $validated['stage_id'];
			if (!is_null($newStageId) && !array_key_exists('position', $validated)) {
				$max = CrmRequest::query()
					->forUser($userId)
					->where('stage_id', $newStageId)
					->max('position');
				$validated['position'] = is_null($max) ? 1 : ($max + 1);
			}
		}

		// Extract property-related fields from validated data (keep request fields clean)
		$propertyFields = [];
		$propertyContentFields = [];
		$propertyCharacteristicFields = [];

		$propertyFieldKeys = [
			'region_id', 'price', 'pricePerMeter', 'purpose', 'type', 'beds', 'bath', 'area',
			'video_url', 'virtual_tour', 'status', 'latitude', 'longitude', 'features',
			'category_id', 'project_id', 'payment_method', 'building_id', 'water_meter_number',
			'electricity_meter_number', 'deed_number', 'size',
		];

		$propertyContentFieldKeys = ['address', 'title', 'description', 'city_id', 'state_id'];

		$propertyCharacteristicFieldKeys = [
			'facade_id', 'length', 'width', 'street_width_north', 'street_width_south', 'street_width_east', 'street_width_west',
			'building_age', 'rooms', 'bathrooms', 'floors', 'floor_number', 'driver_room', 'maid_room', 'dining_room',
			'living_room', 'majlis', 'storage_room', 'basement', 'swimming_pool', 'kitchen', 'balcony', 'garden',
			'annex', 'elevator', 'private_parking', 'size',
		];

		foreach ($propertyFieldKeys as $key) {
			if (array_key_exists($key, $validated)) {
				$propertyFields[$key] = $validated[$key];
				unset($validated[$key]);
			}
		}

		foreach ($propertyContentFieldKeys as $key) {
			if (array_key_exists($key, $validated)) {
				$propertyContentFields[$key] = $validated[$key];
				unset($validated[$key]);
			}
		}

		foreach ($propertyCharacteristicFieldKeys as $key) {
			if (array_key_exists($key, $validated)) {
				$propertyCharacteristicFields[$key] = $validated[$key];
				unset($validated[$key]);
			}
		}

		$finalPropertyId = $validated['property_id'] ?? $model->property_id;

		DB::transaction(function () use (&$model, $validated, $userId, $finalPropertyId, $propertyFields, $propertyContentFields, $propertyCharacteristicFields) {
			// Update CRM request
			$model->fill($validated)->save();

			// If no property is linked, skip property update
			if (!$finalPropertyId) {
				return;
			}

			// Only update property if we received property-related fields
			if (empty($propertyFields) && empty($propertyContentFields) && empty($propertyCharacteristicFields)) {
				return;
			}

			$property = UserProperty::where('id', $finalPropertyId)
				->where('user_id', $userId)
				->first();

			if (!$property) {
				return;
			}

			// Update main property record
			if (!empty($propertyFields)) {
				// Normalize features
				if (isset($propertyFields['features'])) {
					if (is_string($propertyFields['features'])) {
						$decoded = json_decode($propertyFields['features'], true);
						if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
							$propertyFields['features'] = $decoded;
						} else {
							$propertyFields['features'] = [$propertyFields['features']];
						}
					} elseif (!is_array($propertyFields['features'])) {
						$propertyFields['features'] = [];
					}
				}

				$property->updateProperty($propertyFields);
			}

			// Update / create PropertyContent for default language
			if (!empty($propertyContentFields)) {
				$defaultLanguage = Language::where('user_id', $userId)
					->where('is_default', 1)
					->first();

				if ($defaultLanguage) {
					$content = PropertyContent::where('property_id', $property->id)
						->where('language_id', $defaultLanguage->id)
						->first();

					$updateData = $propertyContentFields;

					if (isset($updateData['title'])) {
						$updateData['slug'] = PropertyContent::generateUniqueSlug($updateData['title'], $property->id);
					}

					if ($content) {
						$content->update($updateData);
					} else {
						$categoryId = $property->category_id ?? ApiUserCategory::where('slug', 'other')->value('id');
						$stateId = $updateData['state_id'] ?? 3;

						PropertyContent::create([
							'user_id' => $userId,
							'property_id' => $property->id,
							'language_id' => $defaultLanguage->id,
							'category_id' => $categoryId,
							'state_id' => $stateId,
							'city_id' => $updateData['city_id'] ?? null,
							'title' => $updateData['title'] ?? '',
							'slug' => $updateData['slug'] ?? PropertyContent::generateUniqueSlug('property-' . $property->id, $property->id),
							'address' => $updateData['address'] ?? '',
							'description' => $updateData['description'] ?? '',
						]);
					}
				}
			}

			// Update property characteristics
			if (!empty($propertyCharacteristicFields)) {
				$propertyCharacteristicFields['property_id'] = $property->id;
				$propertyCharacteristicFields['facade_id'] = !empty($propertyCharacteristicFields['facade_id']) ? $propertyCharacteristicFields['facade_id'] : null;

				UserPropertyCharacteristic::updateOrCreate(
					['property_id' => $property->id],
					$propertyCharacteristicFields
				);
			}
		});

		return $this->success(['request' => $model]);
	}

	/**
	 * DELETE /api/v1/crm/requests/{id}
	 */
	public function destroy(Request $request, int $id)
	{
		$model = CrmRequest::forUser($request->user()->id)->findOrFail($id);
		$model->delete();
		return response()->json(['status' => true]);
	}

	/**
	 * POST /api/v1/crm/requests/{id}/change-stage
	 */
	public function changeStage(Request $request, int $id)
	{
		$user = $request->user();
		$model = CrmRequest::forUser($user->id)->findOrFail($id);

		$validated = $request->validate([
			'stage_id' => ['required', 'integer', 'exists:users_api_customers_stages,id'],
		]);

		$newStageId = (int) $validated['stage_id'];

		$max = CrmRequest::query()
			->forUser($user->id)
			->where('stage_id', $newStageId)
			->max('position');

		$model->stage_id = $newStageId;
		$model->position = is_null($max) ? 1 : ($max + 1);
		$model->save();

		return $this->success([
			'request' => $model,
		]);
	}

	/**
	 * POST /api/v1/crm/requests/reorder
	 * Body: { stage_id: int, order: [requestId1, requestId2, ...] }
	 */
	public function reorder(Request $request)
	{
		$user = $request->user();
		$validated = $request->validate([
			'stage_id' => ['required', 'integer', 'exists:users_api_customers_stages,id'],
			'order'    => ['required', 'array'],
			'order.*'  => ['integer', 'distinct'],
		]);

		$stageId = (int) $validated['stage_id'];
		$order = $validated['order'];

		DB::transaction(function () use ($order, $stageId, $user) {
			foreach ($order as $index => $requestId) {
				CrmRequest::query()
					->forUser($user->id)
					->where('id', $requestId)
					->update([
						'stage_id' => $stageId,
						'position' => $index + 1,
					]);
			}
		});

		return $this->success([
			'stage_id' => $stageId,
			'order' => $order,
		]);
	}

	/**
	 * GET /api/v1/crm/stages
	 * Returns authenticated user's stages (id, stage_name) ordered.
	 */
	public function stages(Request $request)
	{
		$userId = $request->user()->id;

		$stages = UserApiCustomerStage::query()
			->where('user_id', $userId)
			->where('is_active', true)
			->orderBy('order', 'asc')
			->get(['id', 'stage_name', 'color', 'icon', 'order']);

		return $this->success([
			'stages' => $stages
		]);
	}
}
