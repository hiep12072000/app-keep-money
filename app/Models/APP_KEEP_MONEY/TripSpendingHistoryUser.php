<?php

namespace App\Models\APP_KEEP_MONEY;

use Illuminate\Database\Eloquent\Model;

class TripSpendingHistoryUser extends Model
{
    protected $table = 'akm_trip_spending_history_users';

    public $timestamps = false;

    protected $fillable = [
        'trip_spending_history_id',
        'user_id',
        'amount',
        'is_balance',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_balance' => 'boolean',
    ];

    /**
     * Get the spending history that owns this user
     */
    public function spendingHistory()
    {
        return $this->belongsTo(TripSpendingHistory::class, 'trip_spending_history_id');
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
