<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_attendance_notifications')) {
            Schema::create('admin_attendance_notifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
                $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
                $table->string('teacher_name');
                $table->string('class_name');
                $table->string('section_name');
                $table->date('attendance_date');
                $table->unsignedInteger('total_students')->default(0);
                $table->string('message');
                $table->timestamps();

                $table->unique(['school_id', 'attendance_session_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_attendance_notifications');
    }
};
