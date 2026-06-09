<?php

namespace App\Models\BEMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Client extends Model
{
    use HasFactory;

    protected $table = 'bems_clients';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function buildings()
    {
        return $this->hasMany(Building::class, 'client_id', 'id');
    }
}