# Dental clinic SaaS — Egypt & Gulf market study
Date: 2026-11-22 · Working product name: Asnan (أسنان) — renameable, no name was supplied by the owner.

## One-paragraph answer
Dental clinic management is a proven, crowded category in both Egypt and the Gulf: at least 10 live Egyptian vendors (Synora, MolarBear, Yalla Dent, Dentral, Dentorithm, Dentolize, Modirify, Dentix, Thanaya, Blue Intelligence) and several Gulf ones (Basmtak in KSA, Enamela and Clinicea in UAE). Demand is real, but so is competition — the wedge is not features, it is distribution and price. Egypt buys at EGP 0–1,850/clinic/month with annual billing at higher tiers, pays vendors through InstaPay/transfer/cash collection, and runs on WhatsApp and installments. The Gulf pays more (from ~499 SAR/branch/mo; UAE anchors AED 99–699/mo) but expects VAT invoices, ZATCA e-invoicing and NPHIES insurance claims in KSA, bilingual documents, and in-region hosting as a trust signal. A solo developer should enter Egypt first at or under the EGP 799–1,250 bracket with the dental-specific core (FDI chart, treatment plans, installments, WhatsApp-friendly reminders) done properly, and treat Gulf readiness (VAT, ZATCA QR, bilingual invoices, regional hosting) as a phase-2 upsell rather than a launch requirement.

## Competitor pricing table (fetched from vendor pages, 2026-11-22)
| Vendor | Market | Price | Billing notes |
|---|---|---|---|
| Synora | Egypt/ME | Free to 50 patients; Basic EGP 799/mo; Pro EGP 1,500/mo | Per clinic, unlimited patients/staff, one branch (Pro up to 5) |
| MolarBear | Egypt | EGP 1,250 / 1,850 / 3,200 per mo | Billed annually |
| Yalla Dent | Egypt | EGP pricing (page shows geo-resolved prices) | Pays via InstaPay/bank transfer/cash collector; 14-day trial |
| Dentral | EG/SA/UAE | Per-country EGP/SAR/AED geo-pricing | Monthly, no setup fees; ZATCA QR included |
| Basmtak | KSA | From 499 SAR/branch/mo | Annual term, 2 practitioners included, 14-day trial |
| Enamela | UAE | AED 99–699/mo anchors on page | AED + VAT; WhatsApp reminders |
| Vezeeta (adjacent) | Egypt | Marketplace commission; ~EGP 200/mo doctor subscription reported 2018 | Booking marketplace, not a PMS |

## Feature baseline (table stakes for v1)
FDI chart (permanent+primary, per-surface), treatment plans with acceptance tracking, plan→estimate→invoice flow, appointments with chairs/branches, invoices+payments with installments (EG), WhatsApp-centric reminders, public booking page, Arabic-first UI. Higher tiers: lab cases, inventory, insurance claims (KSA SBS/NPHIES), doctor commissions, P&L, audit logs.

## Regulatory posture by country (not interchangeable)
- Egypt: PDPL Law 151/2020 text (ILO NatLex) classifies health data as sensitive with stricter consent conditions. A&O Shearman (secondary, major firm) reports the transition/grace period ended and full enforcement applied from Nov 1 2026 — we did not verify this against the primary Executive Regulations text or the regulator; treat the exact effective scope (licensing duties, transition mechanics) as secondary-sourced until confirmed against the primary. v1 product duties regardless: recorded consent, role-based access, audit log. Vendor norm for collecting subscription fees: InstaPay/bank transfer/cash collection, not self-serve cards (Yalla Dent).
- KSA: PDPL Art. 27 health-data safeguards (official NCC text). NPHIES e-claims (official IG; dental billing value set, active 2024-12-24) matters only for insurance-billing clinics. ZATCA e-invoicing exists in phases; vendor pages advertise Phase-1 QR support — we did not verify current wave applicability or technical specs against ZATCA primary docs. In-Kingdom hosting: sources contradict on whether it is legally mandated (iAssets says no since NCA 2024; others market it as expected) — safest stance is regional hosting without claiming a mandate.
- UAE: VAT invoices, bilingual docs, health-data localization expectations (ICT Health Law) — needs a dedicated pass before selling there.

## Implications for Asnan v1
1. Egypt-first, Arabic-first, EGP, installments + WhatsApp-friendly reminders in core.
2. Price hypothesis to test with buyers: EGP 500–1,000/clinic/month monthly, no annual lock-in (undercuts MolarBear, matches Synora Basic band).
3. Multi-branch + multi-dentist from day one (dentists work in several places).
4. Consent records, roles, and audit log are product features, not afterthoughts (PDPL in both markets).
5. Do NOT build NPHIES/ZATCA Phase-2/insurance in v1; design invoice numbering/tax fields so they can be added. No compliance claims until verified.
6. Distribution is the risk, not the code: validate with 5 real dentists before scaling features.

## Caveats / unknowns
- All pricing from vendor pages on 2026-11-22; some vendors geo-resolve prices, so exact EGP figures for Yalla Dent/Dentral need a pricing-page revisit.
- Gulf demand evidence is thinner than Egypt (fewer fetched vendors).
- Whether small Egyptian clinics fall under ETA e-invoicing today: unverified.
- KSA hosting-mandate contradiction unresolved (sources #18 vs #19 in sources.md).
- No buyer interviews yet; demand is inferred from vendor density, not from buyer interviews.
