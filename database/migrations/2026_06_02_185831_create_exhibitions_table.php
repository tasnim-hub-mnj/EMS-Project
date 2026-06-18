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
        Schema::create('exhibitions', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('type');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('location');
            $table->string('city');
            $table->enum('status', ['far','upcoming','ongoing','finished'])->default('upcoming');
            $table->enum('copy_status', ['draft','active','archived'])->default('draft');
            $table->integer('available_booths')->default(0);
            $table->integer('total_booths')->default(0);
            $table->integer('total_events')->default(0);
            $table->integer('visitors_count')->default(0);
            $table->json('sectors')->nullable();//مثال: ["Technology", "Food", "Fashion"]
            $table->json('extra_services')->nullable();
            $table->float('working_hours');
            $table->boolean('is_paid')->default(0);
            $table->float('ticket_price')->nullable();
            $table->json('map');//0000000
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
