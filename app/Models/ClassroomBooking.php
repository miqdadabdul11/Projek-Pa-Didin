<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassroomBooking extends Model
{
    protected $fillable = [
        'classroom_id', 'user_id', 'date', 'time_start',
        'time_end', 'purpose', 'status', 'reject_reason',
    ];

    public function classroom()
    {
        return $this->belongsTo(\App\Models\BEMS\Classroom::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
