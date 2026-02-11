<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // For MySQL, we need to alter the enum column
        // Note: This approach works for MySQL/MariaDB
        DB::statement("ALTER TABLE `whatsapp_messages` MODIFY COLUMN `message_type` ENUM('text', 'image', 'document', 'audio', 'video', 'location', 'reaction', 'edit') DEFAULT 'text'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove 'edit' from enum
        DB::statement("ALTER TABLE `whatsapp_messages` MODIFY COLUMN `message_type` ENUM('text', 'image', 'document', 'audio', 'video', 'location', 'reaction') DEFAULT 'text'");
    }
};
