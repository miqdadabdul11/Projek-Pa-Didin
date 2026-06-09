<?php

namespace App\Models\BEMS;

use Illuminate\Database\Eloquent\Model;

class MqttBroker extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'mqtt_retain' => 'boolean',
    ];

    // Pastikan hanya 1 broker aktif
    public static function setActive(int $id): void
    {
        self::query()->update(['is_active' => false]);
        self::find($id)?->update(['is_active' => true]);
    }

    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }
}