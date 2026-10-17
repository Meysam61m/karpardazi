<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('tankhah_deposit_amount')->nullable()->after('document_date');
            $table->date('tankhah_deposit_date')->nullable()->after('tankhah_deposit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('expense_documents', function (Blueprint $table) {
            $table->dropColumn(['tankhah_deposit_amount', 'tankhah_deposit_date']);
        });
    }
};
