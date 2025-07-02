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
        Schema::create('virtual_bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->index()->references('id')->on('wallets')->onDelete('no action');
            $table->string('currency')->index()->nullable();
            $table->string('account_number')->index()->nullable();
            $table->string('account_name')->index()->nullable();
            $table->string('bank_name')->index()->nullable();
            $table->string('bank_code')->index()->nullable();
            $table->string('provider')->index()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_bank_accounts');
    }
};
