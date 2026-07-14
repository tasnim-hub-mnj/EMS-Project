<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {//i
        Schema::create('events', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('booth_booking_id')->constrained('booth_bookings')->onDelete('cascade');
            $table->string('name');
            $table->string('type')->nullable();//ندوة/مسابقة/عرض تقديمي/ورشة/بث مباشر
            // $table->string('by');//اسم المقدم/لجنة التحكيم
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->string('place')->nullable();//موقع الفعالية هو موقع الجناح
            $table->integer('duration_days')->default(1);//مدة الحدث بالأيام(ايام الحجز)
            $table->text('description')->nullable();
            $table->string('video_promo_url')->nullable();//في صور للفعالية
            $table->boolean('is_general_invitation')->default(true);//هل الحدث مفتوح للجميع
            $table->boolean('has_bookable_seats')->default(false);//هل العدد محدود
            $table->integer('max_participants')->nullable();//عدد المقاعد الكلي
            $table->boolean('requires_booking')->default(false);//هل يتطلب الحدث حجز مسبق
            $table->float('ticket_price')->default(0)->nullable();
            $table->integer('registered_count')->default(0);//المحجوز//المسجلون
            $table->integer('total_seats')->nullable();//المتبقي//default(max_participants)
            $table->integer('scanned_count')->default(0);//عدد الحضور
            $table->enum('status',['upcoming', 'ongoing', 'finished'])->default('upcoming');
            $table->integer('current_day')->default(1);//اليوم الحالي من الفعالية
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
