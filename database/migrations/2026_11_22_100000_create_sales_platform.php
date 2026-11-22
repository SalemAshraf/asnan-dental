<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_admins', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name_ar');
            $t->string('name_en');
            $t->text('blurb_ar')->nullable();
            $t->text('blurb_en')->nullable();
            $t->decimal('price', 12, 2)->nullable();      // null = "contact us", never invented
            $t->decimal('price_yearly', 12, 2)->nullable();
            $t->string('currency', 3)->nullable();
            $t->string('period', 10)->nullable();         // monthly | yearly
            $t->unsignedSmallInteger('trial_days')->nullable();
            $t->unsignedInteger('max_users')->nullable();   // stored, not enforced in v1
            $t->unsignedInteger('max_patients')->nullable(); // stored, not enforced in v1
            $t->json('features_ar')->nullable();
            $t->json('features_en')->nullable();
            $t->boolean('is_public')->default(false);
            $t->boolean('is_active')->default(true);
            $t->boolean('signup_default')->default(false);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $t->string('status', 20)->default('trialing'); // pending | trialing | active | past_due | cancelled
            $t->boolean('trial_enforced')->default(false);
            $t->timestamp('trial_ends_at')->nullable();
            $t->timestamp('current_period_end')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
        });

        Schema::create('sales_inquiries', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('clinic_name')->nullable();
            $t->string('phone', 40)->nullable();
            $t->string('email')->nullable();
            $t->string('plan_slug', 40)->nullable();
            $t->text('message')->nullable();
            $t->string('status', 20)->default('new'); // new | contacted | closed
            $t->timestamps();
        });

        Schema::create('platform_activity', function (Blueprint $t) {
            $t->id();
            $t->foreignId('platform_admin_id')->nullable();
            $t->string('action', 60);
            $t->unsignedBigInteger('clinic_id')->nullable();
            $t->json('meta')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamps();
        });

        Schema::table('clinics', function (Blueprint $t) {
            $t->timestamp('suspended_at')->nullable();
        });

        // Existing clinics keep full access: they get an active, non-trial subscription so the
        // read-only trial rule can never touch data that predates the sales platform.
        $now = now();
        foreach (DB::table('clinics')->pluck('id') as $clinicId) {
            DB::table('subscriptions')->insert([
                'clinic_id' => $clinicId,
                'plan_id' => null,
                'status' => 'active',
                'trial_enforced' => false,
                'trial_ends_at' => null,
                'current_period_end' => null,
                'note' => 'Existing clinic before the sales platform; activated by default.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('clinics', fn (Blueprint $t) => $t->dropColumn('suspended_at'));
        foreach (['platform_activity', 'sales_inquiries', 'subscriptions', 'plans', 'platform_admins'] as $n) {
            Schema::dropIfExists($n);
        }
    }
};
