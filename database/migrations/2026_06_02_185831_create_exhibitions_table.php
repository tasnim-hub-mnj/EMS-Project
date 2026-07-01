<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {//o
        Schema::create('exhibitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('type');//organizers->category
            $table->date('start_date');
            $table->date('end_date');
            $table->string('location');
            $table->text('description')->nullable();
            $table->string('city');
            $table->enum('status', ['far', 'upcoming', 'ongoing', 'finished'])->default('upcoming');
            $table->enum('copy_status', ['draft', 'active', 'archived'])->default('draft');
            $table->integer('available_booths')->default(0);
            $table->integer('total_booths')->default(0);
            $table->integer('total_sponser_events')->default(0);
            $table->integer('visitors_count')->default(0);//scane
            $table->json('sectors')->nullable();//مثال: ["Technology", "Food", "Fashion"]
            $table->json('extra_services')->nullable();
            $table->float('working_hours');//00000
            $table->boolean('is_paid')->default(false);
            // $table->json('images');//جدول لحال

            $table->float('ticket_price')->nullable();
            $table->json('map');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exhibitions');
    }
};
