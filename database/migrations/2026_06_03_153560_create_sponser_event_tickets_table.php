<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponser_event_tickets', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('visitor_id')->nullable()->constrained('visitors')->cascadeOnDelete();
            $table->foreignId('sponsor_event_id')->constrained('sponsor_events')->cascadeOnDelete();

            $table->string('type'); // invitation / paid
            $table->string('holder_name');
            $table->string('holder_email');
            $table->string('holder_phone')->nullable();
            $table->enum('delivery_method', ['email', 'sms', 'manual'])->default('email');

            $table->enum('status', ['pending', 'confirmed', 'attended', 'cancelled'])->default('pending');
            $table->string('qr_code')->nullable();
            $table->float('amount')->nullable();//paid_amount
            $table->dateTime('booked_at')->nullable();
            $table->dateTime('attended_at')->nullable();//invitation

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponser_event_tickets');
    }
};
