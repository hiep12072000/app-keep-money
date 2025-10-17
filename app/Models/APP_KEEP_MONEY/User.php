<?php

namespace App\Models\APP_KEEP_MONEY;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'akm_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'full_name', 'email', 'phone', 'password', 'avatar', 'can_change_password', 'token_fcm', 'is_online', 'last_online_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_online' => 'boolean',
        'last_online_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get friend requests sent by this user
     */
    public function sentFriendRequests()
    {
        return $this->hasMany(Friend::class, 'sender_id');
    }

    /**
     * Get friend requests received by this user
     */
    public function receivedFriendRequests()
    {
        return $this->hasMany(Friend::class, 'receiver_id');
    }

    /**
     * Get all friends of this user (accepted friend requests)
     */
    public function friends()
    {
        return $this->hasMany(Friend::class, 'sender_id')
                   ->where('status', 'ACCEPT')
                   ->orWhere(function($query) {
                       $query->where('receiver_id', $this->id)
                             ->where('status', 'ACCEPT');
                   });
    }
}
