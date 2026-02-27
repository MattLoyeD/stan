<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tool_name');
            $table->string('permission_level');
            $table->json('allowed_patterns')->nullable();
            $table->json('blocked_patterns')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
