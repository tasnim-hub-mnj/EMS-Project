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
        Schema::create('copies', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');

            $table->string('year', 4);
            $table->date('start_date');//exhibition->start_date
            $table->date('end_date');//exhibition->end_date
            $table->enum('copy_status', ['archived','active', 'finished'])->default('active');

            // NEW FIELDS
            $table->boolean('announced')->default(true);
            $table->integer('total_booths')->default(0);//exhibition->total_booths
            $table->integer('booked_booths')->default(0);//total_booths - available_booths
            $table->integer('available_booths')->default(0);//exhibition->available_booths
            $table->integer('pending_requests')->default(0);

            $table->integer('visitor_count')->default(0);//exhibition->visitors_count
            $table->integer('expected_visitors')->default(0);

            $table->float('turnout_percent')->default(0);//الاقبال
            $table->float('expected_turnout_percent')->default(0);

            $table->float('revenue')->default(0);
            $table->float('expected_revenue')->default(0);

            $table->integer('staff_count')->default(0);
            $table->float('sponsorship_percent')->default(0);

            $table->integer('final_booked_booths')->default(0);//exhibition->booths->status->'finished'

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copies');
    }
};
