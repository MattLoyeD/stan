<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('version');
            $table->string('source');
            $table->text('description')->nullable();
            $table->json('required_permissions')->nullable();
            $table->text('signature')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });
    }
};
