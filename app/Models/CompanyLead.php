<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyLead extends Model
{
    public const WEBSITE_STATUSES = [
        'unknown' => 'Nepoznato',
        'no_website' => 'Nema sajta',
        'active' => 'Sajt aktivan',
        'inactive' => 'Sajt neaktivan',
        'error' => 'Greška pri proveri',
    ];

    public const LEAD_STATUSES = [
        'new' => 'Nov',
        'in_progress' => 'U obradi',
        'contacted' => 'Kontaktiran',
        'qualified' => 'Kvalifikovan',
        'rejected' => 'Odbijen',
        'converted' => 'Konvertovan',
    ];
    protected $fillable = [
        'company_id',
        'website_url',
        'website_status',
        'phone',
        'email',
        'lead_status',
        'lead_score',
        'notes',
        'website_checked_at',
        'contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'lead_score' => 'integer',
            'website_checked_at' => 'datetime',
            'contacted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function websiteAudits(): HasMany
    {
        return $this->hasMany(CompanyWebsiteAudit::class);
    }
}
