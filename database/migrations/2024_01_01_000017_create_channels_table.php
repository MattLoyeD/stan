<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('config');
            $table->string('pairing_token')->nullable()->unique();
            $table->boolean('is_active')->default(false);
            $table->timestamp('paired_at')->nullable();
            $table->timestamps();
        });
    }
};
