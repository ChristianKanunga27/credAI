<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_payments', function (Blueprint $table) {
            $table->string('confirmation_source')->nullable()->after('provider_reference');
            $table->foreignId('confirmed_by')->nullable()->after('confirmation_source')->constrained('users')->nullOnDelete();
            $table->foreignId('insurance_loan_application_id')->nullable()->unique()->after('confirmed_by')
                ->constrained('insurance_loan_applications')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('insurance_payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropUnique(['insurance_loan_application_id']);
            $table->dropForeign(['insurance_loan_application_id']);
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['insurance_loan_application_id', 'confirmed_by', 'confirmation_source']);
        });
    }
};
