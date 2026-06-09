<?php

namespace App\Models\BEMS;

use Illuminate\Database\Eloquent\Model;

class TelemetryLog extends Model
{
    protected $guarded = []; 

    // Each log entry belongs to exactly one node
    public function node() {
        return $this->belongsTo(Node::class, 'node_id', 'id');
    }
}
