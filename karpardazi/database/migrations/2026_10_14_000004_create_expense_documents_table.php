<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // ثبت‌کننده
            $table->string('karpardaz_name'); // نام کارپرداز در زمان ثبت
            $table->string('document_number', 40)->nullable()->unique(); // مثل 405-001 — پس از ثبت قطعی
            $table->unsignedInteger('serial')->nullable();
            $table->unsignedSmallInteger('fiscal_year')->nullable(); // سال شمسی سند مثل 1405
            $table->date('document_date'); // میلادی در دیتابیس، شمسی در نمایش
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('attachments_pages')->nullable(); // تعداد برگ مدارک (دستی)
            $table->string('status', 10)->default('draft'); // draft | final
            $table->timestamp('finalized_at')->nullable();
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('total_tax')->default(0);
            $table->bigInteger('total_insurance')->default(0);
            $table->timestamps();

            $table->index('document_date');
            $table->index('status');
            $table->index(['fiscal_year', 'serial']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_documents');
    }
};
