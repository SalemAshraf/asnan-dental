# Basma — product plan (v1 → v2)

Owner-approved scope (2026-11-22): research + plan + database design + independent private repo + start of implementation. Dental clinics specifically, not general medicine.

## Positioning (from market study, docs/research/report.md)
Egypt-first, Arabic-first dental clinic OS. Price hypothesis to validate with real dentists: EGP 500–1,000/clinic/month, monthly billing, no annual lock-in. Competitors: Synora (EGP 799), MolarBear (EGP 1,250+ annual), Yalla Dent, Dentral (EG/SA/UAE). The wedge is price + installments + WhatsApp-centric flow + honest simplicity, not feature count.

## v1 (this repo, implemented + tested)
- Multi-tenant clinics, branches, users with roles (owner/dentist/receptionist/assistant).
- Patients: file numbers per clinic, encrypted national ID, medical alerts (clinical-only visibility), soft delete (owner).
- Consent records with text snapshot, method, timestamp, recorder (Egypt PDPL 151/2020 evidence trail).
- FDI odontogram (permanent + primary, per-surface), upsert by tooth/surface, clinical roles only.
- Treatment plans with enforced lifecycle (draft→proposed→accepted→in_progress→completed/cancelled) and priced items linked to teeth/services.
- Appointments with dentist/chair overlap protection and status flow.
- Invoices: created from accepted plan items, server-side totals + configurable VAT (basis points), sequential per-clinic numbering, issue/void. (This is plain numbering only — no ZATCA QR, e-invoicing or any fiscal-integration claim; those need their own verified build.)
- Payments: installments (multiple payments per invoice — core Egypt workflow), methods incl. InstaPay, auto status (partially_paid/paid), overpayment blocked.
- Audit log: append-only, model events + auth events, sensitive fields excluded.
- Arabic RTL UI (Vue 3 + Inertia + Tailwind), dashboard with day schedule and revenue stats.

## v2 candidates (not built, ordered by buyer value from research)
1. WhatsApp reminders (clinic's own number first — Meta billing to clinic, like Synora Basic).
2. Free public online-booking page per clinic.
3. Lab cases + inventory.
4. Doctor commissions report.
5. KSA pack (each item needs verification against primary specs before any claim): ZATCA QR/e-invoicing, NPHIES claims mapping, SAR pricing, regional hosting decision (hosting-mandate question unresolved — see research).
6. Excel/CSV patient import with dry run (Basmtak-style onboarding).

## Explicitly out of scope
- Diagnosis/treatment advice, prescriptions, AI clinical suggestions.
- Compliance claims (PDPL/ZATCA/NPHIES) until each is implemented and verified per country.
- Live messaging integrations, real patient data, production deploy (no authority granted).
