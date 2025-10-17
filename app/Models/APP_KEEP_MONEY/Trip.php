<?php

namespace App\Models\APP_KEEP_MONEY;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $table = 'akm_trips';

    protected $fillable = [
        'name',
        'group_chat_id',
        'status',
        'created_by',
        'key_member_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created this trip
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the key member of the trip
     */
    public function keyMember()
    {
        return $this->belongsTo(User::class, 'key_member_id');
    }

    /**
     * Get the group chat associated with this trip
     */
    public function groupChat()
    {
        return $this->belongsTo(GroupChat::class, 'group_chat_id');
    }

    /**
     * Get trip spending history
     */
    public function spendingHistory()
    {
        return $this->hasMany(TripSpendingHistory::class, 'trip_id');
    }

    /**
     * Get trip users through trip_users table
     */
    public function tripUsers()
    {
        return $this->hasMany(TripUser::class, 'trip_id');
    }

    /**
     * Get users who are members of this trip
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'akm_trip_users', 'trip_id', 'user_id')
                    ->withPivot('advance');
    }

    /**
     * Get group members through group_chat (legacy)
     */
    public function groupMembers()
    {
        return $this->hasManyThrough(
            User::class,
            GroupChat::class,
            'id', // Foreign key on group_chat table
            'id', // Foreign key on users table
            'group_chat_id', // Local key on trips table
            'id' // Local key on group_chat table
        );
    }
}
