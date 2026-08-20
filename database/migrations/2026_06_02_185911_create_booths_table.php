<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {//oi
        Schema::create('booths', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('number');
            $table->string('section')->nullable();
            $table->float('area');
            $table->enum('status_inv', ['available', 'booked','unavailable'])->default('available');//تلقائي
            $table->enum('status', ['available', 'unavailable'])->default('available');//يدوي/o
            $table->enum('pricing_type', ['total', 'daily'])->default('total');
            $table->float('price')->nullable();//daily_price
            $table->string('location')->nullable();//الموقع داخل المعرض
            $table->json('services')->nullable();//exhibition->some(extra_services)
            $table->json('amenities')->nullable();//الخدمات الاساسية
            // $table->string('image');//جدول لحال
            $table->text('description')->nullable();
            $table->integer('map_x')->nullable();
            $table->integer('map_y')->nullable();
            $table->integer('map_width')->nullable();
            $table->integer('map_height')->nullable();
            $table->timestamps();
        });
    }

    /*
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booths');
    }
};
