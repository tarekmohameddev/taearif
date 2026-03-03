<?php

namespace App\Http\Controllers\Api\property;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\PropertyRequestFormSettings;
use App\Models\Api\UserPropertyRequestFieldSetting;
use App\Http\Requests\Api\Property\BulkUpsertPropertyRequestSettingsRequest;
use App\Http\Requests\Api\Property\ResetPropertyRequestSettingsRequest;
use App\Http\Requests\Api\Property\UpdateOnePropertyRequestSettingsRequest;

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

    public function bulkUpsert(BulkUpsertPropertyRequestSettingsRequest $request)
    {
        $tenantId = $this->tenantId();
        $payload = $request->validated();

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

    public function updateOne(string $field_key, UpdateOnePropertyRequestSettingsRequest $request)
    {
        $tenantId = $this->tenantId();
        abort_unless(in_array($field_key, $this->allowedKeys(), true), 422, 'Invalid field_key');

        $data = $request->validated();

        $setting = UserPropertyRequestFieldSetting::firstOrNew([
            'user_id'   => $tenantId,
            'field_key' => $field_key,
        ]);

        foreach (['is_visible','is_required','sort_order','label_ar','label_en','meta'] as $f) {
            if (array_key_exists($f, $data)) {
                $val = $data[$f];
                if (in_array($f, ['is_visible','is_required'])) {
                    $val = (bool) $val;
                }
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

    public function reset(ResetPropertyRequestSettingsRequest $request)
    {
        $tenantId = $this->tenantId();
        $validated = $request->validated();
        $keys = $validated['keys'] ?? null;

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
