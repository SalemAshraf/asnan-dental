<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20)->default('xray'); // xray|photo
            $table->string('title')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->date('taken_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'patient_id'], 'patient_images_clinic_patient_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_images');
    }
};
