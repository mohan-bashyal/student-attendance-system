<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('device_attendance_events')) {
            Schema::create('device_attendance_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('school_device_id')->constrained('school_devices')->cascadeOnDelete();
                $table->string('idempotency_key', 120);
                $table->json('payload');
                $table->string('status', 20)->default('pending');
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->json('response_json')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'idempotency_key'], 'device_attendance_events_unique');
                $table->index(['status', 'attempts']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('device_attendance_events');
    }
};
