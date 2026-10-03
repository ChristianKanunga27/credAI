<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
