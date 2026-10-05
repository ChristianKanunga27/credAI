<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderService extends Model
{
    protected $fillable = [
        'insurance_provider_id',
        'name',
        'description',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InsurancePayment::class);
    }
}
