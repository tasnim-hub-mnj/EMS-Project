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
        Schema::create('sponsorship_booking_product_images', function (Blueprint $table)
        {
            $table->id();//sponsorship_booking_id
            $table->foreignId('sp_b_id')->constrained('sponsorship_bookings')->onDelete('cascade');
            $table->string('product_name');
            $table->string('image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsorship_booking_product_images');
    }
};
