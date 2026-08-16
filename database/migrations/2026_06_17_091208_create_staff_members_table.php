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
        Schema::create('staff_members', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->string('number');
            $table->string('name');
            // $table->string('email')->nullable();
            // $table->string('phone')->nullable();

            $table->enum('type',['permanent','temporary'])->nullable();
            $table->string('role')->nullable();//"مدير العمليات"
            $table->string('rank')->nullable();//الرتبة
            $table->enum('team',['administrative','organizational','services','external'])->default('administrative')->nullable();
            // $table->string('qrCode')->nullable();//تم نقله
            $table->string('schedule')->nullable();//"08:00 - 17:00"
            $table->float('attendanceRate')->default(0);
            $table->integer('tasksCompleted')->default(0);
            $table->integer('tasksTotal')->default(0);
            $table->string('nationalId')->nullable();
            $table->string('idImage')->nullable();
            $table->string('profileImage')->nullable();
            $table->float('salary')->default(0);
            $table->enum('paymentPeriod',['monthly','bi-weekly','weekly','daily','hourly'])->default('monthly');
            $table->json('workDays')->nullable();//["sun","mon","tue","wed","thu"]
            // $table->enum('status', ['new','pending','approved','rejected'])->default('pending');//new:pending
            // $table->string('applied_at')->nullable();

            $table->string('cvFile')->nullable();
            $table->string('cvFileName')->nullable();
            $table->string('contractFile')->nullable();//ملف العقد
            $table->string('contractFileName')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
