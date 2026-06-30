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
    {//o
        Schema::create('sponsorship_requests', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->foreignId('sponsor_id')->constrained('sponsors')->onDelete('cascade');

            $table->string('company_name');
            $table->string('company_type')->nullable();
            $table->enum('proposed_tier',['gold','silver','bronze','title'])->nullable();
            $table->enum('status', ['new','pending','negotiating','approved', 'rejected'])->default('new');
            $table->float('proposed_amount')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('book_at')->nullable();//تلقائي
            $table->string('contact_name')->nullable();//اسم المسؤول
            $table->text('offer_details')->nullable();
            $table->text('conditions')->nullable();
            $table->text('contract_terms')->nullable();
            $table->text('organizer_notes')->nullable();
            $table->integer('last_sponsor')->default(0);
            $table->text('reject_reason')->nullable();


            $table->string('website')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->date('request_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsorship_requests');
    }
};
