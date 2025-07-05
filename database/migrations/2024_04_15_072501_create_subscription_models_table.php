<?php

use App\Enums\Subscription\ModelNameEnum;
use App\Enums\Subscription\ModelStatusEnum;
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
        Schema::create('subscription_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('name', ModelNameEnum::toArray())->index()->unique()->nullable();
            $table->json('features')->nullable();
            $table->boolean('has_discount')->nullable()->default(false);
            $table->Integer('discount')->nullable()->default(0);
            $table->bigInteger('amount')->nullable()->default(0);
            $table->bigInteger('discount_amount')->nullable()->default(0);
            $table->bigInteger('full_amount')->nullable()->default(0);
            $table->enum('status', ModelStatusEnum::toArray())->index()->nullable()->default('ACTIVE');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_models');
    }
};
