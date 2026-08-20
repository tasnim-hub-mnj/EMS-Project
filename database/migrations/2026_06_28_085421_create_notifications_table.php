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
        Schema::create('notifications', function (Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('exhibition_id')->nullable()->constrained('exhibitions')->nullOnDelete();
            $table->foreignId('portal_link_id')->nullable()->constrained('portal_links')->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('type'); // system, task, attendance, message, booking, event, sponsor, map, report
            $table->string('permission_key')->nullable()->index();
            $table->boolean('read')->default(false);
            $table->json('data')->nullable(); //بيانات تانية
            $table->string('action_url')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'exhibition_id', 'portal_link_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
