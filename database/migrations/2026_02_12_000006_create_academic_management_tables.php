<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_classes')) {
            Schema::create('school_classes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->unsignedInteger('display_order')->nullable();
                $table->timestamps();
                $table->unique(['school_id', 'name']);
            });
        }

        if (! Schema::hasTable('sections')) {
            Schema::create('sections', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->timestamps();
                $table->unique(['school_id', 'name']);
            });
        }

        if (! Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->timestamps();
                $table->unique(['school_id', 'name']);
            });
        }

        if (! Schema::hasTable('teachers')) {
            Schema::create('teachers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->boolean('has_attendance_access')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('teacher_assignments')) {
            Schema::create('teacher_assignments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
                $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
                $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
                $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
                $table->string('student_id')->unique();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('gender', 20)->nullable();
                $table->string('photo_path')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
        Schema::dropIfExists('teacher_assignments');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('school_classes');
    }
};
