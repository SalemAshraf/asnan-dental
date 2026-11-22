<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country', 2)->default('EG');
            $table->string('currency', 3)->default('EGP');
            $table->string('timezone', 64)->default('Africa/Cairo');
            // Basis points. 0 = no tax until the clinic configures its own rate; no statutory rate shipped.
            $table->unsignedInteger('tax_rate_bp')->default(0);
            $table->string('invoice_prefix', 10)->default('INV');
            $table->unsignedBigInteger('next_invoice_seq')->default(1);
            $table->unsignedBigInteger('next_patient_seq')->default(1);
            $table->string('phone', 32)->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['clinic_id', 'name']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->string('password');
            $table->string('role', 20); // owner|dentist|receptionist|assistant
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->unique(['clinic_id', 'email']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('clinics');
    }
};
