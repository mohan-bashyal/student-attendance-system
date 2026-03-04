<?php

namespace App\Http\Controllers;

use App\Services\PublicSite\SubscriptionWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, SubscriptionWebhookService $subscriptionWebhookService): JsonResponse
    {
        $subscriptionWebhookService->handle(
            $request->getContent(),
            $request->header('Stripe-Signature')
        );

        return response()->json(['received' => true]);
    }
}

