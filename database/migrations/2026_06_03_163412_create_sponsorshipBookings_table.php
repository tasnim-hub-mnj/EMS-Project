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
        Schema::create('sponsorshipBookings', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');
            $table->foreignId('sponsorEvent_id')->constrained('sponsorEvents')->onDelete('cascade');
            $table->string('company_name');
            $table->string('company_website')->nullable();
            $table->string('company_phone')->nullable();
            $table->text('product_names')->nullable();
            $table->string('selected_duration_label')->nullable();//مدة العرض المختارة
            $table->integer('selected_days')->default(1);//عدد الأيام المختارة
            $table->float('price')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected','cancelled','ended'])->default('pending');
            $table->date('booked_at')->nullable();
            $table->integer('total_visitors')->default(0);//عدد الزوار
            $table->integer('total_attendees')->default(0);//عدد الحضور
            $table->json('daily_visitors')->nullable();
            $table->integer('current_day')->default(1);
            $table->integer('total_days')->default(1);
            $table->timestamps();//amount
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsorshipBookings');
    }
};
