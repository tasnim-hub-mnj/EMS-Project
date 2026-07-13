<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_booking_image', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booth_booking_id')->constrained('booth_bookings')->cascadeOnDelete();
            $table->string('image_p');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_booking_image');
    }
};
