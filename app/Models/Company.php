<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $fillable = [
        'registration_number',
        'business_name',
        'municipality_code',
        'municipality_name',
        'status',
        'founded_at',
        'legal_form',
        'activity_code',
        'source_snapshot_date',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'founded_at' => 'date',
            'source_snapshot_date' => 'date',
            'imported_at' => 'datetime',
        ];
    }

    public function lead(): HasOne
    {
        return $this->hasOne(CompanyLead::class);
    }

    public function companyDataPoints(): HasMany
    {
        return $this->hasMany(CompanyDataPoint::class);
    }
}
