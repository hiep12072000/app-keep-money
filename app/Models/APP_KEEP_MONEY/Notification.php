<?php

namespace App\Models\APP_KEEP_MONEY;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'akm_notifications';

    protected $fillable = [
        'receive_user_id',
        'content',
        'params',
        'type',
        'title',
        'is_seen',
    ];

    protected $casts = [
        'params' => 'array',
        'is_seen' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who received this notification
     */
    public function receiveUser()
    {
        return $this->belongsTo(User::class, 'receive_user_id');
    }
}

