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
        Schema::create('linked_bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index()->references('id')->on('users')->onDelete('no action');
            $table->string('customer')->nullable();
            $table->string('account_id')->nullable();
            $table->string('data_status')->nullable();
            $table->string('auth_method')->nullable();
            $table->string('account_number')->index()->nullable();
            $table->string('account_name')->index()->nullable();
            $table->string('bank_name')->index()->nullable();
            $table->string('bank_code')->index()->nullable();
            $table->string('type')->nullable();
            $table->string('reference')->nullable();
            $table->string('currency')->index()->nullable();
            $table->string('country')->default('NG');
            $table->string('provider')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('linked_bank_accounts');
    }
};
