<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lab_name');
            $table->string('work_type', 30);
            $table->string('shade', 60)->nullable();
            $table->date('sent_on');
            $table->date('due_on')->nullable();
            $table->string('status', 20)->default('sent');
            $table->unsignedBigInteger('cost_minor')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'status'], 'lab_cases_clinic_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_cases');
    }
};
