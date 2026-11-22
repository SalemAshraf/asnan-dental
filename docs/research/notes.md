# Findings by subquestion (2026-11-22)

## 1. Competitors — Egypt (live, fetched)
- Yalla Dent: Egypt-only positioning; Arabic-first; EGP; pays via InstaPay / bank transfer / cash collector; explicitly designs for installment-paying patients and WhatsApp-centric clinics; dentists working in multiple locations. 14-day trial.
- Synora (Nano AI): free tier to 50 patients; Basic EGP 799/mo, Pro EGP 1,500/mo per clinic (not per chair/user); WhatsApp reminders; lab cases; inventory; insurance claims; dentist commissions; openly states NO ZATCA/NPHIES and no Gulf hosting — Gulf is their gap.
- MolarBear (Queue Solutions): EGP 1,250–3,200/mo billed annually; receptionist+dentist single-board positioning.
- Dentral: spans EG/SA/UAE with geo-pricing and ZATCA Phase-1 QR; free online-booking page per clinic; FDI charting; insurance; inventory; audit logs.
- Dentorithm, Dentolize, Modirify, BeClarified, OdenteQ, Dentonile, Dentix, Thanaya (Codeiks), Blue Intelligence: more Egyptian/Arab players — the Egyptian market is crowded at the low end.
- Vezeeta is adjacent (booking marketplace, commission model); ORCAA and TabeebHub sell commission-free clinic management against it.

## 2. Competitors — Gulf (KSA/UAE)
- Basmtak Clinics (KSA): from 499 SAR/branch/mo annual, 2 practitioners included; SBS-coded price lists; treatment-plan line flows to estimate → pre-auth → claim → ZATCA invoice; KSA-hosted; onboarding import with dry run.
- Enamela (UAE): AED + VAT invoices; WhatsApp reminders; EN/AR; entry ~AED 99/mo anchor and AED 699/mo plan on page; has draft Saudi PDPL addendum.
- Clinicea, metaSOFT DIS, DentoFlow, PDental, Ventrecs: also present in UAE/Gulf (search-visible).
- Gulf table stakes differ from Egypt: ZATCA e-invoice (KSA), NPHIES insurance claims (KSA, mandatory insurance market), SBS billing codes, VAT invoices (UAE 5% / KSA 15%), English+Arabic, in-region hosting as a sales argument.

## 3. Egypt commercial behavior
- Patients pay in installments — installment plans on treatment are a core workflow (Yalla Dent builds for it explicitly).
- WhatsApp is the clinic's operating channel (reminders, rebooking).
- Vendor subscription collection happens through InstaPay / bank transfer / cash collector, not self-serve cards (Yalla Dent).
- Price points cluster: free–EGP 799 entry, EGP 1,250–1,850 mid, EGP 3,200+ premium; annual billing common at higher tiers.

## 4. Regulatory (distinguish countries — not interchangeable)
- Egypt: PDPL Law 151/2020 fully enforceable since 2026-11-01 (A&O Shearman). Health data = sensitive → explicit written consent, licensing/permit conditions, necessity (secondary summaries of Art. conditions). Practical v1 duties: recorded patient consent, access control, audit trail, breach awareness. Egypt e-invoicing (ETA) currently targets larger taxpayers — small clinics mostly out of scope today (not verified for every bracket — open question).
- Saudi Arabia: PDPL + Implementing Regs Art. 27 (health data safeguards). NPHIES is the national e-claims rails (FHIR; dental billing value set exists) — needed only if the clinic bills insurance. ZATCA e-invoicing (FATOORA) Phase 2 applies by VAT-registration waves — QR on invoices is the Phase-1 baseline vendors advertise. Hosting in-Kingdom: sources contradict — iAssets says not legally mandated (NCA dropped mandates 2024); Consenta and others describe in-Kingdom hosting as expected/simpler. Recorded as open contradiction; safest product stance: offer regional hosting without claiming legal mandate.
- UAE: PDPL federal law + health data laws (ICT Health Law 2/2019) create data-localization expectations for health data; Enamela sells AED/VAT + WhatsApp + bilingual docs. Not deep-verified this round — open question.

## 5. Dental-specific feature baseline (from vendor pages)
- FDI tooth charting (permanent + primary), per-surface conditions.
- Treatment plans with prices → patient acceptance tracking → plan line becomes estimate/invoice/claim line.
- Appointments with chairs/rooms, multiple branches, doctor schedules.
- Billing: invoices, payments, installments (EG), VAT (Gulf), ZATCA QR (KSA), insurance claims (KSA).
- WhatsApp reminders; free public online-booking page.
- Extras at higher tiers: lab cases, inventory, doctor commissions, analytics/P&L, audit logs, AI copilots (marketing-heavy, unverified value).
