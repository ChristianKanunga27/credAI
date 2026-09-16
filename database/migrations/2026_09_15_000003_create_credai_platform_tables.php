<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('business')->after('email');
            $table->string('phone')->nullable()->after('role');
            $table->string('avatar_url')->nullable()->after('phone');
        });

        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->string('sector')->nullable();
            $table->decimal('capital', 15, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('fund_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('organization_name');
            $table->string('provider_type')->default('lender');
            $table->decimal('available_funds', 15, 2)->default(0);
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->decimal('max_amount', 15, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('license_number')->nullable();
            $table->string('address')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('funding_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('fund_providers')->nullOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('requested_amount', 15, 2);
            $table->string('purpose');
            $table->string('status')->default('draft');
            $table->decimal('eligibility_ratio', 5, 2)->default(0);
            $table->timestamp('hospital_approved_at')->nullable();
            $table->timestamp('funded_at')->nullable();
            $table->text('ai_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('business_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number');
            $table->string('provider')->nullable();
            $table->string('reference')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('transaction_type')->default('credit');
            $table->timestamp('occurred_at');
            $table->string('verification_status')->default('pending');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payer_id')->constrained('users')->cascadeOnDelete();
            $table->string('gateway')->default('pending');
            $table->string('reference')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending');
            $table->json('gateway_response')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('business_transactions');
        Schema::dropIfExists('funding_applications');
        Schema::dropIfExists('hospitals');
        Schema::dropIfExists('fund_providers');
        Schema::dropIfExists('businesses');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'avatar_url']);
        });
    }
};
