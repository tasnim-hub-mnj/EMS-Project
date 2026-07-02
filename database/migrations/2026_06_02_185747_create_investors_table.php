<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {//i
        Schema::create('investors', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('company_name');
            $table->string('trade_name')->nullable();//المجال التجاري//activity_type
            $table->string('location');
            $table->string('website')->nullable();
            $table->enum('activity_type',['technology','food&hospitality','fashion','health','education','other'])->nullable();//نوع النشاط
            $table->boolean('terms_accepted')->default(false);//الموافقة على الشروط

            $table->text('bio')->nullable();
            $table->string('logo')->nullable();
            // $table->json('social_links')->nullable();//جدول لحال
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investors');
    }
};
