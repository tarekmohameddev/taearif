<?php

namespace App\Http\Controllers\Api\V1\Crm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Crm\ChangeCrmRequestStageRequest;
use App\Http\Requests\Api\Crm\ReorderCrmRequestsRequest;
use App\Http\Requests\Api\Crm\StoreCrmRequest;
use App\Http\Requests\Api\Crm\UpdateCrmRequestRequest;
use App\Http\Requests\Api\Crm\IndexCrmRequestsRequest;
use App\Models\Api\Crm\CrmRequest;
use App\Models\Api\Crm\CrmCard;
use App\Models\Api\UserApiCustomerStage;
use App\Models\User\RealestateManagement\Property as UserProperty;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\UserPropertyCharacteristic;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\Reminder;
use App\Http\Resources\Crm\ReminderResource;

class CrmRequestController extends ApiController
{
	/**
	 * GET /api/v1/crm/requests
	 */
	public function index(IndexCrmRequestsRequest $request)
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
			$request->filled('reminder_type_id') || 
			!empty($catIds) || 
			!empty($propIds);

		if ($hasCustomerFilters) {
			$baseQuery->whereHas('customer', function ($customerQuery) use ($request, $catIds, $propIds, $userId) {
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

			// Reminder type filter
			if ($request->filled('reminder_type_id')) {
				$customerQuery->whereExists(function ($sub) use ($request, $userId) {
					$sub->select(DB::raw(1))
						->from('reminders')
						->whereColumn('reminders.customer_id', 'api_customers.id')
						->where('reminders.user_id', $userId)
						->where('reminders.reminder_type_id', (int)$request->input('reminder_type_id'))
						->whereNull('reminders.deleted_at');
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
						'property_type' => $prop->property_type ?? null,
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
	public function store(StoreCrmRequest $request)
	{
		$user = auth()->user();
		$validated = $request->validated();

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

		// Get reminders for the customer
		$reminders = [];
		if (!empty($model->customer_id)) {
			$tenantId = $request->user()->tenantOwnerId();
			$reminders = Reminder::forUser($tenantId)
				->forCustomer($model->customer_id)
				->with(['reminderType', 'customer.city', 'customer.district'])
				->orderBy('datetime', 'asc')
				->get();
			$reminders = ReminderResource::collection($reminders);
		}

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
						'property_type' => $prop->property_type ?? '',
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
			'reminders' => $reminders,
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
	public function update(UpdateCrmRequestRequest $request, int $id)
	{
		$user = auth()->user();
		$userId = method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id;
		$model = CrmRequest::forUser($userId)->findOrFail($id);
		$validated = $request->validated();

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

					if ($content) {
						// Ensure slug is never accepted from request
						unset($updateData['slug']);
						// Regenerate slug if title is being updated
						if (isset($updateData['title'])) {
							$updateData['slug'] = PropertyContent::generateUniqueSlug($updateData['title'], $property->id);
						}
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
	public function changeStage(ChangeCrmRequestStageRequest $request, int $id)
	{
		$user = auth()->user();
		$model = CrmRequest::forUser($user->id)->findOrFail($id);
		$validated = $request->validated();

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
	public function reorder(ReorderCrmRequestsRequest $request)
	{
		$user = auth()->user();
		$validated = $request->validated();

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
