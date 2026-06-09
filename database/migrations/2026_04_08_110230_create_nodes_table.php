<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            
            // Static Hardware Configuration (Admin Inputs)
            $table->string('name'); // e.g., "Suhu & Kelembaban Ruang 101"
            $table->string('microcontroller_chip')->nullable(); // e.g., "ESP32", "Arduino Nano 33 IoT"
            $table->text('purpose')->nullable(); 

            // Dynamic Telemetry Data (Updated via API / curl)
            $table->string('sensor_reading')->nullable(); 
            $table->string('battery')->nullable(); 
            $table->string('uptime')->nullable(); 
            $table->timestamp('last_status_at')->nullable(); // Tracks when the last packet was received
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
