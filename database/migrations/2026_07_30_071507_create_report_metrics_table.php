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
        Schema::create('report_metrics', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('report_id')->constrained('report_investors')->onDelete('cascade');
            $table->string('key');// visitors_count, sales_total, etc
            $table->string('label');// عدد الزوار، إجمالي المبيعات...
            $table->string('value');// 1200 أو 35000 أو أي قيمة
            $table->enum('trend', ['up','down','stable'])->nullable();
            $table->json('sparkline_data')->nullable(); // [10,20,15,30]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_metrics');
    }
};
