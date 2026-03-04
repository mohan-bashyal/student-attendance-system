<?php

namespace App\Services\PublicSite;

use App\Models\School;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionCheckoutService
{
    public const PLAN_PRICES = [
        School::SUBSCRIPTION_PLAN_BASIC => ['label' => 'Basic', 'amount' => 2900, 'currency' => 'usd'],
        School::SUBSCRIPTION_PLAN_PRO => ['label' => 'Pro', 'amount' => 6900, 'currency' => 'usd'],
        School::SUBSCRIPTION_PLAN_ENTERPRISE => ['label' => 'Enterprise', 'amount' => 14900, 'currency' => 'usd'],
    ];

    public function landingData(): array
    {
        return [
            'plans' => self::PLAN_PRICES,
            'publicKey' => (string) config('services.stripe.key', ''),
            'demoVideoUrl' => (string) env('DEMO_VIDEO_URL', ''),
        ];
    }

    public function createCheckoutSession(string $plan, string $successUrl, string $cancelUrl): string
    {
        $price = self::PLAN_PRICES[$plan] ?? null;
        if (! $price) {
            throw ValidationException::withMessages([
                'plan' => 'Invalid plan selected.',
            ]);
        }

        $stripeSecret = (string) config('services.stripe.secret', '');
        if ($stripeSecret === '') {
            throw ValidationException::withMessages([
                'plan' => 'Stripe is not configured. Please set STRIPE_SECRET key.',
            ]);
        }

        $order = SubscriptionOrder::query()->create([
            'order_uuid' => (string) Str::uuid(),
            'plan' => $plan,
            'currency' => $price['currency'],
            'amount' => (int) $price['amount'],
            'status' => 'pending',
            'stripe_payment_status' => 'unpaid',
        ]);

        $response = Http::asForm()
            ->withBasicAuth($stripeSecret, '')
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'customer_creation' => 'always',
                'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}&order='.$order->order_uuid,
                'cancel_url' => $cancelUrl,
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => $price['currency'],
                'line_items[0][price_data][unit_amount]' => (int) $price['amount'],
                'line_items[0][price_data][product_data][name]' => "Student Attendance SaaS - {$price['label']} Plan",
                'line_items[0][price_data][product_data][description]' => 'One-time onboarding payment to unlock admin registration.',
                'metadata[order_uuid]' => $order->order_uuid,
                'metadata[plan]' => $plan,
                'metadata[type]' => 'school_subscription_onboarding',
            ]);

        if (! $response->successful()) {
            $order->update(['status' => 'failed']);
            throw ValidationException::withMessages([
                'plan' => 'Unable to start Stripe checkout. Please try again.',
            ]);
        }

        $sessionId = (string) ($response->json('id') ?? '');
        $checkoutUrl = (string) ($response->json('url') ?? '');
        if ($sessionId === '' || $checkoutUrl === '') {
            $order->update(['status' => 'failed']);
            throw ValidationException::withMessages([
                'plan' => 'Invalid Stripe checkout response.',
            ]);
        }

        $order->update(['stripe_session_id' => $sessionId]);

        return $checkoutUrl;
    }

    public function createTestingBypassRegistrationUrl(string $plan): string
    {
        $price = self::PLAN_PRICES[$plan] ?? null;
        if (! $price) {
            throw ValidationException::withMessages([
                'plan' => 'Invalid plan selected.',
            ]);
        }

        $order = SubscriptionOrder::query()->create([
            'order_uuid' => (string) Str::uuid(),
            'plan' => $plan,
            'currency' => $price['currency'],
            'amount' => (int) $price['amount'],
            'status' => 'paid',
            'stripe_payment_status' => 'paid',
            'registration_token' => Str::random(48),
            'token_expires_at' => now()->addHours(24),
            'paid_at' => now(),
        ]);

        return route('public.register.admin', ['token' => $order->registration_token]);
    }

    public function completeCheckout(string $orderUuid, string $sessionId): SubscriptionOrder
    {
        $order = SubscriptionOrder::query()
            ->where('order_uuid', $orderUuid)
            ->firstOrFail();

        if ($order->status === 'paid' && $order->registration_token && $order->token_expires_at?->isFuture()) {
            return $order;
        }

        if ($order->stripe_session_id !== $sessionId) {
            throw ValidationException::withMessages([
                'order' => 'Payment session does not match this order.',
            ]);
        }

        $stripeSecret = (string) config('services.stripe.secret', '');
        if ($stripeSecret === '') {
            throw ValidationException::withMessages([
                'order' => 'Stripe is not configured.',
            ]);
        }

        $sessionResponse = Http::withBasicAuth($stripeSecret, '')
            ->get("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

        if (! $sessionResponse->successful()) {
            throw ValidationException::withMessages([
                'order' => 'Could not verify payment from Stripe.',
            ]);
        }

        $paymentStatus = (string) ($sessionResponse->json('payment_status') ?? 'unpaid');
        if ($paymentStatus !== 'paid') {
            throw ValidationException::withMessages([
                'order' => 'Payment not completed yet.',
            ]);
        }

        $stripeCustomerId = (string) ($sessionResponse->json('customer') ?? '');
        $stripeSubscriptionId = (string) ($sessionResponse->json('subscription') ?? '');

        $order->update([
            'status' => 'paid',
            'stripe_payment_status' => $paymentStatus,
            'stripe_customer_id' => $stripeCustomerId !== '' ? $stripeCustomerId : null,
            'stripe_subscription_id' => $stripeSubscriptionId !== '' ? $stripeSubscriptionId : null,
            'paid_at' => now(),
            'registration_token' => Str::random(48),
            'token_expires_at' => now()->addHours(24),
        ]);

        return $order->fresh();
    }

    public function registrationPageData(string $token): array
    {
        $order = $this->validPaidOrderByToken($token);

        return [
            'token' => $token,
            'order' => $order,
            'plans' => self::PLAN_PRICES,
            'selectedPlan' => $order->plan,
        ];
    }

    public function registerAdminFromPaidOrder(string $token, array $data): User
    {
        $order = $this->validPaidOrderByToken($token);

        return DB::transaction(function () use ($order, $data): User {
            $schoolCode = strtoupper((string) $data['school_code']);

            $maxStudents = match ($order->plan) {
                School::SUBSCRIPTION_PLAN_BASIC => School::BASIC_MAX_STUDENTS,
                School::SUBSCRIPTION_PLAN_PRO => School::PRO_MAX_STUDENTS,
                default => School::ENTERPRISE_MAX_STUDENTS,
            };

            $school = School::query()->create([
                'name' => $data['school_name'],
                'code' => $schoolCode,
                'domain' => $data['school_domain'] ?? null,
                'stripe_customer_id' => $order->stripe_customer_id,
                'stripe_subscription_id' => $order->stripe_subscription_id,
                'is_active' => true,
                'subscription_plan' => $order->plan,
                'subscription_status' => School::SUBSCRIPTION_STATUS_ACTIVE,
                'subscription_ends_at' => now()->addYear()->toDateString(),
                'max_students' => $maxStudents,
            ]);

            $admin = User::query()->create([
                'name' => $data['admin_name'],
                'email' => strtolower((string) $data['admin_email']),
                'password' => $data['password'],
                'role' => User::ROLE_ADMIN,
                'school_id' => $school->id,
                'email_verified_at' => now(),
            ]);

            $order->update([
                'status' => 'registered',
                'used_at' => now(),
                'school_id' => $school->id,
                'admin_user_id' => $admin->id,
            ]);

            Auth::login($admin);

            return $admin;
        });
    }

    private function validPaidOrderByToken(string $token): SubscriptionOrder
    {
        $order = SubscriptionOrder::query()
            ->where('registration_token', $token)
            ->where('status', 'paid')
            ->whereNull('used_at')
            ->first();

        if (! $order || ! $order->token_expires_at || $order->token_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'token' => 'Registration link is invalid or expired. Complete payment again.',
            ]);
        }

        return $order;
    }
}
