<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyLead extends Model
{
    public const WEBSITE_STATUS_UNKNOWN = 'unknown';
    public const WEBSITE_STATUS_ACTIVE = 'active';

    public const WEBSITE_STATUSES = [
        self::WEBSITE_STATUS_UNKNOWN => 'Nepoznato',
        'no_website' => 'Nema sajta',
        self::WEBSITE_STATUS_ACTIVE => 'Sajt aktivan',
        'inactive' => 'Sajt neaktivan',
        'error' => 'Greška pri proveri',
    ];

    public const LEAD_STATUS_NEW = 'new';
    public const LEAD_STATUS_CONTACTED = 'contacted';

    public const LEAD_STATUSES = [
        self::LEAD_STATUS_NEW => 'Nov',
        'in_progress' => 'U obradi',
        self::LEAD_STATUS_CONTACTED => 'Kontaktiran',
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
