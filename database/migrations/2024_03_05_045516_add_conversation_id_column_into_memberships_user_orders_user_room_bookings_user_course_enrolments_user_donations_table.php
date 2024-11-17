<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add conversation_id to memberships table
        Schema::table('memberships', function (Blueprint $table) {
            if (!Schema::hasColumn('memberships', 'conversation_id')) {
                $table->string('conversation_id')->nullable();
            }
        });

        // add conversation_id to user_orders table
        Schema::table('user_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('user_orders', 'conversation_id')) {
                $table->string('conversation_id')->nullable();
            }
        });

        // add conversation_id to user_room_bookings table
        Schema::table('user_room_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('user_room_bookings', 'conversation_id')) {
                $table->string('conversation_id')->nullable();
            }
        });
        // add conversation_id to user_course_enrolments table
        Schema::table('user_course_enrolments', function (Blueprint $table) {
            if (!Schema::hasColumn('user_course_enrolments', 'conversation_id')) {
                $table->string('conversation_id')->nullable();
            }
        });
        // add conversation_id to user_donations table
        Schema::table('user_donation_details', function (Blueprint $table) {
            if (!Schema::hasColumn('user_donation_details', 'conversation_id')) {
                $table->string('conversation_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // drop conversation_id to memberships table
        Schema::table('memberships', function (Blueprint $table) {
            if (Schema::hasColumn('memberships', 'conversation_id')) {
                $table->dropColumn('conversation_id');
            }
        });

        // drop conversation_id from user_orders table
        Schema::table('user_orders', function (Blueprint $table) {
            if (Schema::hasColumn('user_orders', 'conversation_id')) {
                $table->dropColumn('conversation_id');
            }
        });

        // drop conversation_id from user_room_bookings table
        Schema::table('user_room_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('user_room_bookings', 'conversation_id')) {
                $table->dropColumn('conversation_id');
            }
        });
        // drop conversation_id from user_course_enrolments table
        Schema::table('user_course_enrolments', function (Blueprint $table) {
            if (Schema::hasColumn('user_course_enrolments', 'conversation_id')) {
                $table->dropColumn('conversation_id');
            }
        });
        // drop conversation_id from user_donation_details table
        Schema::table('user_donation_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_donation_details', 'conversation_id')) {
                $table->dropColumn('conversation_id');
            }
        });
    }
};
