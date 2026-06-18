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
        Schema::create('attendance_records', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff_members')->onDelete('cascade');
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->date('date')->nullable();//تاريخ الحضور (اليوم فقط)
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->float('hours_worked')->nullable();
            $table->timestamps();

        });
        /*
        منع التكرار   UNIQUE(staff_id, date);
        حساب تلقائي للساعات
        تتبع الغياب
        حساب معدل الحضور من هذا الجدول
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
