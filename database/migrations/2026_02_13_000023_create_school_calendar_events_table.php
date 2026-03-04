<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_calendar_events')) {
            return;
        }

        Schema::create('school_calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->date('event_date');
            $table->string('event_type', 30)->default('holiday');
            $table->string('title', 150);
            $table->string('note', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'event_date', 'event_type', 'is_active'], 'school_calendar_events_filter_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_calendar_events');
    }
};

