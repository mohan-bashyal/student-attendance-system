<?php

namespace App\Services\PublicSite;

use App\Models\School;
use App\Models\SubscriptionOrder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionWebhookService
{
    public function handle(string $payload, ?string $signature): void
    {
        $secret = (string) config('services.stripe.webhook_secret', '');
        if ($secret === '') {
            throw ValidationException::withMessages([
                'stripe' => 'Stripe webhook secret is not configured.',
            ]);
        }

        if (! $this->verifyStripeSignature($payload, (string) $signature, $secret)) {
            throw ValidationException::withMessages([
                'stripe' => 'Invalid Stripe webhook signature.',
            ]);
        }

        /** @var array<string,mixed> $event */
        $event = json_decode($payload, true) ?? [];
        $type = (string) ($event['type'] ?? '');
        $object = Arr::get($event, 'data.object', []);
        if (! is_array($object)) {
            return;
        }

        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($object),
            'invoice.paid' => $this->handleInvoicePaid($object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object),
            default => null,
        };
    }

    private function handleCheckoutSessionCompleted(array $session): void
    {
        $sessionId = (string) ($session['id'] ?? '');
        $orderUuid = (string) Arr::get($session, 'metadata.order_uuid', '');
        $paymentStatus = (string) ($session['payment_status'] ?? 'unpaid');
        $customerId = (string) ($session['customer'] ?? '');
        $subscriptionId = (string) ($session['subscription'] ?? '');

        $orderQuery = SubscriptionOrder::query();
        if ($sessionId !== '') {
            $orderQuery->where('stripe_session_id', $sessionId);
        } elseif ($orderUuid !== '') {
            $orderQuery->where('order_uuid', $orderUuid);
        } else {
            return;
        }

        $order = $orderQuery->first();
        if (! $order) {
            return;
        }

        $updates = [
            'stripe_payment_status' => $paymentStatus,
            'stripe_customer_id' => $customerId !== '' ? $customerId : $order->stripe_customer_id,
            'stripe_subscription_id' => $subscriptionId !== '' ? $subscriptionId : $order->stripe_subscription_id,
        ];

        if ($paymentStatus === 'paid') {
            $updates['status'] = in_array($order->status, ['registered'], true) ? $order->status : 'paid';
            $updates['paid_at'] = $order->paid_at ?? now();
            $updates['registration_token'] = $order->registration_token ?: Str::random(48);
            $updates['token_expires_at'] = $order->token_expires_at ?? now()->addHours(24);
        }

        $order->update($updates);
    }

    private function handleInvoicePaid(array $invoice): void
    {
        $customerId = (string) ($invoice['customer'] ?? '');
        $subscriptionId = (string) ($invoice['subscription'] ?? '');
        $periodEnd = Arr::get($invoice, 'lines.data.0.period.end');

        $school = $this->resolveSchoolByStripe($customerId, $subscriptionId);
        if (! $school) {
            return;
        }

        $updates = [
            'subscription_status' => School::SUBSCRIPTION_STATUS_ACTIVE,
            'stripe_customer_id' => $customerId !== '' ? $customerId : $school->stripe_customer_id,
            'stripe_subscription_id' => $subscriptionId !== '' ? $subscriptionId : $school->stripe_subscription_id,
        ];

        if (is_numeric($periodEnd)) {
            $updates['subscription_ends_at'] = now()->setTimestamp((int) $periodEnd)->toDateString();
        }

        $school->update($updates);
    }

    private function handleInvoicePaymentFailed(array $invoice): void
    {
        $customerId = (string) ($invoice['customer'] ?? '');
        $subscriptionId = (string) ($invoice['subscription'] ?? '');

        $school = $this->resolveSchoolByStripe($customerId, $subscriptionId);
        if (! $school) {
            return;
        }

        $school->update([
            'subscription_status' => School::SUBSCRIPTION_STATUS_PAST_DUE,
            'stripe_customer_id' => $customerId !== '' ? $customerId : $school->stripe_customer_id,
            'stripe_subscription_id' => $subscriptionId !== '' ? $subscriptionId : $school->stripe_subscription_id,
        ]);
    }

    private function handleSubscriptionUpdated(array $subscription): void
    {
        $customerId = (string) ($subscription['customer'] ?? '');
        $subscriptionId = (string) ($subscription['id'] ?? '');
        $stripeStatus = (string) ($subscription['status'] ?? '');
        $periodEnd = $subscription['current_period_end'] ?? null;

        $school = $this->resolveSchoolByStripe($customerId, $subscriptionId);
        if (! $school) {
            return;
        }

        $mappedStatus = match ($stripeStatus) {
            'active', 'trialing' => School::SUBSCRIPTION_STATUS_ACTIVE,
            'past_due', 'unpaid', 'incomplete', 'incomplete_expired' => School::SUBSCRIPTION_STATUS_PAST_DUE,
            'canceled' => School::SUBSCRIPTION_STATUS_CANCELLED,
            default => $school->subscription_status,
        };

        $updates = [
            'subscription_status' => $mappedStatus,
            'stripe_customer_id' => $customerId !== '' ? $customerId : $school->stripe_customer_id,
            'stripe_subscription_id' => $subscriptionId !== '' ? $subscriptionId : $school->stripe_subscription_id,
        ];

        $plan = $this->extractPlanFromStripeSubscription($subscription);
        if ($plan) {
            $updates['subscription_plan'] = $plan;
            $updates['max_students'] = match ($plan) {
                School::SUBSCRIPTION_PLAN_BASIC => School::BASIC_MAX_STUDENTS,
                School::SUBSCRIPTION_PLAN_PRO => School::PRO_MAX_STUDENTS,
                default => School::ENTERPRISE_MAX_STUDENTS,
            };
        }

        if (is_numeric($periodEnd)) {
            $updates['subscription_ends_at'] = now()->setTimestamp((int) $periodEnd)->toDateString();
        }

        $school->update($updates);
    }

    private function handleSubscriptionDeleted(array $subscription): void
    {
        $customerId = (string) ($subscription['customer'] ?? '');
        $subscriptionId = (string) ($subscription['id'] ?? '');

        $school = $this->resolveSchoolByStripe($customerId, $subscriptionId);
        if (! $school) {
            return;
        }

        $school->update([
            'subscription_status' => School::SUBSCRIPTION_STATUS_CANCELLED,
            'stripe_customer_id' => $customerId !== '' ? $customerId : $school->stripe_customer_id,
            'stripe_subscription_id' => $subscriptionId !== '' ? $subscriptionId : $school->stripe_subscription_id,
        ]);
    }

    private function resolveSchoolByStripe(string $customerId, string $subscriptionId): ?School
    {
        if ($subscriptionId !== '') {
            $school = School::query()->where('stripe_subscription_id', $subscriptionId)->first();
            if ($school) {
                return $school;
            }

            $order = SubscriptionOrder::query()->where('stripe_subscription_id', $subscriptionId)->first();
            if ($order?->school_id) {
                return School::query()->find((int) $order->school_id);
            }
        }

        if ($customerId !== '') {
            $school = School::query()->where('stripe_customer_id', $customerId)->first();
            if ($school) {
                return $school;
            }

            $order = SubscriptionOrder::query()->where('stripe_customer_id', $customerId)->orderByDesc('id')->first();
            if ($order?->school_id) {
                return School::query()->find((int) $order->school_id);
            }
        }

        return null;
    }

    private function extractPlanFromStripeSubscription(array $subscription): ?string
    {
        $raw = strtolower((string) Arr::get($subscription, 'metadata.plan', ''));
        if ($raw === '') {
            $raw = strtolower((string) Arr::get($subscription, 'items.data.0.price.lookup_key', ''));
        }
        if ($raw === '') {
            $raw = strtolower((string) Arr::get($subscription, 'items.data.0.price.nickname', ''));
        }

        return match (true) {
            str_contains($raw, School::SUBSCRIPTION_PLAN_ENTERPRISE) => School::SUBSCRIPTION_PLAN_ENTERPRISE,
            str_contains($raw, School::SUBSCRIPTION_PLAN_PRO) => School::SUBSCRIPTION_PLAN_PRO,
            str_contains($raw, School::SUBSCRIPTION_PLAN_BASIC) => School::SUBSCRIPTION_PLAN_BASIC,
            default => null,
        };
    }

    private function verifyStripeSignature(string $payload, string $signatureHeader, string $secret): bool
    {
        if ($signatureHeader === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['t'] ?? null;
        $signature = $parts['v1'] ?? null;
        if (! $timestamp || ! $signature) {
            return false;
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $signature);
    }
}

