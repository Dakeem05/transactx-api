<?php

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
            $table->string('email')->nullable();
            $table->string('username')->unique();
            $table->enum('status', UserStatusEnum::toArray())->nullable();
            $table->string('avatar')->nullable();
            $table->string('referral_code')->nullable();
            $table->timestamp('email_verified_at')->nullable();
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
