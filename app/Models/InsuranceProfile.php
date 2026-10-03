<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceProfile extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'sim_balance',
        'coverage_goal',
        'risk_level',
        'monthly_premium',
        'coverage_amount',
        'recommended_plan',
        'ai_summary',
    ];

    protected function casts(): array
    {
        return [
            'sim_balance' => 'decimal:2',
            'balance_verified_at' => 'datetime',
            'monthly_premium' => 'decimal:2',
            'coverage_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
