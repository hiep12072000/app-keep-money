<?php

namespace App\Models\APP_KEEP_MONEY;

use Illuminate\Database\Eloquent\Model;

class TripUser extends Model
{

    protected $table = 'akm_trip_users';

    protected $fillable = [
        'user_id',
        'trip_id',
        'advance',
    ];

    protected $casts = [
        'advance' => 'decimal:0', // Phù hợp với DECIMAL(10, 0) trong database
    ];

    /**
     * Get the advance attribute with proper null handling
     */
    public function getAdvanceAttribute($value)
    {
        return $value !== null ? (float) $value : null;
    }

    /**
     * Relationship với User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship với Trip
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }
}

