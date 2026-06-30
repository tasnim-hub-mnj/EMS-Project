<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{//i
    public function up(): void
    {
        Schema::create('investor_reports', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');
            $table->foreignId('booth_booking_id')->constrained('boothBookings')->onDelete('cascade');
            $table->string('title');
            $table->date('date');//نهاية الحجز
            $table->enum('type',['visitors','performance','events','campaigns','comparison']);
            $table->text('description')->nullable();
            $table->string('period')->nullable();
            $table->float('l0')->nullable();
            $table->float('l1')->nullable();
            $table->float('l2')->nullable();
            $table->float('l3')->nullable();
            // $table->string('booth_name')->nullable();
            // $table->string('exhibition_name')->nullable();
            // $table->float('main_value')->default(0);
            // $table->string('main_label')->nullable();
            // $table->float('trend')->default(0);
            // $table->json('sparkline_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_reports');
    }
};
