<?php

namespace App\Http\Controllers\Api\V1\Calling;

use App\Domain\Calling\Models\CallLog;
use App\Domain\Calling\Models\CallRecording;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RecordingWebhookController extends Controller
{
    /**
     * POST /api/v1/calling/internal/recording-ready
     *
     * Called by the PBX upload script after a recording is stored in object storage.
     * Protected by VerifyPbxWebhookSecret middleware (X-Taearif-Secret header).
     * Accepts both JSON and form-encoded payloads.
     *
     * Fields:
     *   correlation_id  - the call UUID (TAEARIF_CALL_ID)
     *   path            - object storage key
     *   size            - file size in bytes
     *   duration        - duration in seconds
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'correlation_id' => ['required', 'string', 'size:36'],
            'path'           => ['required', 'string'],
            'size'           => ['nullable', 'integer'],
            'duration'       => ['nullable', 'integer'],
        ]);

        $callId = $request->input('correlation_id');
        $log    = CallLog::find($callId);

        if (!$log) {
            // Acknowledge but ignore — call may have been pruned
            return response()->json(['message' => 'ok'], 200);
        }

        CallRecording::updateOrCreate(
            ['call_log_id' => $callId],
            [
                'disk'             => config('calling.recordings.disk', 'oss'),
                'path'             => $request->input('path'),
                'size_bytes'       => $request->input('size'),
                'duration_seconds' => $request->input('duration'),
                'status'           => 'ready',
            ]
        );

        return response()->json(['message' => 'ok'], 200);
    }
}
