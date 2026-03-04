<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_orders')) {
            Schema::create('subscription_orders', function (Blueprint $table): void {
                $table->id();
                $table->uuid('order_uuid')->unique();
                $table->string('plan');
                $table->string('currency', 10)->default('usd');
                $table->unsignedInteger('amount');
                $table->string('stripe_session_id')->nullable()->unique();
                $table->string('stripe_payment_status', 50)->default('unpaid');
                $table->string('status', 30)->default('pending');
                $table->string('registration_token', 80)->nullable()->unique();
                $table->timestamp('token_expires_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['plan', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_orders');
    }
};
