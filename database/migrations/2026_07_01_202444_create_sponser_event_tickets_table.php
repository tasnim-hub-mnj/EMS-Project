<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sponser_event_tickets', function (Blueprint $table) {
            $table->id();
            // ربط التذكرة بالفعالية الراعية
            $table->foreignId('sponsor_event_id')->constrained('sponsor_events')->cascadeOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained('visitors')->cascadeOnDelete();

            // بيانات الشخص الذي حجز التذكرة
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
        Schema::dropIfExists('sponser_event_tickets');
    }
};
