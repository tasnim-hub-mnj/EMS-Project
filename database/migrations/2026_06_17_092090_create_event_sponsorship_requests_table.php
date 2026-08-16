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
        Schema::create('event_sponsorship_requests', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->foreignId('sponsor_event_id')->constrained('sponsor_events')->onDelete('cascade');
            $table->foreignId('investor_id')->nullable()->constrained('investors')->nullOnDelete();

            // بيانات الشركة
            $table->string('company_name');
            $table->string('company_type')->nullable(); // corporate, startup, NGO...

            // بيانات التواصل
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();

            // تفاصيل العرض
            $table->float('proposed_amount')->nullable();
            $table->text('offer_details')->nullable();
            $table->text('conditions')->nullable();

            // ملاحظات المنظم
            $table->text('organizer_notes')->nullable();
            $table->text('reject_reason')->nullable();

            // حالة الطلب
            $table->enum('status', ['new','pending','negotiating','approved','rejected'])
                ->default('new');

            // تاريخ الطلب
            $table->dateTime('request_date')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_sponsorship_requests');
    }
};
