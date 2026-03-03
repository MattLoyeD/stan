<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->boolean('is_swarm')->default(false)->after('llm_model');
            $table->json('swarm_config')->nullable()->after('is_swarm');
        });
    }
};
