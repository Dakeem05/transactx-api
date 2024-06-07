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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->index()->references('id')->on('wallets')->onDelete('no action');
            $table->string('currency')->index()->nullable();
            $table->string('type')->index()->nullable();
            $table->bigInteger('previous_balance')->index()->nullable();
            $table->bigInteger('new_balance')->index()->nullable();
            $table->bigInteger('amount_change')->index()->nullable();
            $table->string('external_transaction_reference')->index()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
