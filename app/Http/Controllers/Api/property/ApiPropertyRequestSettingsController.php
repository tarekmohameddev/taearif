<?php

namespace App\Http\Controllers\Api\property;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\PropertyRequestFormSettings;
use App\Models\Api\UserPropertyRequestFieldSetting;

class ApiPropertyRequestSettingsController extends Controller
{
    public function __construct(private PropertyRequestFormSettings $formSettings) {}

    protected function ok(array $data = [], int $status = 200)
    {
        return response()->json(['status' => 'success', 'data' => $data], $status);
    }

    protected function tenantId(): int
    {
        $user = auth()->user();
        abort_if(!$user, 404, 'Tenant not found');
        return (int) $user->id;
    }

    protected function allowedKeys(): array
    {
        return array_keys(PropertyRequestFormSettings::defaultMap());
    }

    public function index(Request $request)
    {
        $tenantId = $this->tenantId();
        $merged = filter_var($request->query('merged', true), FILTER_VALIDATE_BOOLEAN);

        if ($merged) {
            return $this->ok([
                'tenant_id'    => $tenantId,
                'allowed_keys' => $this->allowedKeys(),
                'settings'     => $this->formSettings->forTenant($tenantId),
            ]);
        }

        $rows = UserPropertyRequestFieldSetting::where('user_id', $tenantId)->get();

        return $this->ok([
            'tenant_id'    => $tenantId,
            'allowed_keys' => $this->allowedKeys(),
            'settings'     => $rows,
        ]);
    }

    public function defaults()
    {
        return $this->ok([
            'allowed_keys' => $this->allowedKeys(),
            'defaults'     => PropertyRequestFormSettings::defaultMap(),
        ]);
    }

    public function bulkUpsert(Request $request)
    {
        $tenantId = $this->tenantId();

        $payload = $request->validate([
            'items'                 => ['required','array','min:1'],
            'items.*.field_key'     => ['required','string', Rule::in($this->allowedKeys())],
            'items.*.is_visible'    => ['nullable','boolean'],
            'items.*.is_required'   => ['nullable','boolean'],
            'items.*.sort_order'    => ['nullable','integer'],
            'items.*.label_ar'      => ['nullable','string','max:255'],
            'items.*.label_en'      => ['nullable','string','max:255'],
            'items.*.meta'          => ['nullable','array'],
        ]);

        $now  = Carbon::now();
        $rows = [];
        foreach ($payload['items'] as $it) {
            $rows[] = [
                'user_id'     => $tenantId,
                'field_key'   => $it['field_key'],
                'is_visible'  => array_key_exists('is_visible', $it)  ? (int) (bool) $it['is_visible']  : 1,
                'is_required' => array_key_exists('is_required', $it) ? (int) (bool) $it['is_required'] : 0,
                'sort_order'  => $it['sort_order'] ?? null,
                'label_ar'    => $it['label_ar']   ?? null,
                'label_en'    => $it['label_en']   ?? null,
                'meta'        => $it['meta']       ?? null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        DB::transaction(function () use ($rows) {
            UserPropertyRequestFieldSetting::upsert(
                $rows,
                ['user_id','field_key'],
                ['is_visible','is_required','sort_order','label_ar','label_en','meta','updated_at']
            );
        });

        cache()->forget("pr_form_settings.tenant.$tenantId");

        return $this->ok([
            'tenant_id' => $tenantId,
            'settings'  => $this->formSettings->forTenant($tenantId),
        ]);
    }

    public function updateOne(string $field_key, Request $request)
    {
        $tenantId = $this->tenantId();
        abort_unless(in_array($field_key, $this->allowedKeys(), true), 422, 'Invalid field_key');

        $data = $request->validate([
            'is_visible'    => ['nullable','boolean'],
            'is_required'   => ['nullable','boolean'],
            'sort_order'    => ['nullable','integer'],
            'label_ar'      => ['nullable','string','max:255'],
            'label_en'      => ['nullable','string','max:255'],
            'meta'          => ['nullable','array'],
        ]);

        $setting = UserPropertyRequestFieldSetting::firstOrNew([
            'user_id'   => $tenantId,
            'field_key' => $field_key,
        ]);

        foreach (['is_visible','is_required','sort_order','label_ar','label_en','meta'] as $f) {
            if ($request->has($f)) {
                $val = $data[$f];
                if (in_array($f, ['is_visible','is_required'])) $val = (bool) $val;
                $setting->{$f} = $val;
            }
        }

        $setting->save();
        cache()->forget("pr_form_settings.tenant.$tenantId");

        return $this->ok([
            'tenant_id' => $tenantId,
            'setting'   => $setting,
        ]);
    }

    public function reset(Request $request)
    {
        $tenantId = $this->tenantId();
        $keys = $request->input('keys');

        if (is_array($keys) && !empty($keys)) {
            UserPropertyRequestFieldSetting::where('user_id', $tenantId)
                ->whereIn('field_key', $keys)->delete();
        } else {
            UserPropertyRequestFieldSetting::where('user_id', $tenantId)->delete();
        }

        cache()->forget("pr_form_settings.tenant.$tenantId");

        return $this->ok([
            'tenant_id' => $tenantId,
            'settings'  => $this->formSettings->forTenant($tenantId),
        ]);
    }
}
