<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripPayer extends Model
{
    protected $table = 'trip_payer';
    
    public $timestamps = false;

    protected $fillable = [
        'trip_spending_history_id',
        'user_id',
        'payment_amount',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
    ];

    /**
     * Get the spending history that owns this payer
     */
    public function spendingHistory()
    {
        return $this->belongsTo(TripSpendingHistory::class, 'trip_spending_history_id');
    }

    /**
     * Get the user who paid
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
