<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coding_session_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->text('content')->nullable();
            $table->json('tool_calls')->nullable();
            $table->json('tool_results')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamps();
        });
    }
};
