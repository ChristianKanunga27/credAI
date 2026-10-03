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
        Schema::table('insurance_profiles', function (Blueprint $table) {
            $table->timestamp('balance_verified_at')->nullable()->after('sim_balance');
        });

        Schema::create('insurance_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('insurance_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('mobile_money_provider')->nullable();
            $table->string('phone');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('TZS');
            $table->string('status')->default('pending');
            $table->string('provider_reference')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_loan_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('insurance_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('insurance_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->unique();
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('sim_balance_at_application', 15, 2)->default(0);
            $table->timestamp('balance_verified_at_application')->nullable();
            $table->string('disbursement_destination');
            $table->string('disbursement_phone')->nullable();
            $table->string('status')->default('submitted');
            $table->unsignedTinyInteger('repayment_months')->nullable();
            $table->decimal('monthly_repayment', 15, 2)->nullable();
            $table->string('disbursement_reference')->nullable()->unique();
            $table->text('decision_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_loan_applications');
        Schema::dropIfExists('insurance_payments');

        Schema::table('insurance_profiles', function (Blueprint $table) {
            $table->dropColumn('balance_verified_at');
        });
    }
};
