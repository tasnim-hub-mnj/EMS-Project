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
            $table->string('name');//title
            $table->string('type');
            // $table->string('by')->nullable();//اسم المقدم/لجنة التحكيم//v
            $table->string('place');//venue_name
            $table->dateTime('start_time');//start_at
            $table->dateTime('end_time');//end_at
            $table->text('description')->nullable();
            $table->enum('ticket_type', ['invitation', 'paid'])->default('invitation');
            // $table->boolean('is_general_invitation')->default(true);//ticket_type
            $table->float('ticket_price')->default(0);
            $table->integer('max_participants');//capacity
            // $table->string('image');//جدول لحال

            $table->integer('duration_days')->default(1);//عدد ايام عرض الحدث
            $table->json('duration_options')->nullable();//o->i/خيارات مدة العرض (كل يوم مع السعر )
            $table->double('daily_price')->nullable();//o->i/السعر اليومي للرعاية

            $table->integer('registered_count')->default(0);//registered/المحجوز
            // $table->integer('total_seats')->nullable();//المتبقي
            $table->integer('scanned_count')->default(0);//attended/عدد الحضور
            $table->enum('status', ['upcoming', 'ongoing', 'finished'])->default('upcoming');//status in investor
            $table->enum('copy_status', ['draft', 'published', 'archived'])->default('draft');//status in organizer
            $table->date('publish_date')->nullable();//published_at
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
