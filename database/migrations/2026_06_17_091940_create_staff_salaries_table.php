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
        Schema::create('staff_salaries', function (Blueprint $table)
        {
            $table->id();
            // $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff_members')->onDelete('cascade');//full_name
            $table->string('year');
            $table->string('month');
            $table->string('year');
            $table->enum('type_staff',['administrative','technical','services','organizational','security'])->nullable();
            $table->float('salary');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_salaries');
    }
};
