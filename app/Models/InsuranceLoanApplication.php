<?php

namespace App\Models;

use Database\Factories\InsuranceLoanApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceLoanApplication extends Model
{
    /** @use HasFactory<InsuranceLoanApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'insurance_profile_id',
        'insurance_policy_id',
        'insurance_provider_id',
        'reviewed_by',
        'reference',
        'requested_amount',
        'sim_balance_at_application',
        'balance_verified_at_application',
        'disbursement_destination',
        'status',
        'repayment_months',
        'monthly_repayment',
        'disbursement_reference',
        'decision_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'sim_balance_at_application' => 'decimal:2',
            'balance_verified_at_application' => 'datetime',
            'monthly_repayment' => 'decimal:2',
            'reviewed_at' => 'datetime',
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

    public function provider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
