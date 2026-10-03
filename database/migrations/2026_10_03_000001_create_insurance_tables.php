<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('individual')->change();
        });

        Schema::create('insurance_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->decimal('sim_balance', 15, 2)->default(0);
            $table->string('coverage_goal')->default('family_protection');
            $table->string('risk_level')->default('moderate');
            $table->decimal('monthly_premium', 15, 2)->default(0);
            $table->decimal('coverage_amount', 15, 2)->default(0);
            $table->string('recommended_plan')->nullable();
            $table->text('ai_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('organization_name');
            $table->string('license_number')->nullable();
            $table->string('provider_type')->default('insurer');
            $table->decimal('available_funds', 15, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('insurance_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('policy_number')->unique();
            $table->string('coverage_goal')->default('family_protection');
            $table->decimal('premium', 15, 2)->default(0);
            $table->decimal('coverage_amount', 15, 2)->default(0);
            $table->string('status')->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('claim_number')->unique();
            $table->string('claim_type')->default('medical');
            $table->decimal('claim_amount', 15, 2)->default(0);
            $table->string('status')->default('submitted');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('insurance_policies');
        Schema::dropIfExists('insurance_providers');
        Schema::dropIfExists('insurance_profiles');

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('business')->change();
        });
    }
};
