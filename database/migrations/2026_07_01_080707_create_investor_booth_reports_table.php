<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{//i
    public function up(): void
    {
        Schema::create('investor_booth_reports', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');//00
            $table->foreignId('booth_booking_id')->constrained('booth_bookings')->onDelete('cascade');
            // $table->string('booth_number');
            // $table->string('exhibition_name');
            $table->string('date_period');
            $table->float('performance_index')->default(0);
            $table->decimal('growth_rate', 8, 2)->default(0);
            $table->integer('potential_clients')->default(0);
            $table->integer('events_count')->default(0);
            $table->json('data_graph');
            $table->json('data_specific_table');
            $table->json('data_recommendations');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_booth_reports');
    }
};
