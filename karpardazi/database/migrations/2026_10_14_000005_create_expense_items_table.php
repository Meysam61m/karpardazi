<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('row_number');
            $table->date('expense_date'); // میلادی
            $table->string('description', 500);
            $table->bigInteger('amount')->default(0); // ریال
            $table->bigInteger('tax')->default(0);
            $table->bigInteger('insurance')->default(0);
            $table->timestamps();

            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
    }
};
