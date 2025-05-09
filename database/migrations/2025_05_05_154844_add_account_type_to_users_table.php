<?php

use App\Enums\UserAccountTypeEnum;
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
        // Add the account_type column to the users table
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_type', UserAccountTypeEnum::toArray())->default('main')->after('username');
            $table->uuid('main_account_id')->nullable()->after('account_type'); 
            $table->foreign('main_account_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null')
                ->name('users_main_account_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_type');
            $table->dropForeign('users_main_account_id_foreign');
            $table->dropColumn('main_account_id');
        });
    }
};
