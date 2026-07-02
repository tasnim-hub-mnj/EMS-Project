<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{//i
    public function up(): void
    {
        Schema::create('investor_event_reports', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');
            // $table->foreignId('booth_id')->constrained('booths')->onDelete('cascade');
            $table->foreignId('booth_booking_id')->constrained('booth_bookings')->onDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->date('date');
            $table->integer('total_registrations')->default(0);
            $table->integer('actual_attendance')->default(0);
            $table->float('rating')->default(0);
            $table->float('growth_rate')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_event_reports');
    }
};
