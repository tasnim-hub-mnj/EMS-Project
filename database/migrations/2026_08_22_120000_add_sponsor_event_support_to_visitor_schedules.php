<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_schedules', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->unsignedBigInteger('event_id')->nullable()->change();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreignId('sponsor_event_id')
                ->nullable()
                ->after('event_id')
                ->constrained('sponsor_events')
                ->cascadeOnDelete();
            $table->string('event_source')->default('event')->after('sponsor_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('visitor_schedules', function (Blueprint $table) {
            $table->dropForeign(['sponsor_event_id']);
            $table->dropColumn(['sponsor_event_id', 'event_source']);
            $table->dropForeign(['event_id']);
            $table->unsignedBigInteger('event_id')->nullable(false)->change();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }
};
