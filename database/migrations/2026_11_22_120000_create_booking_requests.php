<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('phone', 40);
            $t->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $t->date('preferred_date')->nullable();
            $t->string('preferred_time', 10)->nullable();
            $t->text('notes')->nullable();
            $t->string('status', 20)->default('new'); // new | confirmed | declined
            $t->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $t->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $t->timestamps();
            $t->index(['clinic_id', 'status']);
        });

        Schema::table('clinics', function (Blueprint $t) {
            $t->string('booking_token', 32)->nullable()->unique();
            $t->boolean('booking_enabled')->default(false); // opt-in per clinic, in Settings
        });

        Schema::table('appointments', function (Blueprint $t) {
            $t->string('booked_via', 20)->nullable(); // null = staff booking, 'online' = public page
        });

        foreach (DB::table('clinics')->whereNull('booking_token')->pluck('id') as $id) {
            DB::table('clinics')->where('id', $id)->update(['booking_token' => Str::random(24)]);
        }
    }

    public function down(): void
    {
        Schema::table('appointments', fn (Blueprint $t) => $t->dropColumn('booked_via'));
        Schema::table('clinics', fn (Blueprint $t) => $t->dropColumn(['booking_token', 'booking_enabled']));
        Schema::dropIfExists('booking_requests');
    }
};
