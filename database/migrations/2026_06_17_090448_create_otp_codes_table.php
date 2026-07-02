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
        Schema::create('otp_codes', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code');
            $table->timestamp('expires_at')->nullable();//تاريخ ووقت انتهاء صلاحية الكود
            $table->boolean('is_used')->default(false);
            $table->timestamps();

        });
    }
    /*soon
            تشفير
            تحديد مدة صلاحية لتجنب التخمين
            حذف الاكواد المنتهية
            تحديد  معدل الارسال لكل مستخدم في الدقيقة
    */
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
