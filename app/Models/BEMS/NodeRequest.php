<?php

namespace App\Models\BEMS;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class NodeRequest extends Model
{
    protected $fillable = ['node_id', 'user_id', 'action', 'status'];

    public function node() {
        return $this->belongsTo(Node::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}