<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_bookings', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('sponsor_event_id');
            $table->string('company_phone', 20)->nullable()->after('company_name');
            $table->string('company_website')->nullable()->after('company_phone');
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_phone',
                'company_website',
            ]);
        });
    }
};
