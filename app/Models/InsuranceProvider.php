<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceProvider extends Model
{
    protected $fillable = [
        'user_id',
        'organization_name',
        'license_number',
        'provider_type',
        'available_funds',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ProviderService::class, 'insurance_provider_id');
    }
}
