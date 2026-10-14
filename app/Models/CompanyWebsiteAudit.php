<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyWebsiteAudit extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
    ];

    protected $fillable = [
        'company_lead_id',
        'requested_url',
        'final_url',
        'status',
        'audit_version',
        'http_status',
        'is_reachable',
        'has_https',
        'redirect_count',
        'response_time_ms',
        'page_title',
        'meta_description',
        'notes',
        'reviewed_at',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'audit_version' => 'v1',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'is_reachable' => 'boolean',
            'has_https' => 'boolean',
            'redirect_count' => 'integer',
            'response_time_ms' => 'integer',
            'reviewed_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CompanyWebsiteAudit $audit) {
            $audit->validateStatus($audit->status);
        });

        static::updating(function (CompanyWebsiteAudit $audit) {
            $audit->validateStatus($audit->status);
        });
    }

    public function companyLead(): BelongsTo
    {
        return $this->belongsTo(CompanyLead::class);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    private function validateStatus(?string $status): void
    {
        if ($status !== null && ! in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException(
                "Invalid website audit status [{$status}]."
            );
        }
    }
}
