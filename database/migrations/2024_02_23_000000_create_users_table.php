<?php

use App\Enums\UserKYBStatusEnum;
use App\Enums\UserKYCStatusEnum;
use App\Enums\UserStatusEnum;
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
        // Step 1: Create the users table without the self-referential foreign key
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('role_id')->nullable()->references('id')->on('roles')->onDelete('set null');
            $table->uuid('referred_by_user_id')->nullable(); // Define the column without the foreign key constraint
            $table->string('name')->index()->nullable();
            $table->string('email')->index()->nullable();
            $table->string('username')->index()->unique();
            $table->enum('status', UserStatusEnum::toArray())->nullable();
            $table->string('avatar')->index()->nullable();
            $table->string('country')->index()->nullable();
            $table->string('referral_code')->index()->nullable();
            $table->string('transaction_pin')->index()->nullable();
            $table->string('bvn')->index()->nullable();
            $table->enum('kyc_status', UserKYCStatusEnum::toArray())->index()->nullable();
            $table->enum('kyb_status', UserKYBStatusEnum::toArray())->index()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('push_in_app_notifications')->default(true);
            $table->string('last_logged_in_device')->index()->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Step 2: Add the self-referential foreign key
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('referred_by_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->name('users_referred_by_user_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};