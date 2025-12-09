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

class CrmRequestController extends ApiController
{
	/**
	 * GET /api/v1/crm/requests
	 */
	public function index(Request $request)
	{
		$user = $request->user();
		$userId = $user->id;

		// Statistics (4 cards)
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

		// Filters reused for per-stage queries
		$filterHasProperty = $request->filled('has_property') ? (string) $request->has_property : null;
		$filterTerm = $request->filled('q') ? trim((string) $request->q) : null;

		// Stages with requests inside each
		$stages = UserApiCustomerStage::query()
			->where('user_id', $userId)
			->where('is_active', true)
			->orderBy('order', 'asc')
			->get(['id', 'stage_name', 'color', 'icon', 'order'])
			->map(function ($stage) use ($userId, $filterHasProperty, $filterTerm) {
				$reqQuery = CrmRequest::query()
					->forUser($userId)
					->where('stage_id', $stage->id)
					->when($filterHasProperty !== null, function ($q) use ($filterHasProperty) {
						if ($filterHasProperty === '1') {
							$q->whereNotNull('property_id');
						} elseif ($filterHasProperty === '0') {
							$q->whereNull('property_id');
						}
					})
					->when(!empty($filterTerm), function ($q) use ($filterTerm) {
						$q->where(function ($qq) use ($filterTerm) {
							$qq->where('customer_name', 'like', "%{$filterTerm}%")
								->orWhere('customer_phone', 'like', "%{$filterTerm}%");
						});
					})
					->orderBy('position', 'asc')
					->orderByDesc('id');

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
					$customers = \App\Models\ApiCustomer::whereIn('id', $customerIds)
						->get(['id','name','phone_number','stage_id','priority_id','type_id'])
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
							'stage_id' => $c->stage_id,
							'priority_id' => $c->priority_id,
							'type_id' => $c->type_id,
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
				'password' => bcrypt(Str::random(24)),
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
			$c = \App\Models\ApiCustomer::find($model->customer_id);
			if ($c) {
				$customer = [
					'id' => $c->id,
					'name' => $c->name,
					'phone_number' => $c->phone_number,
					'stage_id' => $c->stage_id,
					'priority_id' => $c->priority_id,
					'type_id' => $c->type_id,
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

		$payload = [
			'request' => $model,
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
		$model = CrmRequest::forUser($user->id)->findOrFail($id);

		$validator = Validator::make($request->all(), [
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
		]);

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
					->forUser($user->id)
					->where('stage_id', $newStageId)
					->max('position');
				$validated['position'] = is_null($max) ? 1 : ($max + 1);
			}
		}

		$model->fill($validated)->save();

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


