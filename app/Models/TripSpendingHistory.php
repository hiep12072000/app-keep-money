<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripSpendingHistory extends Model
{
    protected $table = 'trip_spending_history';

    protected $fillable = [
        'trip_id',
        'name',
        'total_amount',
        'is_balance',
        'note',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'is_balance' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the trip that owns this spending history
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }

    /**
     * Get the user who created this spending history
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
