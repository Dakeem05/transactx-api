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
        Schema::create('money_request_styles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->index()->nullable();
            $table->string('content')->index()->nullable();
            $table->string('picture')->index()->nullable();
            $table->boolean('status')->index()->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('money_request_styles');
    }
};
