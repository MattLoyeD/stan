<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_step_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('session_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tool_name');
            $table->string('tool_category');
            $table->string('risk_level');
            $table->json('input');
            $table->text('output')->nullable();
            $table->boolean('was_sandboxed')->default(false);
            $table->boolean('was_approved')->default(false);
            $table->string('approval_method')->nullable();
            $table->boolean('guardian_passed')->default(false);
            $table->text('guardian_reason')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->integer('exit_code')->nullable();
            $table->timestamps();
        });
    }
};
