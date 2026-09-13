<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('activation_source')->nullable()->after('conversation_id')->index();
            $table->string('transition_reason')->nullable()->after('activation_source')->index();
            $table->unsignedBigInteger('previous_membership_id')->nullable()->after('transition_reason')->index();
        });

        Schema::table('membership_change_logs', function (Blueprint $table) {
            $table->string('reason')->nullable()->after('action');
            $table->unsignedBigInteger('previous_package_id')->nullable()->after('previous_package');
            $table->unsignedBigInteger('new_package_id')->nullable()->after('new_package');
        });
    }

    public function down()
    {
        Schema::table('membership_change_logs', function (Blueprint $table) {
            $table->dropColumn(['reason', 'previous_package_id', 'new_package_id']);
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn(['activation_source', 'transition_reason', 'previous_membership_id']);
        });
    }
};
