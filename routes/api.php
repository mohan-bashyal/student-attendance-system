<?php

use App\Http\Controllers\DeviceAttendanceController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('device.token')->group(function (): void {
    Route::post('/device-attendance', [DeviceAttendanceController::class, 'store'])->name('api.device_attendance.store');
    Route::post('/device-attendance/heartbeat', [DeviceAttendanceController::class, 'heartbeat'])->name('api.device_attendance.heartbeat');
});

Route::post('/stripe/webhook', StripeWebhookController::class)->name('api.stripe.webhook');
