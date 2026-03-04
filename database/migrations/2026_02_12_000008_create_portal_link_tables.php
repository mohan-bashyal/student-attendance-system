<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_user_profiles')) {
            Schema::create('student_user_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique('user_id');
                $table->unique('student_id');
            });
        }

        if (! Schema::hasTable('parent_student_links')) {
            Schema::create('parent_student_links', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['parent_user_id', 'student_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student_links');
        Schema::dropIfExists('student_user_profiles');
    }
};
