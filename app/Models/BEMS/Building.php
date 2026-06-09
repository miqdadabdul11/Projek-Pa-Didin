<?php

namespace App\Models\BEMS;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $guarded = []; // Allows mass assignment

    public function client() {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }
    
    public function classrooms() {
        return $this->hasMany(Classroom::class, 'building_id', 'id');
    }
}