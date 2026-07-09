<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heartbeat_monitor_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->unsignedInteger('expected_every_seconds');
            $table->unsignedInteger('grace_seconds')->default(60);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();

            $table->index('last_heartbeat_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heartbeat_monitor_configs');
    }
};
