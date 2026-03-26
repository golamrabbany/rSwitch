<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('reseller_transaction_id')->nullable()->after('transaction_id');
            $table->foreign('reseller_transaction_id')->references('id')->on('transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['reseller_transaction_id']);
            $table->dropColumn('reseller_transaction_id');
        });
    }
};
