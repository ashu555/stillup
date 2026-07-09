<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('http_monitor_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('method', 10)->default('GET');
            $table->unsignedSmallInteger('expected_status')->default(200);
            $table->unsignedSmallInteger('timeout_seconds')->default(10);
            $table->string('keyword')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('http_monitor_configs');
    }
};
