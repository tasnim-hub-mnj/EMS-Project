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
        Schema::create('tasks', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');

            // معلومات المهمة
            $table->string('title');
            $table->text('description')->nullable();

            // الفريق المسؤول
            $table->enum('team',['administrative','technical','services','organizational','security'])->nullable();
            $table->enum('priority', ['low','medium','high'])->default('medium');
            $table->enum('status', ['pending','in_progress','completed','delayed'])->default('pending');
            $table->date('due_date')->nullable();
            $table->json('assigned_staff_ids')->nullable();// الموظفين المعيّنين : ["s2","s3"]
            // $table->json('assigned_names')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
