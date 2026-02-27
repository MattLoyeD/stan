<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objective_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('status')->default('planned');
            $table->text('reasoning')->nullable();
            $table->string('tool_name')->nullable();
            $table->json('tool_input')->nullable();
            $table->text('tool_output')->nullable();
            $table->text('observation')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }
};
