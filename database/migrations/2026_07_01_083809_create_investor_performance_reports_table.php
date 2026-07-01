<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{//i
    public function up(): void
    {
        Schema::create('investor_performance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');
            $table->integer('total_booths')->default(0);
            $table->integer('total_visitors')->default(0);
            $table->integer('total_potential_clients')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->float('avg_performance_index')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_performance_reports');
    }
};
