<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('insurance_payments', 'clickpesa_control_number')
            && ! Schema::hasColumn('insurance_payments', 'fastlipa_control_number')) {
            Schema::table('insurance_payments', function (Blueprint $table) {
                $table->renameColumn('clickpesa_control_number', 'fastlipa_control_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('insurance_payments', 'fastlipa_control_number')
            && ! Schema::hasColumn('insurance_payments', 'clickpesa_control_number')) {
            Schema::table('insurance_payments', function (Blueprint $table) {
                $table->renameColumn('fastlipa_control_number', 'clickpesa_control_number');
            });
        }
    }
};
