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
    {// بطاقة تعريف الشركة داخل البوث
        Schema::create('boothProfiles', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');
            $table->foreignId('booth_id')->constrained('booths')->onDelete('cascade');
            $table->text('company_nature')->nullable();
            $table->text('services_products')->nullable();
            $table->string('headquarters')->nullable();//مقر الشركة
            $table->json('social_links')->nullable();
            // $table->json('product_images')->nullable();
            // $table->json('booth_images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boothProfiles');
    }
};
