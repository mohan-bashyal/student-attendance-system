<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_audit_logs')) {
            Schema::create('attendance_audit_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 30)->default('updated');
                $table->string('previous_status', 20)->nullable();
                $table->string('new_status', 20)->nullable();
                $table->string('previous_leave_type', 20)->nullable();
                $table->string('new_leave_type', 20)->nullable();
                $table->string('previous_remark')->nullable();
                $table->string('new_remark')->nullable();
                $table->timestamp('changed_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_audit_logs');
    }
};
