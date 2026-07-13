<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{//o
    public function up(): void
    {
        Schema::create('sponsor_event_images', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('sponsor_event_id')->constrained('sponsor_events')->onDelete('cascade');
            $table->string('image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsor_event_images');
    }
};
