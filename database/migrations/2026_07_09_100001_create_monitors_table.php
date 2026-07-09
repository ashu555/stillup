<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('interval_seconds')->default(60);
            $table->string('status')->default('pending');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_status_change_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_enabled', 'status']);
            $table->index(['last_checked_at', 'interval_seconds']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
