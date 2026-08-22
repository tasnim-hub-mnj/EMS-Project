<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booth_bookings', function (Blueprint $table) {
            $table->text('company_services_products')->nullable()->after('services_products');
        });
    }

    public function down(): void
    {
        Schema::table('booth_bookings', function (Blueprint $table) {
            $table->dropColumn('company_services_products');
        });
    }
};
