<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swarm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->text('instructions');
            $table->text('goal');
            $table->json('allowed_tools')->nullable();
            $table->string('status')->default('pending');
            $table->integer('token_budget')->default(0);
            $table->integer('tokens_used')->default(0);
            $table->integer('sequence')->default(0);
            $table->json('depends_on')->nullable();
            $table->text('result')->nullable();
            $table->text('error')->nullable();
            $table->string('llm_provider')->nullable();
            $table->string('llm_model')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }
};
