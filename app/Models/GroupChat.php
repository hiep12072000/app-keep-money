<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupChat extends Model
{
    use SoftDeletes;

    protected $table = 'group_chat';

    protected $fillable = [
        'name',
        'type',
        'created_by',
        'is_private',
        'avatar',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created this group
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get group members through the pivot table group_chat_user
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'group_chat_user', 'group_chat_id', 'user_id')
                    ->withPivot('is_admin')
                    ->withTimestamps();
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? url('storage/' . $this->avatar) : null;
    }
}
