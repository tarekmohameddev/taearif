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
        try {
            $filters = $request->only(['type', 'status', 'from', 'to']);
            $reminders = $this->reminderService->list(auth()->id(), $filters);

            return response()->json(['status' => true, 'data' => $reminders]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function dismiss($id)
    {
        try {
            $reminder = $this->reminderService->dismiss($id, auth()->id());
            return response()->json(['status' => true, 'data' => $reminder]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reminder not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function snooze(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'snooze_until' => 'required|date|after:today'
            ]);

            $reminder = $this->reminderService->snooze($id, $validated['snooze_until'], auth()->id());

            return response()->json(['status' => true, 'data' => $reminder]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reminder not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

