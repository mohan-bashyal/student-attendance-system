<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\PublicSite\SubscriptionCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicLandingController extends Controller
{
    public function __construct(private readonly SubscriptionCheckoutService $checkoutService)
    {
    }

    public function index(): View
    {
        return view('public.landing', $this->checkoutService->landingData());
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', Rule::in(School::SUBSCRIPTION_PLANS)],
        ]);

        if ((string) config('services.stripe.secret', '') === '') {
            $url = $this->checkoutService->createTestingBypassRegistrationUrl($data['plan']);

            return redirect()->to($url)->with('status', 'Stripe key not set. Opened test registration flow directly.');
        }

        $checkoutUrl = $this->checkoutService->createCheckoutSession(
            $data['plan'],
            route('public.checkout.success'),
            route('public.landing')
        );

        return redirect()->away($checkoutUrl);
    }

    public function checkoutSuccess(Request $request): View
    {
        $data = $request->validate([
            'session_id' => ['required', 'string'],
            'order' => ['required', 'string'],
        ]);

        $order = $this->checkoutService->completeCheckout($data['order'], $data['session_id']);

        return view('public.checkout-success', ['order' => $order]);
    }

    public function registerForm(Request $request): View
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        return view('public.admin-register', $this->checkoutService->registrationPageData($data['token']));
    }

    public function registerStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'school_name' => ['required', 'string', 'max:255'],
            'school_code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:schools,code'],
            'school_domain' => ['nullable', 'string', 'max:255', 'unique:schools,domain'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->checkoutService->registerAdminFromPaidOrder($data['token'], $data);

        return redirect()->route('dashboard.admin');
    }
}
