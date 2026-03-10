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
        // Extend whatsapp_messages.message_type enum to include "contacts"
        DB::statement("ALTER TABLE `whatsapp_messages` MODIFY COLUMN `message_type` ENUM('text', 'image', 'document', 'audio', 'video', 'location', 'reaction', 'edit', 'contacts') DEFAULT 'text'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove "contacts" from enum
        DB::statement("ALTER TABLE `whatsapp_messages` MODIFY COLUMN `message_type` ENUM('text', 'image', 'document', 'audio', 'video', 'location', 'reaction', 'edit') DEFAULT 'text'");
    }
};

