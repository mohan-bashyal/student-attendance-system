<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table): void {
                if (! Schema::hasColumn('schools', 'stripe_customer_id')) {
                    $table->string('stripe_customer_id')->nullable()->after('domain');
                }
                if (! Schema::hasColumn('schools', 'stripe_subscription_id')) {
                    $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
                }
            });
        }

        if (Schema::hasTable('subscription_orders')) {
            Schema::table('subscription_orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('subscription_orders', 'stripe_customer_id')) {
                    $table->string('stripe_customer_id')->nullable()->after('stripe_session_id');
                }
                if (! Schema::hasColumn('subscription_orders', 'stripe_subscription_id')) {
                    $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table): void {
                if (Schema::hasColumn('schools', 'stripe_subscription_id')) {
                    $table->dropColumn('stripe_subscription_id');
                }
                if (Schema::hasColumn('schools', 'stripe_customer_id')) {
                    $table->dropColumn('stripe_customer_id');
                }
            });
        }

        if (Schema::hasTable('subscription_orders')) {
            Schema::table('subscription_orders', function (Blueprint $table): void {
                if (Schema::hasColumn('subscription_orders', 'stripe_subscription_id')) {
                    $table->dropColumn('stripe_subscription_id');
                }
                if (Schema::hasColumn('subscription_orders', 'stripe_customer_id')) {
                    $table->dropColumn('stripe_customer_id');
                }
            });
        }
    }
};
