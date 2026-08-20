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
        Schema::create('portal_links', function (Blueprint $table)
        {
            $table->id();
            $table->uuid('token')->unique()->nullable();// الرابط الأساسي (UUID)

            $table->foreignId('staff_id')->constrained('staff_members')->onDelete('cascade');
            $table->string('firebase_uid')->nullable()->index();
            $table->string('staff_name');
            $table->string('staff_email')->nullable();
            $table->string('staff_title')->nullable(); // مشرف الموارد البشرية
            $table->string('staff_number')->nullable(); // s1

            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->string('exhibition_name');

            $table->string('role'); // administrative / organizational / services / external
            $table->json('permissions')->nullable(); // ["admin.staff", "admin.attendance", ...]
            $table->json('messaging_channels')->nullable(); // ["visitors-complaints", "team-admin"]

            $table->boolean('is_manager')->default(false);// المديرين
            $table->boolean('is_used')->default(false);

            $table->string('created_by')->nullable(); // org_user_1/من أنشأ الرابط
            $table->string('created_by_name')->nullable(); // إدارة المعرض الرئيسي

            $table->uuid('parent_token')->nullable();// روابط فرعية
            $table->boolean('active')->default(true);// حالة الرابط
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_links');
    }
};
