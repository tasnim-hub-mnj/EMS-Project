<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('copy_reports', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->foreignId('copy_id')->constrained('copies')->onDelete('cascade');
            $table->integer('total_visitors')->default(0);
            $table->float('revenues', 10, 2)->default(0);
            $table->integer('booking_booths')->default(0);
            $table->integer('available_booths')->default(0);
            $table->float('sponsors', 10, 2)->default(0);
            $table->foreignId('booth_id')->constrained('booths')->onDelete('cascade');
            $table->foreignId('booth_booking_id')->constrained('booth_bookings')->onDelete('cascade');
            $table->foreignId('sponsor_id')->constrained('sponsors')->onDelete('cascade');
            $table->foreignId('sponsor_event_id')->constrained('sponsor_events')->onDelete('cascade');
            $table->foreignId('staff_member_id')->constrained('staff_members')->onDelete('cascade');
            $table->foreignId('visitor_id')->constrained('visitors')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copy_reports');
    }
};
