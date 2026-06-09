<?php

namespace App\Models\BEMS;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    protected $guarded = []; 

    // Add this to cast the column to a datetime object
    protected $casts = [
    'last_status_at' => 'datetime',
    'mqtt_retain'    => 'boolean',
];

    public function classroom() {
        return $this->belongsTo(Classroom::class, 'classroom_id', 'id');
    }
    
    public function telemetryLogs() {
        return $this->hasMany(TelemetryLog::class, 'node_id', 'id');
    }
}