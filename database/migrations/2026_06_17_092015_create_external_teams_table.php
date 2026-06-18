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
            $table->enum('role',[''])->nullable();
            $table->json('category')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->decimal('contract_value', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['pending', 'active', 'completed', 'rejected'])->default('pending');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->date('request_date')->nullable();
            $table->json('members')->nullable(); // [ { id, name, role, phone, email } ]
            $table->json('tasks')->nullable();   // [ { id, title, status, assignedTo, due } ]
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
