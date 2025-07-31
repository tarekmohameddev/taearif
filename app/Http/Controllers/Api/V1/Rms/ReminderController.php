<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Rms\ReminderService;


class ReminderController extends Controller
{
    protected $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['type', 'status', 'from', 'to']);
        $reminders = $this->reminderService->list(auth()->id(), $filters);

        return response()->json(['status' => true, 'data' => $reminders]);
    }

    public function dismiss($id)
    {
        $reminder = $this->reminderService->dismiss($id, auth()->id());
        return response()->json(['status' => true, 'data' => $reminder]);
    }

    public function snooze(Request $request, $id)
    {
        $validated = $request->validate([
            'snooze_until' => 'required|date|after:today'
        ]);

        $reminder = $this->reminderService->snooze($id, $validated['snooze_until'], auth()->id());

        return response()->json(['status' => true, 'data' => $reminder]);
    }
}

