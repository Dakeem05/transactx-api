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
        Schema::create('mono_api_call_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index()->references('id')->on('users')->onDelete('no action');
            $table->foreignUuid('linked_bank_account_id')->index()->references('id')->on('linked_bank_accounts')->onDelete('no action');
            $table->boolean('has_new_data')->default(false);
            $table->string('job_status')->nullable();
            $table->string('job_id')->nullable();
            $table->string('type')->default('transactions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mono_api_call_logs');
    }
};
