<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->string('name');
            $table->string('type')->nullable();
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->integer('map_x')->nullable();
            $table->integer('map_y')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['exhibition_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
