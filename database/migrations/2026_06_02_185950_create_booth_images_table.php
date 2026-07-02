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
        Schema::create('booth_images', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('booth_booking_id')->constrained('booth_bookings')->cascadeOnDelete();
            $table->string('url');
            $table->enum('type',['product','booth'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booth_images');
    }
};
