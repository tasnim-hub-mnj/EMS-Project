<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_bookings', function (Blueprint $table) {
            $table->unique(
                ['investor_id', 'sponsor_event_id'],
                'sponsorship_bookings_investor_event_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_bookings', function (Blueprint $table) {
            $table->dropUnique('sponsorship_bookings_investor_event_unique');
        });
    }
};
