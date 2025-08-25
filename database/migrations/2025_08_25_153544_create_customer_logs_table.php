<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customer_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id');               // the account / owner
            $t->unsignedBigInteger('customer_id');
            $t->string('action', 40);                          // created|updated|deleted|restored|custom
            $t->unsignedBigInteger('actor_id')->nullable();    // who did it
            $t->enum('actor_type', ['employee','tenant','system'])->default('tenant');
            $t->ipAddress()->nullable();
            $t->string('user_agent')->nullable();
            $t->json('changes')->nullable();                   // {before:{}, after:{}}
            $t->string('note')->nullable();                    // custom messages
            $t->timestamps();

            $t->index(['tenant_id','customer_id']);
            $t->index(['tenant_id','action']);
        });
    }
    public function down(): void { Schema::dropIfExists('customer_logs'); }
};
