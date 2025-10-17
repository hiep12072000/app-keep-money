<?php

namespace App\Models\APP_KEEP_MONEY;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupChat extends Model
{
    use SoftDeletes;

    protected $table = 'akm_group_chat';

    protected $fillable = [
        'name',
        'type',
        'created_by',
        'is_private',
        'avatar',
        'is_seen',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_seen' => 'boolean',
    ];

    /**
     * Get the user who created the group chat
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the members of the group chat
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'group_chat_user', 'group_chat_id', 'user_id');
    }
}
