<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('file_no');
            $table->string('full_name');
            $table->string('phone', 32);
            $table->string('whatsapp', 32)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable();
            $table->text('national_id')->nullable(); // encrypted cast
            $table->json('medical_alerts')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['clinic_id', 'file_no']);
            $table->index(['clinic_id', 'phone']);
        });

        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // data_processing|treatment|marketing
            $table->text('text_snapshot');
            $table->string('method', 20); // verbal_logged|written|digital
            $table->dateTime('given_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32)->nullable();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('category', 20)->default('other');
            $table->bigInteger('default_price_minor')->default(0);
            $table->unsignedSmallInteger('duration_min')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['clinic_id', 'code']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->cascadeOnDelete();
            $table->string('chair', 32)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('scheduled');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['clinic_id', 'branch_id', 'starts_at']);
            $table->index(['dentist_id', 'starts_at']);
        });

        Schema::create('odontogram_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('tooth_number');
            $table->string('surface', 1)->nullable(); // M|O|D|B|L or null=whole
            $table->string('condition', 20);
            $table->string('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['clinic_id', 'patient_id', 'tooth_number', 'surface'], 'odontogram_tooth_surface_unique');
        });

        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('status', 20)->default('draft');
            $table->bigInteger('total_minor')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'patient_id']);
        });

        Schema::create('treatment_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('treatment_plans')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('tooth_number')->nullable();
            $table->string('surfaces', 8)->nullable();
            $table->string('description');
            $table->bigInteger('price_minor');
            $table->bigInteger('discount_minor')->default(0);
            $table->unsignedSmallInteger('qty')->default(1);
            $table->string('status', 20)->default('proposed');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_items');
        Schema::dropIfExists('treatment_plans');
        Schema::dropIfExists('odontogram_entries');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('services');
        Schema::dropIfExists('consent_records');
        Schema::dropIfExists('patients');
    }
};
