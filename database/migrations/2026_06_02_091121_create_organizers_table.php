<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {//o
        Schema::create('organizers', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('company_name')->nullable();
            $table->json('category')->nullable();//exhibitoin->type
            $table->string('headquarters')->nullable();
            $table->integer('reg_number')->unique()->nullable();
            $table->string('location');//EXHIBITION->LOCATION
            $table->string('logo');
            $table->json('file');//العقد
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizers');
    }
};
