# Asnan — database design (v1)

Engine target: MySQL 8 / MariaDB 10.6+ in production, SQLite in dev/tests. Migrations use portable column types only; no SQLite-only or MySQL-only features. Money stored as integer minor units (piasters/halalas/fils) in `BIGINT`; currencies are ISO-4217 codes on the clinic. All tenant data carries `clinic_id`; every query is tenant-scoped.

## Tables

### clinics
id, name, country (EG|SA|AE|...), currency (EGP|SAR|AED), timezone (default Africa/Cairo), tax_rate_bp (basis points, e.g. 1400 = 14%), invoice_prefix (default INV), next_invoice_seq, phone, address, timestamps.

### branches
id, clinic_id FK, name, address, phone, is_active, timestamps. Unique (clinic_id, name).

### users
id, clinic_id FK, branch_id FK nullable, name, email (unique per clinic), phone, password, role ENUM-ish string: owner|dentist|receptionist|assistant, is_active, remember_token, timestamps.
Roles: owner = everything incl. settings/users; dentist = clinical (odontogram, plans) + patients read + own appointments; receptionist = patients, appointments, invoices/payments; assistant = read-only clinical support (reserved).

### patients
id, clinic_id FK, branch_id FK nullable, file_no (per-clinic seq), full_name, phone, whatsapp nullable, birth_date nullable, gender nullable (male|female), national_id nullable (encrypted at rest), medical_alerts (JSON array of strings: allergies/conditions), notes nullable, timestamps, soft_deletes. Unique (clinic_id, file_no). Index (clinic_id, phone), fulltext on full_name.

### consent_records
id, clinic_id FK, patient_id FK, type (data_processing|treatment|marketing), text_snapshot (the wording the patient agreed to), method (verbal_logged|written|digital), given_at, recorded_by FK users, timestamps. Append-only: no update/delete endpoints (PDPL evidence).

### services
id, clinic_id FK, code nullable (clinic's own code; SBS/CPT mapping later for KSA), name_ar, name_en nullable, category (exam|preventive|restorative|endo|perio|surgery|prostho|ortho|cosmetic|other), default_price_minor, duration_min, is_active, timestamps. Unique (clinic_id, code) where code not null.

### appointments
id, clinic_id FK, branch_id FK, patient_id FK, dentist_id FK users, chair nullable, starts_at, ends_at, status (scheduled|confirmed|checked_in|completed|cancelled|no_show), reason nullable, notes nullable, timestamps, soft_deletes.
Invariant (app-enforced, tested): no overlap for the same dentist or the same non-null chair within a branch among active statuses. Index (clinic_id, branch_id, starts_at), (dentist_id, starts_at).

### odontogram_entries
id, clinic_id FK, patient_id FK, tooth_number (FDI: 11-18,21-28,31-38,41-48 permanent; 51-55,61-65,71-75,81-85 primary), surface nullable (M|O|D|B|L or null = whole tooth), condition (healthy|caries|filling|root_canal|crown|missing|implant|to_extract|bridge|veneer|sealant), notes nullable, recorded_by FK users, timestamps. Unique (clinic_id, patient_id, tooth_number, surface) — one current state per tooth/surface; history via audit_log.

### treatment_plans
id, clinic_id FK, patient_id FK, dentist_id FK, title, status (draft|proposed|accepted|in_progress|completed|cancelled), total_minor cached, notes nullable, timestamps.
Transitions: draft→proposed→accepted→in_progress→completed; any non-completed→cancelled. Enforced in model, tested.

### treatment_plan_items
id, plan_id FK, service_id FK nullable, tooth_number nullable, surfaces nullable (e.g. "MOD"), description, price_minor, discount_minor default 0, qty default 1, status (proposed|accepted|in_progress|completed|rejected), sort, timestamps.

### invoices
id, clinic_id FK, branch_id FK, patient_id FK, number (e.g. INV-000042, from clinic seq under lock), status (draft|issued|partially_paid|paid|void), currency (copy of clinic currency at issue), subtotal_minor, discount_minor, tax_rate_bp, tax_minor, total_minor, paid_minor cached, issued_at nullable, due_at nullable, notes nullable, timestamps. Unique (clinic_id, number).
Invariant: totals are computed server-side from items; paid_minor recomputed on each payment; status flips issued→partially_paid→paid automatically (tested). Issue requires consent on file for billing-relevant data processing (checked, logged).

### invoice_items
id, invoice_id FK, treatment_plan_item_id FK nullable, description, tooth_number nullable, qty, unit_price_minor, discount_minor, total_minor, timestamps.

### payments
id, clinic_id FK, invoice_id FK, patient_id FK (denormalized for reports), amount_minor, method (cash|card|instapay|bank_transfer|wallet), reference nullable, paid_at, received_by FK users, notes nullable, timestamps. Multiple payments per invoice = installments (core Egypt workflow).

### audit_logs
id, clinic_id FK, user_id FK nullable, action (created|updated|deleted|login|login_failed|invoice_issued|payment_received|consent_recorded), auditable_type, auditable_id nullable, changes JSON (old/new for dirty fields, sensitive fields excluded), ip, user_agent, created_at. Append-only.

## Cross-cutting
- Global tenant scope on all clinic-owned models; cross-clinic access is a 404, tested.
- Audit via model observers on patients, appointments, odontogram, plans, invoices, payments, consents.
- national_id encrypted (Laravel encrypted cast). medical_alerts are sensitive → visible to clinical roles and owner only (policy-tested).
- Soft-deleted patients excluded from booking; deletion is audited.
- Egypt PDPL: consent_records + audit_logs are the v1 evidence trail. KSA later: ZATCA QR (number/seq/tax fields already present), NPHIES claim mapping via services.code + invoice_items. UAE VAT via tax_rate_bp.
