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
        Schema::create('sponsorEvents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->foreignId('booth_id')->constrained('booths')->onDelete('cascade');
            // $table->foreignId('created_by')->constrained('organizers')->onDelete('cascade');
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('by');//اسم المقدم/لجنة التحكيم
            $table->string('place')->nullable();//الاماكن المتاحة فقط داخل المعرض
            $table->timestamps('start_time')->nullable();//منع تضارب اكثر من فعالية في مكان ووقت واحد
            $table->timestamps('end_time')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_general_invitation')->default(true);//هل مفتوح للجميع أم يتطلب دعوة خاصة
            $table->float('ticket_price')->default(0)->nullable();
            $table->integer('max_participants')->nullable();//عدد المقاعد الكلي
            // $table->string('image');//جدول لحال

            $table->integer('listing_days')->default(1);//عدد ايام عرض الحدث
            $table->json('duration_options')->nullable();//خيارات مدة العرض (كل يوم مع السعر )

            $table->integer('registered_count')->default(0);//المحجوز
            $table->integer('total_seats')->nullable();//المتبقي
            $table->integer('scanned_count')->default(0);//عدد الحضور
            $table->enum('status', ['active', 'nonactive', 'finished'])->default('active');
            $table->enum('copy_status', ['draft', 'active', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsorEvents');
    }

};
