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
        Schema::table('service_providers', function (Blueprint $table) {
            $table->double('percentage_charge')->default(0.00)->after('status')
                ->comment('Percentage charge applied to transactions with this service provider');
            $table->double('fixed_charge')->default(0.00)->after('percentage_charge')
                ->comment('Fixed charge applied to transactions with this service provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            $table->dropColumn(['percentage_charge', 'fixed_charge']);
        });
    }
};
