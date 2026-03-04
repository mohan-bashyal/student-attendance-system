<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_uuid',
        'plan',
        'currency',
        'amount',
        'stripe_session_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_payment_status',
        'status',
        'registration_token',
        'token_expires_at',
        'paid_at',
        'used_at',
        'school_id',
        'admin_user_id',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'used_at' => 'datetime',
            'amount' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
