<?php
namespace App\Models\BEMS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ClassroomBooking extends Model {
    protected $fillable = ['classroom_id','user_id','date','time_start','time_end','purpose','status','reject_reason'];
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
