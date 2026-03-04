<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parent_notification_delivery_logs')) {
            Schema::create('parent_notification_delivery_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->foreignId('parent_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('channel', 20);
                $table->string('status', 20);
                $table->string('recipient')->nullable();
                $table->text('message');
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'channel', 'status']);
                $table->index(['student_id', 'attendance_session_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_notification_delivery_logs');
    }
};
