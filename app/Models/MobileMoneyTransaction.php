<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileMoneyTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'reference',
        'amount',
        'transaction_type',
        'occurred_at',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
