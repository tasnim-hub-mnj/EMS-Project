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
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->enum('status',['pending','in_progress','completed','late','cancelled'])->default('pending');
            $table->enum('priority',['low','medium','high']);
            $table->date('due_date')->nullable();//موعد انتهاء المهمة
            $table->foreignId('assigned_to')->constrained('external_teams')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
