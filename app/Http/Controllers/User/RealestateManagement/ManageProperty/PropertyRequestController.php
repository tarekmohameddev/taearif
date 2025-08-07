<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProperty;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\UserPropertyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PropertyRequestController extends Controller
{
    public function create()
    {
        return view('user-front.realestate.property.property_requests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_type' => 'required|in:شقة,دور,تاون هاوس,فيلا,أرض,عمارة',
            'category' => 'nullable|in:سكني,تجاري,صناعي,زراعي',
            'neighborhoods' => 'nullable|array',
            'neighborhoods.*' => 'string|max:255',
            'area_from' => 'nullable|integer|min:0',
            'area_to' => 'nullable|integer|min:0',
            'purchase_method' => 'required|in:كاش,تمويل بنكي',
            'budget_from' => 'required|numeric|min:0',
            'budget_to' => 'required|numeric|min:0',
            'seriousness' => 'nullable|in:مستعد فورًا,خلال شهر,خلال 3 أشهر,لاحقًا / استكشاف فقط',
            'purchase_goal' => 'nullable|in:سكن خاص,استثمار وتأجير,بناء وبيع,مشروع تجاري',
            'wants_similar_offers' => 'nullable|boolean',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'contact_on_whatsapp' => 'nullable|boolean',
            'notes' => 'nullable|string|max:5000',
        ]);

        $validated['user_id'] = auth()->check() ? auth()->id() : null;
        $validated['region'] = 'الرياض';
        $validated['is_read'] = false;
        $validated['is_active'] = true;

        UserPropertyRequest::create($validated);

        return redirect()
            ->back()
            ->with('success', 'تم إرسال الطلب بنجاح، سنقوم بالتواصل معك قريبًا.');
    }
}

