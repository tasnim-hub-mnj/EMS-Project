<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {//i
        Schema::create('booth_bookings', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');
            $table->foreignId('booth_id')->constrained('booths')->onDelete('cascade');
            // $table->string('offical_name')->nullable();//اسم المسؤوول
            $table->integer('duration_days');//عدد ايام الحجز(لا يتزاوج عدد ايام المعرض)
            $table->string('notes')->nullable();
            $table->float('total_price');
            $table->float('paid_amount');
            $table->text('services_products')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled','Finished'])->default('pending');
            $table->date('booked_at')->nullable();
            // $table->json('images');//جدول لحال
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booth_bookings');
    }
};
