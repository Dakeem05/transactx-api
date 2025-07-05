<?php

use App\Enums\Subscription\ModelPaymentMethodEnum;
use App\Enums\Subscription\ModelUserStatusEnum;
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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index()->references('id')->on('users')->onDelete('no action');
            $table->foreignUuid('subscription_model_id')->index()->references('id')->on('subscription_models')->onDelete('no action');
            $table->string('payment_gateway_id')->nullable();
            $table->string('payment_intent')->nullable();
            $table->enum('method', ModelPaymentMethodEnum::toArray())->index()->nullable()->default('WALLET');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('renewal_date')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->enum('status', ModelUserStatusEnum::toArray())->index()->nullable()->default('PENDING');
            $table->boolean('is_auto_renew')->default(true);
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
        Schema::dropIfExists('subscriptions');
    }
};
