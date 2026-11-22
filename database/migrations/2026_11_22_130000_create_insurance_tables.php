<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'name'], 'insurance_companies_clinic_name_idx');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('insurance_company_id')->nullable()->after('notes')
                ->constrained('insurance_companies')->nullOnDelete();
            $table->string('insurance_member_no')->nullable()->after('insurance_company_id');
        });

        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('claim_no')->nullable();
            $table->unsignedBigInteger('amount')->default(0); // minor units
            $table->string('status')->default('submitted'); // submitted|approved|rejected|paid
            $table->date('submitted_on');
            $table->date('decided_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'status'], 'insurance_claims_clinic_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('insurance_company_id');
            $table->dropColumn('insurance_member_no');
        });
        Schema::dropIfExists('insurance_companies');
    }
};
