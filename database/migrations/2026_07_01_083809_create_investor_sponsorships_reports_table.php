<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{//i
    public function up(): void
    {
        Schema::create('investor_sponsorships_reports', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');

            $table->integer('total_campaigns')->default(0);
            $table->integer('total_reach')->default(0);
            $table->decimal('growth_rate', 8, 2)->default(0);
            $table->decimal('total_amount', 8, 2)->default(0);
            $table->integer('total_favorites')->default(0);
            $table->decimal('overall_ctr', 5, 2)->default(0);

            $table->json('data_graph');
            $table->json('data_specific_table');
            $table->json('data_recommendations');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_sponsorships_reports');
    }
};
