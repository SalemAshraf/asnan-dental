<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Read-only AI connector (Claude): per-clinic on/off switch plus OAuth 2.1 (authorization code + PKCE) tables. DATETIME columns for MariaDB 10.6 parity. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $t) {
            if (! Schema::hasColumn('clinics', 'ai_enabled')) {
                $t->boolean('ai_enabled')->default(false)->after('booking_enabled');
            }
        });

        if (! Schema::hasTable('oauth_clients')) {
            Schema::create('oauth_clients', function (Blueprint $t) {
                $t->id();
                $t->string('client_id', 64)->unique();
                $t->string('name', 100);
                $t->json('redirect_uris');
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('oauth_codes')) {
            Schema::create('oauth_codes', function (Blueprint $t) {
                $t->id();
                $t->string('code_hash', 64)->unique();
                $t->string('client_id', 64);
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $t->string('redirect_uri', 500);
                $t->string('code_challenge', 128);
                $t->string('host', 255);
                $t->dateTime('expires_at');
                $t->dateTime('used_at')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('oauth_tokens')) {
            Schema::create('oauth_tokens', function (Blueprint $t) {
                $t->id();
                $t->string('family', 40)->index();
                $t->string('access_hash', 64)->unique();
                $t->string('refresh_hash', 64)->unique();
                $t->string('client_id', 64);
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $t->string('host', 255);
                $t->dateTime('access_expires_at');
                $t->dateTime('refresh_expires_at');
                $t->dateTime('rotated_at')->nullable();
                $t->dateTime('revoked_at')->nullable();
                $t->dateTime('last_used_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
        Schema::dropIfExists('oauth_codes');
        Schema::dropIfExists('oauth_clients');
        Schema::table('clinics', function (Blueprint $t) {
            $t->dropColumn('ai_enabled');
        });
    }
};
