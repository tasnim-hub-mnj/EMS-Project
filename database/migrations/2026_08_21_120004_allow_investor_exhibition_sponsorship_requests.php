<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_requests', function (Blueprint $table) {
            $table->foreignId('investor_id')
                ->nullable()
                ->after('exhibition_id')
                ->constrained('investors')
                ->nullOnDelete();
            $table->foreignId('sponsor_id')->nullable()->change();
            $table->unique(
                ['investor_id', 'exhibition_id'],
                'sponsorship_requests_investor_exhibition_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_requests', function (Blueprint $table) {
            $table->dropUnique('sponsorship_requests_investor_exhibition_unique');
            $table->dropForeign(['investor_id']);
            $table->dropColumn('investor_id');
            $table->foreignId('sponsor_id')->nullable(false)->change();
        });
    }
};
