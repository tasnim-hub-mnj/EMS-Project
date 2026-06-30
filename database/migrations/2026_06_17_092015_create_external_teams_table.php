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
        Schema::create('external_teams', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('role')->nullable();
            $table->text('description')->nullable();
            $table->string('offical_name')->nullable();//اسم المسؤوول
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->float('amount')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('classification')->nullable();//التصنيف
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'active', 'finished'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_teams');
    }
};
