<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_payments', function (Blueprint $table) {
            $table->string('collection_method')->default('ussd')->after('payment_type');
            $table->string('clickpesa_control_number')->nullable()->unique()->after('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_payments', function (Blueprint $table) {
            $table->dropUnique(['clickpesa_control_number']);
            $table->dropColumn(['collection_method', 'clickpesa_control_number']);
        });
    }
};
