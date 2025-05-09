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
        Schema::table('virtual_bank_accounts', function (Blueprint $table) {
            $table->string('barter_id')->nullable()->after('wallet_id');
            $table->string('account_reference')->nullable()->after('barter_id');
            $table->string('country')->default('NG')->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('barter_id');
            $table->dropColumn('account_reference');
            $table->dropColumn('country');
        });
    }
};
