<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mobile_money_transactions')) {
            Schema::create('mobile_money_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('provider')->nullable();
                $table->string('reference');
                $table->decimal('amount', 15, 2);
                $table->string('transaction_type');
                $table->timestamp('occurred_at');
                $table->string('verification_status')->default('pending');
                $table->timestamps();
                $table->unique(['user_id', 'reference']);
            });
        }

        if (! Schema::hasIndex('mobile_money_transactions', 'mmt_user_status_occurred_idx')) {
            Schema::table('mobile_money_transactions', function (Blueprint $table) {
                $table->index(['user_id', 'verification_status', 'occurred_at'], 'mmt_user_status_occurred_idx');
            });
        }

        if (! Schema::hasTable('provider_services')) {
            Schema::create('provider_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('insurance_provider_id')->constrained()->cascadeOnDelete();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->decimal('price', 15, 2);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['insurance_provider_id', 'is_active']);
            });
        }

        Schema::table('insurance_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('insurance_payments', 'payment_type')) {
                $table->string('payment_type')->default('premium')->after('insurance_policy_id');
            }
            if (! Schema::hasColumn('insurance_payments', 'provider_service_id')) {
                $table->foreignId('provider_service_id')->nullable()->after('payment_type')
                    ->constrained('provider_services')->nullOnDelete();
            }
            if (! Schema::hasIndex('insurance_payments', ['payment_type', 'status'])) {
                $table->index(['payment_type', 'status']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('insurance_payments', function (Blueprint $table) {
            $table->dropIndex(['payment_type', 'status']);
            $table->dropForeign(['provider_service_id']);
            $table->dropColumn(['provider_service_id', 'payment_type']);
        });

        Schema::dropIfExists('provider_services');
        Schema::dropIfExists('mobile_money_transactions');
    }
};
