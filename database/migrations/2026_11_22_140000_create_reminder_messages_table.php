<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recall_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20); // recall | appointment
            $table->string('channel', 30)->default('whatsapp_manual');
            $table->date('scheduled_for');
            $table->string('status', 20)->default('queued'); // queued | sent | skipped
            $table->string('dedupe_key', 100);
            $table->dateTime('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['clinic_id', 'dedupe_key'], 'reminder_messages_clinic_dedupe_uniq');
            $table->index(['clinic_id', 'status', 'scheduled_for'], 'reminder_messages_queue_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_messages');
    }
};
