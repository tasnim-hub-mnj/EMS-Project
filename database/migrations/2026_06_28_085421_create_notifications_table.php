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
        Schema::create('notifications', function (Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->string('type');//اسم فئة الاشعارات bookingNotification
            $table->morphs('notifiable');//لتحديد من يتلقى الاشعار/علاقة مورف
            $table->text('data');//body/محتوى الاشعار
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
