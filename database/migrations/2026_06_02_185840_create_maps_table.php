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
        Schema::create('maps', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();// من أنشأ الخريطة
            $table->integer('version')->default(1);//رقم النسخة (version)
            $table->integer('schema_version')->default(1);// نسخة مخطط الخريطة (schema_version)
            $table->enum('status', ['draft', 'published'])->default('draft');// حالة الخريطة
            $table->timestamp('published_at')->nullable();// وقت النشر
            $table->json('map_json');// بيانات الخريطة كاملة (JSON)
            $table->timestamps(); 
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maps');
    }
};
