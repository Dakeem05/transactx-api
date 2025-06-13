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
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('principal_transaction_id')->after('wallet_transaction_id')->nullable();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('principal_transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('set null')
                  ->name('transactions_principal_transaction_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['principal_transaction_id']);
            $table->dropColumn('principal_transaction_id');
        });
    }
};
