<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    protected $fillable = [
        'classroom_id', 'day', 'time_start', 'time_end',
        'subject', 'sks', 'lecturer', 'type',
    ];

    public function classroom()
    {
        return $this->belongsTo(\App\Models\BEMS\Classroom::class);
    }
}
