<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class CompanyDataPoint extends Model
{
    public const FIELD_WEBSITE = 'website';

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_COMPANYWALL = 'companywall';
    public const SOURCE_OPENSTREETMAP = 'openstreetmap';
    public const SOURCE_WIKIDATA = 'wikidata';
    public const SOURCE_GOOGLE_PLACES = 'google_places';
    public const SOURCE_APR = 'apr';

    public const CONFIDENCE_HIGH = 'high';
    public const CONFIDENCE_MEDIUM = 'medium';
    public const CONFIDENCE_LOW = 'low';

    public const LICENSE_ALLOWED = 'allowed';
    public const LICENSE_RESTRICTED = 'restricted';
    public const LICENSE_FORBIDDEN = 'forbidden';
    public const LICENSE_UNKNOWN = 'unknown';

    public const STATUS_CANDIDATE = 'candidate';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_UNMATCHED = 'unmatched';
    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const IDENTIFIER_REGISTRATION_NUMBER = 'registration_number';
    public const IDENTIFIER_PIB = 'pib';
    public const IDENTIFIER_PLACE_ID = 'place_id';
    public const IDENTIFIER_NAME_ADDRESS = 'name_address';

    protected $fillable = [
        'company_id',
        'external_identifier',
        'identifier_type',
        'field',
        'value',
        'source',
        'source_url',
        'confidence',
        'identity_match_score',
        'data_license_status',
        'status',
        'retrieved_at',
        'verified_at',
        'verified_by',
        'notes',
    ];

    protected $casts = [
        'identity_match_score' => 'integer',
        'retrieved_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function fields(): array
    {
        return [
            self::FIELD_WEBSITE,
        ];
    }

    public static function sources(): array
    {
        return [
            self::SOURCE_MANUAL,
            self::SOURCE_COMPANYWALL,
            self::SOURCE_OPENSTREETMAP,
            self::SOURCE_WIKIDATA,
            self::SOURCE_GOOGLE_PLACES,
            self::SOURCE_APR,
        ];
    }

    public static function confidences(): array
    {
        return [
            self::CONFIDENCE_HIGH,
            self::CONFIDENCE_MEDIUM,
            self::CONFIDENCE_LOW,
        ];
    }

    public static function licenseStatuses(): array
    {
        return [
            self::LICENSE_ALLOWED,
            self::LICENSE_RESTRICTED,
            self::LICENSE_FORBIDDEN,
            self::LICENSE_UNKNOWN,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_CANDIDATE,
            self::STATUS_VERIFIED,
            self::STATUS_REJECTED,
            self::STATUS_UNMATCHED,
            self::STATUS_NEEDS_REVIEW,
        ];
    }

    public static function identifierTypes(): array
    {
        return [
            self::IDENTIFIER_REGISTRATION_NUMBER,
            self::IDENTIFIER_PIB,
            self::IDENTIFIER_PLACE_ID,
            self::IDENTIFIER_NAME_ADDRESS,
        ];
    }

    public static function validationRules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'external_identifier' => ['nullable', 'string', 'max:255'],
            'identifier_type' => [
                'nullable',
                'string',
                Rule::in(self::identifierTypes()),
            ],
            'field' => ['required', 'string', Rule::in(self::fields())],
            'value' => ['required', 'string', 'max:2000'],
            'source' => ['required', 'string', Rule::in(self::sources())],
            'source_url' => ['nullable', 'string', 'max:500'],
            'confidence' => ['required', 'string', Rule::in(self::confidences())],
            'identity_match_score' => ['required', 'integer', 'min:0', 'max:100'],
            'data_license_status' => [
                'required',
                'string',
                Rule::in(self::licenseStatuses()),
            ],
            'status' => ['required', 'string', Rule::in(self::statuses())],
            'retrieved_at' => ['nullable', 'date'],
            'verified_at' => ['nullable', 'date'],
            'verified_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
