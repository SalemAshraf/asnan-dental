<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30);
            $table->unsignedBigInteger('amount_minor');
            $table->date('spent_on');
            $table->string('vendor')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_id', 'spent_on'], 'expenses_clinic_spent_idx');
            $table->index(['clinic_id', 'category'], 'expenses_clinic_category_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
