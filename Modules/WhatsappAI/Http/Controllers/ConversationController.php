<?php

namespace Modules\WhatsappAI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\WhatsappAI\Entities\WhatsappConversation;

class ConversationController extends Controller
{
    /**
     * List conversations with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = WhatsappConversation::with([
            'whatsappUser',
            'customer',
            'inquiry',
        ]);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by real estate inquiry only
        if ($request->boolean('real_estate_only')) {
            $query->where('is_real_estate_inquiry', true);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by phone or name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'last_message_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $conversations = $query->paginate($perPage);

        return response()->json($conversations);
    }

    /**
     * Show single conversation with messages
     */
    public function show(int $id): JsonResponse
    {
        $conversation = WhatsappConversation::with([
            'whatsappUser',
            'customer',
            'inquiry',
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
        ])->findOrFail($id);

        return response()->json($conversation);
    }

    /**
     * Get conversation statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->get('user_id');

        $query = WhatsappConversation::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'collecting' => (clone $query)->where('status', 'collecting')->count(),
            'processed' => (clone $query)->where('status', 'processed')->count(),
            'archived' => (clone $query)->where('status', 'archived')->count(),
            'real_estate_inquiries' => (clone $query)->where('is_real_estate_inquiry', true)->count(),
            'inquiries_created' => (clone $query)->whereNotNull('inquiry_id')->count(),
            'today' => (clone $query)->whereDate('created_at', today())->count(),
            'this_week' => (clone $query)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Archive a conversation
     */
    public function archive(int $id): JsonResponse
    {
        $conversation = WhatsappConversation::findOrFail($id);
        $conversation->update(['status' => 'archived']);

        return response()->json([
            'message' => 'Conversation archived successfully',
            'conversation' => $conversation,
        ]);
    }

    /**
     * Delete a conversation and its messages
     */
    public function destroy(int $id): JsonResponse
    {
        $conversation = WhatsappConversation::findOrFail($id);
        $conversation->delete();

        return response()->json([
            'message' => 'Conversation deleted successfully',
        ]);
    }
}

