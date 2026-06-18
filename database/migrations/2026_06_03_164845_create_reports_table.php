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
        Schema::create('reports', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('type');
            $table->text('description')->nullable();
            $table->string('period')->nullable();
            $table->string('booth_name')->nullable();
            $table->string('exhibition_name')->nullable();
            $table->float('main_value')->default(0);
            $table->string('main_label')->nullable();
            $table->float('trend')->default(0);
            $table->json('sparkline_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
