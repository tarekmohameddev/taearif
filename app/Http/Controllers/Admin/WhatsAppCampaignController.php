<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WaCampaign;
use Illuminate\Http\Request;

class WhatsAppCampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = WaCampaign::query()
            ->with(['user:id,username,email', 'waNumber:id,phone_number,name,status']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $campaigns = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $statusOptions = [
            'draft' => 'مسودة',
            'scheduled' => 'مجدولة',
            'in_progress' => 'قيد الإرسال',
            'paused' => 'متوقفة',
            'sent' => 'مرسلة',
            'failed' => 'فاشلة',
            'cancelled' => 'ملغاة',
        ];

        return view('admin.communication.whatsapp-campaigns.index', compact('campaigns', 'statusOptions'));
    }

    public function show(int $id)
    {
        $campaign = WaCampaign::with(['user:id,username,email', 'waNumber', 'creator:id,username,first_name,last_name', 'template:id,name'])
            ->findOrFail($id);

        $logsSummary = $campaign->logs()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('admin.communication.whatsapp-campaigns.show', compact('campaign', 'logsSummary'));
    }
}
