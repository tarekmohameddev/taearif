<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;
use App\Http\Requests\Rms\Reminder\SnoozeReminderRequest;
use App\Http\Resources\Rms\ReminderResource;
use Illuminate\Http\Request;
use App\Services\Rms\ReminderService;

class ReminderController extends BaseApiController
{
    use HandlesApiExceptions;

    protected $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    public function index(Request $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $filters = $request->only(['type', 'status', 'from', 'to']);
            $reminders = $this->reminderService->list($this->getUserId(), $filters);

            return $this->success(ReminderResource::collection($reminders));
        }, 'list reminders');
    }

    public function dismiss($id)
    {
        return $this->executeWithExceptionHandling(function () use ($id) {
            $reminder = $this->reminderService->dismiss($id, $this->getUserId());
            return $this->success(
                ReminderResource::make($reminder),
                'Reminder dismissed successfully'
            );
        }, 'dismiss reminder');
    }

    public function snooze(SnoozeReminderRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $reminder = $this->reminderService->snooze(
                $id,
                $request->validated()['snooze_until'],
                $this->getUserId()
            );

            return $this->success(
                ReminderResource::make($reminder),
                'Reminder snoozed successfully'
            );
        }, 'snooze reminder');
    }
}

