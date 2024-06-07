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
        Schema::create('transactions', function (Blueprint $table) {
            // $table->id();
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index()->references('id')->on('users')->onDelete('no action');
            $table->foreignUuid('wallet_id')->nullable()->index()->references('id')->on('wallets')->onDelete('no action');
            $table->foreignUuid('wallet_transaction_id')->nullable()->index()->references('id')->on('wallet_transactions')->onDelete('no action');

            $table->string('type')->index()->nullable();
            $table->string('description')->index()->nullable();
            $table->bigInteger('amount')->default(0);
            $table->string('currency')->index()->nullable();
            $table->string('reference')->index()->nullable();
            $table->string('external_transaction_reference')->index()->nullable();
            $table->string('status')->index()->nullable();
            $table->string('user_ip')->index()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
