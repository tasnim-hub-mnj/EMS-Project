<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE sponsorship_bookings AS bookings
            INNER JOIN investors AS investors ON investors.id = bookings.investor_id
            INNER JOIN users AS users ON users.id = investors.user_id
            SET
                bookings.company_name = COALESCE(NULLIF(bookings.company_name, ''), investors.company_name),
                bookings.company_phone = COALESCE(NULLIF(bookings.company_phone, ''), users.phone),
                bookings.company_website = COALESCE(NULLIF(bookings.company_website, ''), investors.website)
            WHERE bookings.company_name IS NULL
               OR bookings.company_name = ''
               OR bookings.company_phone IS NULL
               OR bookings.company_phone = ''
               OR bookings.company_website IS NULL
               OR bookings.company_website = ''
        SQL);
    }

    public function down(): void
    {
        // Backfilled values are retained when rolling back this migration.
    }
};
