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
        Schema::create('sponsorship_requests', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->foreignId('sponsor_id')->constrained('sponsors')->onDelete('cascade');

            $table->string('company_name');
            $table->enum('company_type', ['corporate', 'government', 'ngo', 'startup'])->nullable();
            $table->string('website')->nullable();

            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();

            $table->enum('proposed_tier',['gold','silver','bronze','title'])->nullable();
            $table->decimal('proposed_amount', 12, 2)->nullable();
            $table->text('offer_details')->nullable();
            $table->text('conditions')->nullable();
            $table->text('contract_terms')->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->date('request_date')->nullable();

            $table->enum('status', ['new','reviewing','negotiating','accepted','rejected'])->default('new');
            $table->text('reject_reason')->nullable();
            $table->text('organizer_notes')->nullable();
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
