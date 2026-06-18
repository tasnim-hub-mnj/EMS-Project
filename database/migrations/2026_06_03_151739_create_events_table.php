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
        Schema::create('events', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');
            $table->foreignId('booth_id')->constrained('booths')->onDelete('cascade');
            $table->string('name');
            $table->string('type')->nullable();
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->integer('duration_days')->default(1);//مدة الحدث بالأيام
            $table->integer('max_participants')->nullable();//الحد الأقصى للمشاركين في الحدث
            $table->integer('registered_count')->default(0);//عدد المسجلين في الحدث
            $table->enum('status',['active','nonactive'])->default('active');
            $table->enum('copy_status', ['draft','archived'])->default('draft');
            $table->text('description')->nullable();
            $table->boolean('requires_booking')->default(false);//هل يتطلب الحدث حجز مسبق
            $table->string('place')->nullable();
            $table->boolean('has_bookable_seats')->default(false);//هل يحتوي الحدث على مقاعد قابلة للحجز
            $table->integer('total_seats')->nullable();//إجمالي عدد المقاعد المتاحة في الحدث
            $table->integer('booked_seats')->default(0);//عدد المقاعد المحجوزة في الحدث
            $table->integer('sold_tickets')->default(0);//عدد التذاكر المباعة للحدث
            $table->float('ticket_price')->default(0);
            $table->boolean('is_general_invitation')->default(true);//هل الحدث مفتوح للجميع أم يتطلب دعوة خاصة
            $table->string('video_promo_url')->nullable();
            $table->json('company_images')->nullable();//88-logo
            $table->integer('current_day')->default(1);
            $table->integer('total_event_days')->default(1);
            $table->json('daily_attendees')->nullable();//تخزين عدد الحضور اليومي لكل يوم من أيام الحدث
            $table->integer('scanned_count')->default(0);//عدد الحضور الذين تم مسح تذاكرهم عند الدخول للحدث
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
