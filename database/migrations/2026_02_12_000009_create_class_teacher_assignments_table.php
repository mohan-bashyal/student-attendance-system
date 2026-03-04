<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('class_teacher_assignments')) {
            Schema::create('class_teacher_assignments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
                $table->foreignId('section_id')->constrained()->cascadeOnDelete();
                $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['school_id', 'school_class_id', 'section_id'], 'class_teacher_unique_class_section');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_teacher_assignments');
    }
};
