<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {//v
        //تذكرة فعالية
        Schema::create('event_tickets', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('visitor_id')->constrained('visitors')->OnDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('qr_code')->nullable();//approved
            $table->decimal('amount', 12, 2)->nullable();
            $table->dateTime('booked_at')->nullable();//now()->format('Y-m-d H:i')

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_tickets');
    }
};
