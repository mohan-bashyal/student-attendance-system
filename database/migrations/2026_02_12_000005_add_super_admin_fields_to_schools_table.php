<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (! Schema::hasColumn('schools', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('domain');
            }

            if (! Schema::hasColumn('schools', 'subscription_plan')) {
                $table->string('subscription_plan')->default('basic')->after('is_active');
            }

            if (! Schema::hasColumn('schools', 'subscription_status')) {
                $table->string('subscription_status')->default('trial')->after('subscription_plan');
            }

            if (! Schema::hasColumn('schools', 'subscription_ends_at')) {
                $table->date('subscription_ends_at')->nullable()->after('subscription_status');
            }

            if (! Schema::hasColumn('schools', 'max_students')) {
                $table->unsignedInteger('max_students')->nullable()->after('subscription_ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (Schema::hasColumn('schools', 'max_students')) {
                $table->dropColumn('max_students');
            }

            if (Schema::hasColumn('schools', 'subscription_ends_at')) {
                $table->dropColumn('subscription_ends_at');
            }

            if (Schema::hasColumn('schools', 'subscription_status')) {
                $table->dropColumn('subscription_status');
            }

            if (Schema::hasColumn('schools', 'subscription_plan')) {
                $table->dropColumn('subscription_plan');
            }

            if (Schema::hasColumn('schools', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
