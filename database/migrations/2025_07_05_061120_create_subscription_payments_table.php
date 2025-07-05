<?php

use App\Enums\Subscription\ModelPaymentMethodEnum;
use App\Enums\Subscription\ModelPaymentStatusEnum;
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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index()->references('id')->on('users')->onDelete('no action');
            $table->foreignUuid('subscription_id')->index()->references('id')->on('subscriptions')->onDelete('no action');
            $table->string('payment_reference')->unique()->nullable();
            $table->string('external_reference')->unique()->nullable();
            $table->enum('method', ModelPaymentMethodEnum::toArray())->index()->nullable()->default('WALLET');
            $table->bigInteger('amount');
            $table->string('currency')->default('NGN');
            $table->string('gateway_response')->nullable();
            $table->enum('status', ModelPaymentStatusEnum::toArray())->index()->nullable()->default('SUCCESSFUL');
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
