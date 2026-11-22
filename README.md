# بسمة (Basma) — Dental Clinic Management SaaS

> **Brand status: provisional.** The working name بسمة / Basma is under review after a same-category name collision was found during domain research; the final brand will be decided separately. The repository slug (`asnan-dental`) is unchanged by any naming decision.

Multi-tenant dental clinic management for Egypt & the Gulf. Arabic-first, RTL.
Laravel 13 + Vue 3 (Inertia) + Tailwind 4.

- Market study (Egypt + Gulf, sourced): `docs/research/report.md` — sources in `docs/research/sources.md`
- Product plan (v1 scope + deferred v2): `docs/product-plan.md`
- Database design: `docs/database.md`

## What is implemented (v1)

- **Tenancy & staff**: clinics, branches, users with roles (owner / dentist / reception). Tenant isolation enforced by a global scope; cross-clinic access returns 404 (tested).
- **Patients**: file numbers, contact + WhatsApp, birth date, medical alerts, notes, national ID (encrypted at rest).
- **Consent records**: type, text snapshot, method, timestamp — append-only.
- **FDI odontogram**: per-tooth conditions and surfaces (M/O/D/B/L), interactive chart, RBAC-gated edits.
- **Treatment plans**: lifecycle (draft → proposed → accepted → in_progress → completed / cancelled), priced items per tooth, per-item status.
- **Appointments**: branch/dentist/chair booking with overlap protection (same dentist or same chair rejected, tested), day view, status transitions. Times are stored in UTC from clinic-local wall time (clinic timezone).
- **Billing**: invoices generated from accepted plan items, server-computed totals with per-clinic tax rate (basis points), draft → issue → partially_paid → paid, void, sequential per-clinic invoice numbers.
- **Payments**: manual records of money received (cash / InstaPay / bank transfer / wallet reference) with installment support and overpayment protection (tested). **Not** card processing or gateway integration.
- **Audit log**: append-only, sensitive fields excluded, owner-only viewer.
- **Dashboard**: today's appointments, key stats.

Synthetic seed data only — no real patient data anywhere in the repo.

## Stack

PHP 8.3 · Laravel 13 · Inertia · Vue 3 · Tailwind 4 · SQLite (dev/tests) · MySQL-compatible migrations

## Setup (dev)

```bash
composer install
cp .env.example .env   # then: php artisan key:generate
npm install && npm run build
php artisan migrate:fresh --seed
php artisan serve
```

Seeded demo logins (fictional data): `owner@asnan.test`, `dentist@asnan.test`, `reception@asnan.test` — password `password`.

## Tests

```bash
php artisan test     # 33 feature tests, 107 assertions
```

Covers: tenant isolation (404s), appointment overlap rules, invoice math/tax/installments/overpayment, plan lifecycle, odontogram upsert + RBAC, privacy (medical alerts stripped server-side, national ID encrypted, JSON search payload shape), audit, soft-delete preserving financial history, clinic-timezone storage.

> Tests currently run on SQLite. Migrations are written MySQL-compatible, but a full MySQL 8 parity run is pending and tracked in the project plan.

## Security & privacy posture (engineering controls, not legal compliance)

- Tenant isolation via global scope (route-binding safe), cross-clinic access = 404 (tested).
- National ID encrypted at rest; medical alerts removed server-side from non-clinical responses, including the appointment-search JSON payload (tested). Any future export/API must apply the same server-side filter.
- Consent records + append-only audit log (supports a PDPL-style evidence trail; sensitive fields excluded). Audit viewer is owner-only.
- Money in integer minor units; invoice totals computed server-side; sequential per-clinic invoice numbers (plain numbering, **not** ZATCA/fiscal integration).
- Payments are manual records of money received, not payment-gateway processing.
- Patient deletion is soft-delete: clinical and financial history (appointments, invoices, payments, consents) is preserved; only the owner can delete (tested).

No compliance certification or legal-compliance claim is made for any jurisdiction. See `docs/product-plan.md` for deferred items and `docs/research/report.md` for source confidence levels.

## Roadmap (deferred to v2, unverified items marked)

- WhatsApp appointment reminders (integration not built).
- Gulf requirements: e-invoicing/fiscal integration (ZATCA), NPHIES, bilingual UI — pending verification against official sources.
- Online payment collection via Egyptian gateways.
- Per-clinic timezone settings UI (storage support exists).
