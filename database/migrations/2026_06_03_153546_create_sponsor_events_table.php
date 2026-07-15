<?php

use App\Models\Event;
use App\Models\SponsorshipBooking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {//o
        Schema::create('sponsor_events', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->string('name');
            $table->string('type');
            $table->string('by')->nullable();//اسم المقدم/لجنة التحكيم//v
            $table->string('place');//الاماكن المتاحة فقط داخل المعرض
            $table->dateTime('start_time');//now()->format('Y-m-d h:i A')
            $table->dateTime('end_time');//now()->format('Y-m-d h:i A')
            $table->text('description')->nullable();
            $table->boolean('is_general_invitation')->default(true);//هل مفتوح للجميع أم يتطلب دعوة خاصة
            $table->float('ticket_price')->default(0);
            $table->integer('max_participants');//عدد المقاعد الكلي
            // $table->string('image');//جدول لحال

            $table->integer('duration_days')->default(1);//عدد ايام عرض الحدث
            $table->json('duration_options')->nullable();//o->i/خيارات مدة العرض (كل يوم مع السعر )

            $table->integer('registered_count')->default(0);//المحجوز
            $table->integer('total_seats')->nullable();//المتبقي
            $table->integer('scanned_count')->default(0);//عدد الحضور
            $table->enum('status', ['upcoming', 'ongoing', 'finished'])->default('upcoming');
            $table->enum('copy_status', ['draft', 'active', 'archived'])->default('draft');
            $table->date('publish_date')->nullable();//$event->publish_date = Carbon::now()->locale('en')->translatedFormat('l, j F Y');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsor_events');
    }

};
