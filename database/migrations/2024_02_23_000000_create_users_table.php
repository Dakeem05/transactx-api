<?php

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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('role_id')->nullable()->references('id')->on('roles')->onDelete('set null');
            $table->foreignUuid('referred_by_user_id')->nullable()->references('id')->on('users')->onDelete('set null');
            $table->string('name')->index()->nullable();
            $table->string('email')->index()->nullable();
            $table->string('username')->index()->unique();
            $table->enum('status', UserStatusEnum::toArray())->nullable();
            $table->string('avatar')->index()->nullable();
            $table->string('country')->index()->nullable();
            $table->string('referral_code')->index()->nullable();
            $table->string('transaction_pin')->index()->nullable();
            $table->enum('kyc_status', UserKYCStatusEnum::toArray())->index()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('push_in_app_notifications')->default(true);
            $table->boolean('push_email_notifications')->default(true);
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
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
