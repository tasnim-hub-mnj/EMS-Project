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
        Schema::create('booths', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->string('number');
            $table->float('area');
            $table->enum('status_inv', ['available', 'booked', 'pending'])->default('available');
            $table->enum('status', ['available', 'unavailable', 'pending'])->default('available');
            $table->float('price');
            $table->date('end_date')->nullable();
            $table->string('location')->nullable();//الموقع داخل المعرض
            $table->json('services');//الخدمة + السعر
            $table->integer('map_x')->nullable();
            $table->integer('map_y')->nullable();
            $table->integer('map_z')->nullable();
            $table->timestamps();//sponsor_event_programe
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booths');
    }
};
