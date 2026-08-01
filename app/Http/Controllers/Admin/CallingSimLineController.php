<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Calling\Exceptions\InvalidPhoneNumberException;
use App\Domain\Calling\Models\CallSimLine;
use App\Domain\Calling\Services\PhoneNumberService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CallingSimLineController extends Controller
{
    protected PhoneNumberService $phones;

    public function __construct(PhoneNumberService $phones)
    {
        $this->phones = $phones;
    }

    public function index(Request $request)
    {
        $query = CallSimLine::with([
            'tenant:id,first_name,last_name,email,username,company_name',
            'trunk:id,name,type,status',
            'dedicatedAgent:id,first_name,last_name,username,company_name',
        ]);

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('msisdn', 'like', '%' . $search . '%')
                    ->orWhere('label', 'like', '%' . $search . '%')
                    ->orWhere('asterisk_endpoint', 'like', '%' . $search . '%');
            });
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $simLines = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.calling.sim_lines.index', compact('simLines'));
    }

    public function update(Request $request, int $id)
    {
        $line = CallSimLine::findOrFail($id);

        $validated = $request->validate([
            'label'   => 'required|string|max:100',
            'msisdn'  => 'required|string|max:20',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        try {
            $validated['msisdn'] = $this->phones->toE164($validated['msisdn']);
        } catch (InvalidPhoneNumberException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $line->update([
            'label'   => $validated['label'],
            'msisdn'  => $validated['msisdn'],
            'user_id' => $validated['user_id'] ?? null,
        ]);

        return back()->with('success', __('SIM line updated.'));
    }

    public function toggle(int $id)
    {
        $line = CallSimLine::findOrFail($id);
        $line->update(['is_active' => !$line->is_active]);

        return back()->with(
            'success',
            $line->is_active ? __('SIM line activated.') : __('SIM line deactivated.')
        );
    }
}
