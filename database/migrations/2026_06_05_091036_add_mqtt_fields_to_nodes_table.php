<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->string('mqtt_topic')->nullable()->after('purpose');   // e.g., "bems/gedungA/ruang101"
            $table->unsignedTinyInteger('mqtt_qos')->default(0)->after('mqtt_topic'); // 0, 1, or 2
            $table->boolean('mqtt_retain')->default(false)->after('mqtt_qos');
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn(['mqtt_topic', 'mqtt_qos', 'mqtt_retain']);
        });
    }
};