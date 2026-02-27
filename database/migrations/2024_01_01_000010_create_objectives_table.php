<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('goal');
            $table->json('constraints')->nullable();
            $table->json('allowed_tools')->nullable();
            $table->string('status')->default('pending');
            $table->integer('token_budget')->default(100000);
            $table->integer('tokens_used')->default(0);
            $table->string('llm_provider')->nullable();
            $table->string('llm_model')->nullable();
            $table->text('result_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }
};
