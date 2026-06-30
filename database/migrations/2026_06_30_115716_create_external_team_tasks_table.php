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
        Schema::create('external_team_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->foreignId('external_teams_id')->constrained('external_teams')->onDelete('cascade');
            $table->foreignId('external_team_member_id')->constrained('external_team_members')->onDelete('cascade');//name
            $table->date('due_date')->nullable();//موعد انتهاء المهمة
            $table->enum('status',['pending','in_progress','completed','delayed'])->default('pending');//تخزين الوقت عند انجاز المهمة
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_team_tasks');
    }
};
