<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_server_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('transport'); // stdio or sse
            $table->string('command')->nullable(); // stdio: binary path
            $table->json('args')->nullable(); // stdio: CLI args
            $table->json('env')->nullable(); // stdio: env vars
            $table->string('url')->nullable(); // sse: endpoint
            $table->text('api_key')->nullable(); // encrypted
            $table->string('default_risk_level')->default('high');
            $table->json('tool_overrides')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('cached_tools')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });
    }
};
