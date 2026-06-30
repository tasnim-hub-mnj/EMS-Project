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
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('type',['administrative','technical','services','organizational','security'])->default('services')->nullable();
            $table->string('proffesion')->nullable();
            $table->enum('role',['manager','supervisor','specicialist','employee'])->nullable()->default('employee');
            $table->date('availability_date');
            $table->integer('national_num');
            $table->float('exp_salary');
            $table->text('bio');
            $table->text('scientific_experience');
            $table->text('educational_qualifications');
            $table->text('skills');
            $table->enum('status', ['new','pending','approved','rejected'])->default('new');

            
            $table->string('team');
            $table->json('schedule');//ايام و اوقات العمل
            $table->string('qr_code');
            $table->float('att_rate');//معدل الحضور

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
