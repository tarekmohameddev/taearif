<?php

namespace App\Domain\CustomersHub\Services;

use App\Models\Api\ApiCustomerInquiry;
use App\Models\Api\UserPropertyRequest;
use App\Models\CustomersHub\CrmHubNote;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PropertyRequestDetailBuilder
 *
 * Builds full property request and inquiry payloads for pipeline and customer detail.
 * Shared by RequestsController (show) and CustomerDetailService (propertyRequests).
 */
class PropertyRequestDetailBuilder
{
    /**
     * Build full property request payload for show endpoint.
     */
    public function buildFullPropertyRequestAction(int $userId, object $action): ?array
    {
        $propertyRequestId = (int) ($action->sourceId ?? 0);
        if ($propertyRequestId <= 0) {
            return null;
        }

        $propertyRequest = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->where('id', $propertyRequestId)
            ->where('is_active', 1)
            ->first();

        if (!$propertyRequest) {
            return null;
        }

        $now = Carbon::now();
        $appointmentRows = DB::table('property_request_appointments')
            ->where('user_id', $userId)
            ->where('property_request_id', $propertyRequestId)
            ->orderBy('datetime', 'asc')
            ->get();
        $reminderRows = DB::table('property_request_reminders')
            ->where('user_id', $userId)
            ->where('property_request_id', $propertyRequestId)
            ->orderBy('datetime', 'asc')
            ->get();

        $appointments = $appointmentRows
            ->map(fn ($row) => $this->formatPropertyRequestAppointment($row))
            ->values()
            ->all();
        $reminders = $reminderRows
            ->map(fn ($row) => $this->formatPropertyRequestReminder($row, $now))
            ->values()
            ->all();

        $hubNoteRows = CrmHubNote::where('noteable_type', UserPropertyRequest::class)
            ->where('noteable_id', $propertyRequestId)
            ->with('employee.basic_setting')
            ->orderBy('created_at')
            ->get();

        $stageId = null;
        $stage = null;
        if (!empty($propertyRequest->customers_hub_stage_id)) {
            $stageRow = DB::table('customers_hub_stages')
                ->where('stage_id', $propertyRequest->customers_hub_stage_id)
                ->where('is_active', true)
                ->first(['id', 'stage_id', 'stage_name_ar', 'stage_name_en']);
            if ($stageRow) {
                $stageId = $stageRow->stage_id;
                $stage = [
                    'id' => (int) $stageRow->id,
                    'stage_id' => $stageRow->stage_id,
                    'nameAr' => $stageRow->stage_name_ar,
                    'nameEn' => $stageRow->stage_name_en ?? $stageRow->stage_name_ar,
                ];
            }
        }

        $city = null;
        if (!empty($propertyRequest->city_id)) {
            $city = DB::table('user_cities')
                ->where('id', $propertyRequest->city_id)
                ->value('name_ar');
        }

        $districtAR = null;
        if (!empty($propertyRequest->districts_id)) {
            $districtAR = DB::table('user_districts')
                ->where('id', $propertyRequest->districts_id)
                ->value('name_ar');
        }

        $propertyCategory = null;
        if (!empty($propertyRequest->category_id)) {
            $propertyCategory = DB::table('api_user_categories')
                ->where('id', $propertyRequest->category_id)
                ->value('name');
        }

        $assignedTo = isset($action->assignedTo) && $action->assignedTo !== null && $action->assignedTo !== ''
            ? (int) $action->assignedTo
            : null;
        $assignedToName = trim((string) ($action->assignedToName ?? ''));

        if ($assignedTo === null || $assignedToName === '') {
            // Primary: request-level assignment (source of truth)
            $uprAssignee = DB::table('users_property_requests as upr')
                ->leftJoin('users as u', 'upr.responsible_employee_id', '=', 'u.id')
                ->where('upr.user_id', $userId)
                ->where('upr.id', $propertyRequestId)
                ->select([
                    'upr.responsible_employee_id',
                    DB::raw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as assigned_to_name"),
                ])
                ->first();

            if ($assignedTo === null && $uprAssignee && $uprAssignee->responsible_employee_id !== null) {
                $assignedTo = (int) $uprAssignee->responsible_employee_id;
            }
            if ($assignedToName === '' && $uprAssignee) {
                $assignedToName = trim((string) ($uprAssignee->assigned_to_name ?? ''));
            }
        }

        // Fallback: customer-level assignment (when request has no explicit assignment)
        if ($assignedTo === null || $assignedToName === '') {
            $assignee = DB::table('api_customers as ac')
                ->leftJoin('users as u', 'ac.responsible_employee_id', '=', 'u.id')
                ->where('ac.user_id', $userId)
                ->where('ac.phone_number', $propertyRequest->phone)
                ->select([
                    'ac.responsible_employee_id',
                    DB::raw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as assigned_to_name"),
                ])
                ->first();

            if ($assignedTo === null && $assignee && $assignee->responsible_employee_id !== null) {
                $assignedTo = (int) $assignee->responsible_employee_id;
            }
            if ($assignedToName === '' && $assignee) {
                $assignedToName = trim((string) ($assignee->assigned_to_name ?? ''));
            }
        }

        $fullAction = array_merge((array) $action, (array) $propertyRequest);

        $fullAction['id'] = $action->id ?? ('property_request_' . $propertyRequestId);
        $fullAction['property_request_id'] = $propertyRequestId;
        $fullAction['sourceId'] = $propertyRequestId;
        $fullAction['sourceTable'] = 'users_property_requests';
        $fullAction['objectType'] = 'property_request';
        $fullAction['source'] = $action->source ?? ($propertyRequest->source ?? 'website');

        $fullAction['notes'] = $this->formatHubNotes($hubNoteRows);
        $fullAction['stage_id'] = $stageId;
        $fullAction['stage'] = $stage;
        $fullAction['priority'] = $this->mapPropertyRequestPriorityToString($propertyRequest->seriousness ?? null);
        $fullAction['status'] = $this->resolvePropertyRequestStatus($propertyRequest);
        $fullAction['propertyCategory'] = $propertyCategory;
        $fullAction['propertyType'] = $propertyRequest->property_type;
        $fullAction['city'] = $city;
        $fullAction['districtAR'] = $districtAR;
        $fullAction['state'] = $propertyRequest->region;
        $fullAction['budgetMin'] = $propertyRequest->budget_from !== null ? (float) $propertyRequest->budget_from : null;
        $fullAction['budgetMax'] = $propertyRequest->budget_to !== null ? (float) $propertyRequest->budget_to : null;
        $fullAction['assignedTo'] = $assignedTo;
        $fullAction['assignedToName'] = $assignedToName;
        $fullAction['completedAt'] = null;
        $fullAction['completedBy'] = null;
        $fullAction['snoozedUntil'] = null;
        $fullAction['dueDate'] = null;
        $fullAction['appointments'] = $appointments;
        $fullAction['reminders'] = $reminders;
        $fullAction['metadata'] = $this->buildPropertyRequestMetadata($propertyRequest, $action->metadata ?? []);

        return $fullAction;
    }

    /**
     * Build full inquiry payload for show endpoint (stage, appointments, reminders, assignee).
     */
    public function buildFullInquiryAction(int $userId, object $action): ?array
    {
        $inquiryId = (int) ($action->sourceId ?? 0);
        if ($inquiryId <= 0) {
            return null;
        }

        $inquiry = DB::table('api_customer_inquiry')
            ->where('user_id', $userId)
            ->where('id', $inquiryId)
            ->first();

        if (!$inquiry) {
            return null;
        }

        $now = Carbon::now();
        $appointmentRows = DB::table('inquiry_appointments')
            ->where('user_id', $userId)
            ->where('inquiry_id', $inquiryId)
            ->orderBy('datetime', 'asc')
            ->get();
        $reminderRows = DB::table('inquiry_reminders')
            ->where('user_id', $userId)
            ->where('inquiry_id', $inquiryId)
            ->orderBy('datetime', 'asc')
            ->get();

        $appointments = $appointmentRows
            ->map(fn ($row) => $this->formatPropertyRequestAppointment($row))
            ->values()
            ->all();
        $reminders = $reminderRows
            ->map(fn ($row) => $this->formatPropertyRequestReminder($row, $now))
            ->values()
            ->all();

        $inquiryHubNoteRows = CrmHubNote::where('noteable_type', ApiCustomerInquiry::class)
            ->where('noteable_id', $inquiryId)
            ->with('employee.basic_setting')
            ->orderBy('created_at')
            ->get();

        $stageId = null;
        $stage = null;
        if (!empty($inquiry->stage_id)) {
            $stageRow = DB::table('customers_hub_stages')
                ->where('stage_id', $inquiry->stage_id)
                ->where('is_active', true)
                ->first(['id', 'stage_id', 'stage_name_ar', 'stage_name_en']);
            if ($stageRow) {
                $stageId = $stageRow->stage_id;
                $stage = [
                    'id' => (int) $stageRow->id,
                    'stage_id' => $stageRow->stage_id,
                    'nameAr' => $stageRow->stage_name_ar,
                    'nameEn' => $stageRow->stage_name_en ?? $stageRow->stage_name_ar,
                ];
            }
        }

        $assignedTo = isset($action->assignedTo) && $action->assignedTo !== null && $action->assignedTo !== ''
            ? (int) $action->assignedTo
            : null;
        $assignedToName = trim((string) ($action->assignedToName ?? ''));
        $customerId = $inquiry->customer_id ?? null;
        if (($assignedTo === null || $assignedToName === '') && $customerId) {
            $assignee = DB::table('api_customers as ac')
                ->leftJoin('users as u', 'ac.responsible_employee_id', '=', 'u.id')
                ->where('ac.user_id', $userId)
                ->where('ac.id', $customerId)
                ->select([
                    'ac.responsible_employee_id',
                    DB::raw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as assigned_to_name"),
                ])
                ->first();
            if ($assignee) {
                if ($assignedTo === null && $assignee->responsible_employee_id !== null) {
                    $assignedTo = (int) $assignee->responsible_employee_id;
                }
                if ($assignedToName === '' && !empty($assignee->assigned_to_name)) {
                    $assignedToName = trim((string) $assignee->assigned_to_name);
                }
            }
        }

        $fullAction = array_merge((array) $action, (array) $inquiry);
        $fullAction['id'] = $action->id ?? ('inquiry_' . $inquiryId);
        $fullAction['sourceId'] = $inquiryId;
        $fullAction['sourceTable'] = 'api_customer_inquiry';
        $fullAction['objectType'] = 'inquiry';
        $fullAction['stage_id'] = $stageId;
        $fullAction['stage'] = $stage;
        $fullAction['status'] = $this->mapPropertyRequestStatusToString(
            (bool) ($inquiry->is_archived ?? false),
            (bool) ($inquiry->is_read ?? false)
        );
        $fullAction['city'] = $inquiry->city ?? null;
        $fullAction['state'] = $inquiry->region_name ?? $inquiry->district ?? null;
        $fullAction['budgetMin'] = isset($inquiry->budget) && $inquiry->budget !== null ? (float) $inquiry->budget : null;
        $fullAction['budgetMax'] = null;
        $fullAction['assignedTo'] = $assignedTo;
        $fullAction['assignedToName'] = $assignedToName;
        $fullAction['appointments'] = $appointments;
        $fullAction['reminders'] = $reminders;
        $fullAction['notes'] = $this->formatHubNotes($inquiryHubNoteRows);

        return $fullAction;
    }

    /**
     * Format appointment row for list/single (no requestId/customerId).
     * Public so RequestsController can use in list enrichment and ForResponse.
     */
    public function formatPropertyRequestAppointment(object $row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'type' => $row->type,
            'datetime' => Carbon::parse($row->datetime)->toIso8601String(),
            'duration' => (int) $row->duration,
            'status' => $row->status ?? 'scheduled',
            'priority' => $this->mapPriorityAppointmentToString((int) ($row->priority ?? 2)),
            'notes' => $row->notes,
            'createdAt' => Carbon::parse($row->created_at)->toIso8601String(),
        ];
    }

    /**
     * Format reminder row for list/single (with isOverdue, daysUntilDue).
     * Public so RequestsController can use in list enrichment and ForResponse.
     */
    public function formatPropertyRequestReminder(object $row, Carbon $now): array
    {
        $dt = Carbon::parse($row->datetime);
        $isOverdue = $dt->lt($now);
        $daysUntilDue = $isOverdue ? 0 : (int) $now->diffInDays($dt, false);

        return [
            'id' => $row->id,
            'title' => $row->title,
            'description' => $row->description,
            'datetime' => $dt->toIso8601String(),
            'priority' => $this->mapPriorityReminderToString((int) ($row->priority ?? 1)),
            'type' => $row->type ?? 'follow_up',
            'status' => $row->status ?? 'pending',
            'notes' => $row->notes,
            'isOverdue' => $isOverdue,
            'daysUntilDue' => $daysUntilDue,
            'createdAt' => Carbon::parse($row->created_at)->toIso8601String(),
        ];
    }

    private function mapPropertyRequestPriorityToString(?string $seriousness): string
    {
        return match ($seriousness) {
            'مستعد فورًا' => 'urgent',
            'خلال شهر' => 'high',
            'خلال 3 أشهر' => 'medium',
            'لاحقًا / استكشاف فقط' => 'low',
            default => 'medium',
        };
    }

    private function resolvePropertyRequestStatus(object $propertyRequest): string
    {
        $statusId = $propertyRequest->status_id ?? null;
        if ($statusId !== null && $statusId !== '') {
            $slug = DB::table('property_request_statuses')
                ->where('id', (int) $statusId)
                ->value('slug');
            if ($slug !== null && $slug !== '') {
                return $this->mapStatusSlugToApiStatus($slug);
            }
        }
        return $this->mapPropertyRequestStatusToString(
            (bool) ($propertyRequest->is_archived ?? false),
            (bool) ($propertyRequest->is_read ?? false)
        );
    }

    private function mapStatusSlugToApiStatus(string $slug): string
    {
        return match ($slug) {
            'cancelled' => 'dismissed',
            'completed' => 'completed',
            'suspended' => 'pending',
            'in_progress', 'waiting' => 'in_progress',
            default => 'in_progress',
        };
    }

    private function mapPropertyRequestStatusToString(bool $isArchived, bool $isRead): string
    {
        if ($isArchived) {
            return 'dismissed';
        }
        if ($isRead) {
            return 'in_progress';
        }
        return 'pending';
    }

    private function buildPropertyRequestMetadata(object $propertyRequest, mixed $existingMetadata): array
    {
        $metadata = [];
        if (is_array($existingMetadata)) {
            $metadata = $existingMetadata;
        } elseif (is_object($existingMetadata)) {
            $metadata = (array) $existingMetadata;
        } elseif (is_string($existingMetadata)) {
            $decoded = json_decode($existingMetadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        $propertyCategoryNameAr = null;
        if (!empty($propertyRequest->category_id)) {
            $propertyCategoryNameAr = DB::table('api_user_categories')
                ->where('id', $propertyRequest->category_id)
                ->value('name');
        }

        $defaults = [
            'propertyRequestId' => (int) $propertyRequest->id,
            'propertyType' => $propertyRequest->property_type,
            'propertyCategory' => $propertyRequest->category_id,
            'propertyCategoryNameAr' => $propertyCategoryNameAr,
            'budgetFrom' => $propertyRequest->budget_from !== null ? (float) $propertyRequest->budget_from : null,
            'budgetTo' => $propertyRequest->budget_to !== null ? (float) $propertyRequest->budget_to : null,
            'purpose' => $propertyRequest->purpose,
            'seriousness' => $propertyRequest->seriousness,
        ];

        return array_replace($defaults, $metadata);
    }

    private function mapPriorityAppointmentToString(int $priority): string
    {
        return match ($priority) {
            4 => 'urgent',
            3 => 'high',
            2 => 'medium',
            1 => 'low',
            default => 'medium',
        };
    }

    private function mapPriorityReminderToString(int $priority): string
    {
        return match ($priority) {
            3 => 'urgent',
            2 => 'high',
            1 => 'medium',
            0 => 'low',
            default => 'medium',
        };
    }

    /**
     * @param  Collection<int, CrmHubNote>  $notes
     * @return array<int, array{id: int, note: string, addedBy: string, addedByName: string, addedByType: string|null, createdAt: string, updatedAt: string}>
     */
    private function formatHubNotes(Collection $notes): array
    {
        return $notes->map(fn (CrmHubNote $note) => $this->formatHubNote($note))->values()->all();
    }

    /**
     * @return array{id: int, note: string, addedBy: string, addedByName: string, addedByType: string|null, createdAt: string, updatedAt: string}
     */
    private function formatHubNote(CrmHubNote $note): array
    {
        $user = $note->employee;
        $addedByType = null;
        $addedByName = null;
        if ($user !== null) {
            $addedByType = $user->isTenant() ? 'tenant' : 'employee';
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if ($user->isTenant()) {
                $addedByName = trim((string) ($user->basic_setting?->company_name ?? ''));
                if ($addedByName === '') {
                    $addedByName = $fullName !== '' ? $fullName : ($user->email ?? null);
                }
            } else {
                $addedByName = $fullName !== '' ? $fullName : ($user->email ?? null);
            }
        }
        $addedByName = $addedByName !== null && $addedByName !== '' ? $addedByName : ('User #' . $note->employee_id);

        return [
            'id' => (int) $note->id,
            'note' => (string) $note->note,
            'addedBy' => (string) $note->employee_id,
            'addedByName' => $addedByName,
            'addedByType' => $addedByType,
            'createdAt' => Carbon::parse($note->created_at)->toIso8601String(),
            'updatedAt' => Carbon::parse($note->updated_at)->toIso8601String(),
        ];
    }

    /**
     * Get property summaries for a list of property IDs (for requests list).
     * Returns array of summary arrays: id, title, address, slug, price, featuredImage, district, city,
     * propertyType (residential/commercial/agricultural/industrial), category (شقة/فيلا), area, size,
     * listingType, listingTypeLabel (للبيع/للإيجار).
     * Uses user_properties + user_property_contents (first per property) + user_districts + api_user_categories.
     *
     * @param  array<int>  $propertyIds
     * @return array<int, array{id: int, title: string|null, address: string|null, slug: string|null, price: float|null, featuredImage: string|null, district: string|null, city: string|null, propertyType: string|null, category: string|null, area: int|null, size: string|null, listingType: string|null, listingTypeLabel: string|null}>
     */
    public function getPropertySummariesForIds(int $userId, array $propertyIds): array
    {
        $propertyIds = array_values(array_unique(array_filter(array_map('intval', $propertyIds))));
        if (empty($propertyIds)) {
            return [];
        }

        $rows = DB::table('user_properties as p')
            ->where('p.user_id', $userId)
            ->whereIn('p.id', $propertyIds)
            ->leftJoin(
                DB::raw('(SELECT property_id, MIN(id) AS content_id FROM user_property_contents GROUP BY property_id) AS first_pc'),
                'first_pc.property_id',
                '=',
                'p.id'
            )
            ->leftJoin('user_property_contents as pc', function ($join) {
                $join->on('pc.property_id', '=', 'p.id')
                    ->on('pc.id', '=', DB::raw('first_pc.content_id'));
            })
            ->leftJoin('user_districts as ud', 'pc.state_id', '=', 'ud.id')
            ->leftJoin('api_user_categories as cat', 'p.category_id', '=', 'cat.id')
            ->select([
                'p.id',
                'p.price',
                'p.featured_image',
                'p.purpose',
                'p.property_type',
                'p.area',
                'p.size',
                'pc.title',
                'pc.address',
                'pc.slug',
                'ud.name_ar as district',
                'ud.city_name_ar as city',
                'cat.name as category_name',
            ])
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $featuredImage = null;
            if (!empty($row->featured_image)) {
                $featuredImage = asset($row->featured_image);
            }
            $purpose = $row->purpose !== null ? trim((string) $row->purpose) : null;
            $listingType = $purpose;
            $listingTypeLabel = $this->purposeToListingLabel($purpose);
            $result[(int) $row->id] = [
                'id' => (int) $row->id,
                'title' => $row->title !== null ? (string) $row->title : null,
                'address' => $row->address !== null ? (string) $row->address : null,
                'slug' => $row->slug !== null ? (string) $row->slug : null,
                'price' => isset($row->price) && $row->price !== null ? (float) $row->price : null,
                'featuredImage' => $featuredImage,
                'district' => $row->district !== null ? (string) $row->district : null,
                'city' => $row->city !== null ? (string) $row->city : null,
                'propertyType' => $row->property_type !== null ? (string) $row->property_type : null,
                'category' => $row->category_name !== null ? (string) $row->category_name : null,
                'area' => isset($row->area) && $row->area !== null ? (int) $row->area : null,
                'size' => $row->size !== null && $row->size !== '' ? (string) $row->size : null,
                'listingType' => $listingType,
                'listingTypeLabel' => $listingTypeLabel,
            ];
        }
        return $result;
    }

    /**
     * Map purpose (sale/rent/for_sale/for_rent) to Arabic label للبيع / للإيجار.
     */
    private function purposeToListingLabel(?string $purpose): ?string
    {
        if ($purpose === null || $purpose === '') {
            return null;
        }
        $p = strtolower(trim($purpose));
        if (in_array($p, ['sale', 'for_sale'], true)) {
            return 'للبيع';
        }
        if (in_array($p, ['rent', 'for_rent'], true)) {
            return 'للإيجار';
        }
        return $purpose;
    }
}
