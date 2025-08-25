<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('project_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('project_id');
            $t->string('action', 40);
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->enum('actor_type', ['employee','tenant','system'])->default('tenant');
            $t->ipAddress()->nullable();
            $t->string('user_agent')->nullable();
            $t->json('changes')->nullable();
            $t->string('note')->nullable();
            $t->timestamps();

            $t->index(['tenant_id','project_id']);
            $t->index(['tenant_id','action']);
        });
    }
    public function down(): void { Schema::dropIfExists('project_logs'); }
};
