<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkShare extends Model
{
    protected $table = 'link_shares';

    protected $fillable = [
        'group_id',
        'code',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(GroupChat::class, 'group_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
