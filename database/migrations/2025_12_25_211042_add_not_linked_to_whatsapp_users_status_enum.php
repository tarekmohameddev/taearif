<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddNotLinkedToWhatsappUsersStatusEnum extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to use a raw query to update the ENUM
        DB::statement("ALTER TABLE whatsapp_users MODIFY COLUMN status ENUM('active', 'inactive', 'blocked', 'not_linked') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM values
        // Note: Any records with 'not_linked' might need to be converted back
        DB::table('whatsapp_users')->where('status', 'not_linked')->update(['status' => 'inactive']);
        DB::statement("ALTER TABLE whatsapp_users MODIFY COLUMN status ENUM('active', 'inactive', 'blocked') DEFAULT 'active'");
    }
}
