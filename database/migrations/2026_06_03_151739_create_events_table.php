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
            $table->string('type')->nullable();//ندوة/مسابقة/عرض تقديمي/ورشة/بث مباشر
            $table->string('by');//اسم المقدم/لجنة التحكيم
            $table->date('date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();//موقع الفعالية هو موقع الجناح
            $table->integer('duration_days')->default(1);//مدة الحدث بالأيام(ايام الحجز)
            $table->text('description')->nullable();
            $table->string('video_promo_url')->nullable();//في صور للفعالية
            $table->boolean('is_general_invitation')->default(true);//هل الحدث مفتوح للجميع أم يتطلب دعوة خاصة
            $table->boolean('has_bookable_seats')->default(false);//هل العدد محدود
            $table->integer('max_participants')->nullable();//عدد المقاعد الكلي
            $table->boolean('requires_booking')->default(false);//هل يتطلب الحدث حجز مسبق
            $table->float('ticket_price')->default(0)->nullable();
            $table->integer('registered_count')->default(0);//المحجوز
            $table->integer('total_seats')->nullable();//المتبقي
            $table->integer('scanned_count')->default(0);//عدد الحضور
            $table->enum('status',['active','nonactive','finished'])->default('active');
            $table->integer('current_day')->default(1);
            // $table->json('daily_attendees')->nullable();//تخزين عدد الحضور اليومي لكل يوم من أيام الحدث
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
