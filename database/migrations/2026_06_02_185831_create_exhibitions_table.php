<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {//o
        Schema::create('exhibitions', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('organizer_id')->constrained('organizers')->onDelete('cascade');
            $table->string('admin_full_name')->nullable();
            // $table->string('year')->constrained('copies')->onDelete('cascade');
            // $table->enum('copy_status', ['draft', 'active', 'archived'])->default('draft');
            $table->string('name');
            $table->string('type');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('location');
            $table->text('description')->nullable();
            $table->string('city')->nullable();
            $table->enum('status', ['far', 'upcoming', 'ongoing', 'finished','hidden'])->default('far');
            $table->integer('available_booths')->default(0);
            $table->integer('total_booths')->default(0);
            $table->integer('total_sponser_events')->default(0);
            $table->integer('visitors_count')->default(0);//scane
            $table->json('sectors')->nullable();//مثال: ["Technology", "Food", "Fashion"]//organizers->category
            $table->json('extra_services')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->float('ticket_price')->nullable();
            $table->boolean('map_built')->default(false);
            // $table->json('map')->nullable();//جدول لحال
            $table->float('working_hours')->nullable();
            $table->string('image')->nullable();
            // $table->json('images');//جدول لحال
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('exhibitions');
    }
};
