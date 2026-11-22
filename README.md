# أسنان (Asnan) — Dental Clinic Management SaaS

Multi-tenant dental clinic management for Egypt & the Gulf. Arabic-first.
Laravel 13 + Vue 3 (Inertia) + Tailwind 4. SQLite for dev/tests, MySQL 8-compatible migrations.

- Market study: `docs/research/report.md` (sources in `docs/research/sources.md`)
- Product plan: `docs/product-plan.md`
- Database design: `docs/database.md`

## Setup (dev)
```bash
composer install
cp .env.example .env   # then: php artisan key:generate
npm install && npm run build
php artisan migrate:fresh --seed
php artisan serve
```
Synthetic dev logins (seeded, fictional data only):
- owner@asnan.test / password (مالك)
- dentist@asnan.test / password (طبيب)
- reception@asnan.test / password (استقبال)

## Tests
```bash
php artisan test     # 30 feature tests: tenancy isolation, overlap rules, billing, privacy, audit
```

## Security & privacy posture
- Tenant isolation via global scope (route-binding safe), cross-clinic access = 404.
- National ID encrypted at rest; medical alerts visible to clinical roles only.
- Consent records + append-only audit log (PDPL evidence trail), sensitive fields excluded.
- Money in integer minor units; invoice totals server-side; sequential per-clinic invoice numbers.

No compliance certification is claimed. See docs/product-plan.md for what is deferred.
