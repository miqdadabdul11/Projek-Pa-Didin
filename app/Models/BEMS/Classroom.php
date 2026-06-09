<?php

namespace App\Models\BEMS;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $guarded = []; 

    // Each Classroom belongs to one Building
    public function building() {
        return $this->belongsTo(Building::class, 'building_id', 'id');
    }
    public function nodes() {
        return $this->hasMany(Node::class, 'classroom_id', 'id');
    }
}