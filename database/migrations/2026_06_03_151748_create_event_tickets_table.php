<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {//v
        //تذكرة فعالية
        Schema::create('event_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('visitors')->OnDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->string('qr_code')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->timestamp('booked_at')->nullable();
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
