<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsurancePolicy extends Model
{
    protected $fillable = [
        'user_id',
        'insurance_provider_id',
        'insurance_profile_id',
        'policy_number',
        'coverage_goal',
        'premium',
        'coverage_amount',
        'status',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'premium' => 'decimal:2',
            'coverage_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(InsuranceProfile::class, 'insurance_profile_id');
    }
}
