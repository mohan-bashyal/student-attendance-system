<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    public const BASIC_MAX_STUDENTS = 500;
    public const PRO_MAX_STUDENTS = 2000;
    public const ENTERPRISE_MAX_STUDENTS = 10000;

    public const SUBSCRIPTION_PLAN_BASIC = 'basic';
    public const SUBSCRIPTION_PLAN_PRO = 'pro';
    public const SUBSCRIPTION_PLAN_ENTERPRISE = 'enterprise';

    public const SUBSCRIPTION_STATUS_TRIAL = 'trial';
    public const SUBSCRIPTION_STATUS_ACTIVE = 'active';
    public const SUBSCRIPTION_STATUS_PAST_DUE = 'past_due';
    public const SUBSCRIPTION_STATUS_CANCELLED = 'cancelled';

    public const SUBSCRIPTION_PLANS = [
        self::SUBSCRIPTION_PLAN_BASIC,
        self::SUBSCRIPTION_PLAN_PRO,
        self::SUBSCRIPTION_PLAN_ENTERPRISE,
    ];

    public const ENABLED_SUBSCRIPTION_PLANS = [
        self::SUBSCRIPTION_PLAN_BASIC,
        self::SUBSCRIPTION_PLAN_PRO,
        self::SUBSCRIPTION_PLAN_ENTERPRISE,
    ];

    public const SUBSCRIPTION_STATUSES = [
        self::SUBSCRIPTION_STATUS_TRIAL,
        self::SUBSCRIPTION_STATUS_ACTIVE,
        self::SUBSCRIPTION_STATUS_PAST_DUE,
        self::SUBSCRIPTION_STATUS_CANCELLED,
    ];

    protected $fillable = [
        'name',
        'code',
        'domain',
        'stripe_customer_id',
        'stripe_subscription_id',
        'is_active',
        'subscription_plan',
        'subscription_status',
        'subscription_ends_at',
        'max_students',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'subscription_ends_at' => 'date',
            'max_students' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(SchoolDevice::class);
    }

    public function isBasicPlan(): bool
    {
        return $this->subscription_plan === self::SUBSCRIPTION_PLAN_BASIC;
    }
}
