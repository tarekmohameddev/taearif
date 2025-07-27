<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateUsersReferralCode extends Migration
{
    public function up()
    {
        // Update users in chunks to avoid memory issues if the table is large
        DB::table('users')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                do {
                    $code = Str::upper(Str::random(8));
                } while (DB::table('users')->where('referral_code', $code)->exists());

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['referral_code' => $code]);
            }
        });
    }

    public function down()
    {
        // If needed, you can reset referral_code to null
        DB::table('users')->update(['referral_code' => null]);
    }
}
