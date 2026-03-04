<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_devices')) {
            return;
        }

        Schema::table('school_devices', function (Blueprint $table): void {
            if (! Schema::hasColumn('school_devices', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('school_devices', 'last_event_at')) {
                $table->timestamp('last_event_at')->nullable()->after('last_seen_at');
            }
            if (! Schema::hasColumn('school_devices', 'last_event_status')) {
                $table->string('last_event_status', 30)->nullable()->after('last_event_at');
            }
            if (! Schema::hasColumn('school_devices', 'last_event_message')) {
                $table->string('last_event_message', 255)->nullable()->after('last_event_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('school_devices')) {
            return;
        }

        Schema::table('school_devices', function (Blueprint $table): void {
            if (Schema::hasColumn('school_devices', 'last_event_message')) {
                $table->dropColumn('last_event_message');
            }
            if (Schema::hasColumn('school_devices', 'last_event_status')) {
                $table->dropColumn('last_event_status');
            }
            if (Schema::hasColumn('school_devices', 'last_event_at')) {
                $table->dropColumn('last_event_at');
            }
            if (Schema::hasColumn('school_devices', 'last_seen_at')) {
                $table->dropColumn('last_seen_at');
            }
        });
    }
};

