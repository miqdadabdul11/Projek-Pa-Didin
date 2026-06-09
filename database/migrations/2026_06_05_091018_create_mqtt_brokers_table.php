<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mqtt_brokers', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // e.g., "Broker Utama Gedung A"
            $table->string('host');                          // e.g., "broker.hivemq.com"
            $table->unsignedSmallInteger('port')->default(1883);
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(false);   // Hanya 1 yang aktif
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mqtt_brokers');
    }
};