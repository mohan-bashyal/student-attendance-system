<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (! Schema::hasColumn('students', 'device_identifier')) {
                $table->string('device_identifier')->nullable()->after('student_id');
                $table->unique(['school_id', 'device_identifier'], 'students_school_device_identifier_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (Schema::hasColumn('students', 'device_identifier')) {
                $table->dropUnique('students_school_device_identifier_unique');
                $table->dropColumn('device_identifier');
            }
        });
    }
};
