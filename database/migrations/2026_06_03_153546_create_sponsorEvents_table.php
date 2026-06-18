<?php

use App\Models\Event;
use App\Models\SponsorshipBooking;
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
        Schema::create('sponsorEvents', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('organizers')->onDelete('cascade');
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('date')->nullable();//تاريخ بدء الحدث
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('place')->nullable();
            $table->integer('listing_days')->default(1);//عدد ايام عرض الحدث
            $table->text('description')->nullable();
            $table->json('duration_options')->nullable();//خيارات مدة العرض
            $table->float('price')->nullable();
            $table->enum('copy_status', ['draft','active','archived'])->default('draft');
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
