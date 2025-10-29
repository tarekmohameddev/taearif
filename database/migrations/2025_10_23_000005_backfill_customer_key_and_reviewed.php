<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Populate customer_key for existing rows using phone from source tables
        $matches = DB::table('property_matches')->select('id','request_type','request_id')->whereNull('customer_key')->get();
        foreach ($matches as $m) {
            $phone = null;
            if ($m->request_type === 'web') {
                $row = DB::table('users_property_requests')->select('phone')->where('id', $m->request_id)->first();
                $phone = $row->phone ?? null;
            } elseif ($m->request_type === 'whatsapp') {
                $row = DB::table('api_customer_inquiry')->select('phone_number')->where('id', $m->request_id)->first();
                $phone = $row->phone_number ?? null;
            }
            $normalized = self::normalizePhone($phone);
            if ($normalized) {
                DB::table('property_matches')->where('id', $m->id)->update(['customer_key' => $normalized]);
            }
        }

        // Ensure is_reviewed default false where nulls exist
        DB::table('property_matches')->whereNull('is_reviewed')->update(['is_reviewed' => false]);
    }

    public function down(): void
    {
        // No-op: keep data
    }

    private static function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '') return null;
        // KSA normalization: handle leading 0, 966, +966 already removed
        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }
        return '966' . $digits;
    }
};


