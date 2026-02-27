<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coding_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('project_path');
            $table->string('status')->default('active');
            $table->integer('token_budget')->default(200000);
            $table->integer('tokens_used')->default(0);
            $table->string('llm_provider')->nullable();
            $table->string('llm_model')->nullable();
            $table->json('sandbox_config')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }
};
