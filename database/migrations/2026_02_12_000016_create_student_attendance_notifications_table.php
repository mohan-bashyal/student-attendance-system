<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_attendance_notifications')) {
            Schema::create('student_attendance_notifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('attendance_session_id')->constrained('attendance_sessions')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->string('message', 255);
                $table->boolean('is_read')->default(false);
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['school_id', 'attendance_session_id', 'student_id'],
                    'student_attendance_notifications_unique'
                );
                $table->index(['student_id', 'is_read']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance_notifications');
    }
};
