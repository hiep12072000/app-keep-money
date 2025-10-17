<?php

namespace App\Models\APP_KEEP_MONEY;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'akm_messages';

    protected $fillable = [
        'user_id',
        'group_chat_id',
        'content',
        'type',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function groupChat()
    {
        return $this->belongsTo(GroupChat::class, 'group_chat_id');
    }
}
