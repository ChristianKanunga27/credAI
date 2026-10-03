<?php

namespace App\Models;

use Database\Factories\InsurancePaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsurancePayment extends Model
{
    /** @use HasFactory<InsurancePaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'insurance_profile_id',
        'insurance_policy_id',
        'reference',
        'mobile_money_provider',
        'phone',
        'amount',
        'currency',
        'status',
        'provider_reference',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(InsuranceProfile::class, 'insurance_profile_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'insurance_policy_id');
    }
}
