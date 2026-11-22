# بسمة (Basma) — Dental Clinic Management SaaS

> Product name approved by the owner: بسمة / Basma (working repo slug stays asnan-dental).

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

## Security & privacy posture (engineering controls, not legal compliance)
- Tenant isolation via global scope (route-binding safe), cross-clinic access = 404 (tested).
- National ID encrypted at rest; medical alerts removed server-side from non-clinical responses (tested). Any future export/API must apply the same server-side filter.
- Consent records + append-only audit log (supports a PDPL-style evidence trail; sensitive fields excluded). Audit viewer is owner-only.
- Money in integer minor units; invoice totals computed server-side; sequential per-clinic invoice numbers (plain numbering, NOT ZATCA/fiscal integration).
- Payments are manual records of money received (cash/InstaPay/transfer references), not card processing or payment-gateway integration.
- Patient deletion is soft-delete: clinical and financial history (appointments, invoices, payments, consents) is preserved; only the owner can delete (tested).

No compliance certification or legal-compliance claim is made for any jurisdiction. See docs/product-plan.md for deferred items and docs/research/report.md for source confidence levels.
