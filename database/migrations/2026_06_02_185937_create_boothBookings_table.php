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
        Schema::create('boothBookings', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');
            $table->foreignId('booth_id')->constrained('booths')->onDelete('cascade');
            // $table->string('offical_name')->nullable();//اسم المسؤوول
            $table->integer('duration_days');//عدد ايام الحجز(لا يتزاوج عدد ايام المعرض)
            $table->string('notes')->nullable();//ملاحظات
            $table->float('total_price');
            $table->float('paid_amount');
            $table->text('services_products')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled','Finished'])->default('pending');
            $table->date('booked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boothBookings');
    }
};
