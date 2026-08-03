---
name: bhcis-ui-style
description: BHCIS System Sta. Ana UI/UX style guide. Use when adding or changing UI (Blade views, forms, buttons, tables, login, dashboard, reports, print handouts) for maternal tracking (pre-natal/post-natal), child & adult immunization, consultations & referrals, and other barangay services. Ensures typography, colors, clinical workflows, accessibility, and DOH compliance stay consistent.
---

# BHCIS — Barangay Health Center Information System — UI/UX Style Guide

Apply this guide whenever creating or editing frontend views for **BHCIS System Sta. Ana** (Barangay Health Center Information System). The app serves the barangay health team — BHWs, midwives, nurses, doctors, and admins — as a college capstone project; we do not have any affiliation with RHU/DOH, and it must feel trustworthy, efficient, and aligned with public-health reporting standards.

**Scope**: consultations & referrals, **maternal tracking (pre-natal and post-natal)**, **child and adult immunization**, and other barangay services (family planning, nutrition, NCD follow-up, home visits).

## System context

| Item         | Standard                                                                                       |
| ------------ | ---------------------------------------------------------------------------------------------- |
| Product name | **BHCIS** (sidebar); **BHCIS** in page titles                                                  |
| Facility     | Barangay Sta. Ana Health Center, Tagoloan                                                      |
| DOH programs | FHSIS, National Immunization Program (EPI), MNCHN, Garantisadong Pambata, PhilHealth Maternity Care / Newborn packages |
| Reference    | DOH Manual of Standards for Primary Care Facilities (2020); DOH BHW Reference Manual (2022); FHSIS recording & reporting |
| Users        | BHW, midwife, nurse, doctor, admin — often non-technical, time-pressured                       |
| Primary goal | Fast, accurate patient care and RHU-compliant record-keeping                                   |

## Two UI modes

Use the correct mode for the surface being built.

### 1. App shell (interactive screens)

Dashboard, patient lists, registries, forms, settings, reports index — anything inside `@extends('layouts.app')`.

- Modern, calm, clinical — white surfaces with DOH green chrome
- Tailwind utility classes + CSS variables from `resources/views/layouts/app.blade.php`
- Rounded cards, soft shadows, subtle motion

### 2. DOH official forms & print/PDF

Patient handouts, enrollment forms, lab slips, morbidity PDFs, immunization records, pregnancy tracking forms — anything that leaves the facility or follows iClinicSys layout.

- **Do not** restyle these with app-shell tokens
- Use black `1px` borders, fixed grid layouts, small point sizes
- Include DOH branding via `resources/views/consultations/handout/partials/_doh-header.blade.php`
- Bilingual subtext (English / Bisaya) where the DOH template requires them
- DOH logo: `img/Department_of_Health_(DOH)_PHL.svg.webp`

---

## Design direction

- **Tone**: Refined, calm, trustworthy — standard government health-system feel, not startup/SaaS
- **Primary brand color**: DOH green `#0d4a3c` — conveys health authority and consistency with DOH materials
- **Density**: Information-dense but scannable; health workers need quick reads during busy clinic hours
- **Registry-first**: the app is a record-keeping system — master lists (patients, pregnant women, children, seniors) drive most screens; every list row should surface its next-action date and status
- **Avoid**: Generic AI look (Inter, Roboto, Arial as primary fonts; purple/sky gradients; decorative UI that slows workflows)

---

## Typography

- **Display & body**: Poppins — `font-display`, `font-sans`, or `font-family: var(--font-display)`
- **Page titles**: `class="font-display font-semibold text-2xl lg:text-3xl text-ink"`
- **Subtitles / descriptions**: `class="text-sm mt-1 text-ink-muted"`
- **Section labels / KPI captions**: `class="text-[11px] font-semibold uppercase tracking-wider text-ink-muted"` — 11px is the floor; never go smaller
- **DOH print forms**: 8–12px fixed sizes; do not use Tailwind display scale

Do not introduce Inter, Roboto, or Arial as primary fonts in the app shell.

Text sizes are fluid (`clamp()` autoscaling in the layout) — do not add fixed `px` overrides in the app shell.

---

## Colors (CSS variables)

Use variables from `resources/views/layouts/app.blade.php`; do not hardcode hex/rgb for brand or UI surfaces.

| Variable                                    | Use                                                  |
| ------------------------------------------- | ---------------------------------------------------- |
| `--bg-page`                                 | Page background (white)                              |
| `--bg-surface`                              | Cards, form areas, footer                            |
| `--bg-surface-elevated`                     | Main content card, dropdowns, modals                 |
| `--bg-sidebar`                              | Sidebar (`#0d4a3c`)                                  |
| `--bg-header`                               | Top header bar (`#0a3d32`)                           |
| `--ink`                                     | Primary text, headings                               |
| `--ink-muted`                               | Secondary text, labels                               |
| `--ink-subtle`                              | Decorative only (icons, placeholders, disabled text) |
| `--border`                                  | Dividers, input borders                              |
| `--primary`                                 | Links, secondary actions, DOH green accent           |
| `--primary-hover`                           | Hover state for primary elements                     |
| `--accent`                                  | Primary CTAs (Register, Sign in) — same green family |
| `--teal-soft`                               | Soft green fill (badges, table headers, KPI icons)   |
| `--accent-soft`                             | Soft warm fill (referred / warning / due badges)     |
| `--shadow-sm`, `--shadow-md`, `--shadow-lg` | Elevation                                            |

### Prefer utility classes over inline styles

The Tailwind config in `layouts/app.blade.php` maps every token to a utility. **Use the class first; use `style="..."` only when no utility exists.**

| Utility class                              | Token                        |
| ------------------------------------------ | ---------------------------- |
| `text-ink`, `text-ink-muted`, `text-ink-subtle` | `--ink*`                |
| `text-primary`, `bg-primary`, `border-primary`, `hover:bg-primary/10` | `--primary` |
| `bg-page`, `bg-surface`, `bg-surface-elevated` | `--bg-*`                 |
| `border-border`, `border-b border-border`  | `--border`                   |
| `bg-teal-soft`, `text-primary` (on teal)   | `--teal-soft`                |
| `shadow-sm`, `shadow-md`, `shadow-lg`      | `--shadow-*`                 |

### Surface hierarchy

All background tokens are white today, so elevated surfaces only read as distinct when they have a border.

- **Every nested card/panel gets `border border-border`** — never stack white on white with shadow alone
- Main content card (already in the layout) uses `rounded-2xl bg-surface-elevated shadow-sm`
- Dropdowns and modals: `border border-border shadow-md` on `bg-surface-elevated`

### `--ink-subtle` contrast rule

`#94a3b8` on white is ≈ 2.6:1 — **fails WCAG AA** for body copy. Use it only for decorative icons, placeholders, and disabled text. Helper/secondary copy uses `--ink-muted` (`#475569`, ≈ 7:1).

### Errors are red, never green

`--accent`/`--primary` is the DOH green — it must **not** be used for form errors or destructive messages. Use the red palette: text `#b91c1c`, background `#fef2f2`.

```html
<label for="zone_number" class="block text-xs font-medium mb-1 text-ink-muted">Zone number <span class="text-red-700">*</span></label>
<input type="text" id="zone_number" class="rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-primary text-ink" value="{{ old('zone_number') }}">
@error('zone_number')
    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
@enderror
```

**Button hierarchy**

| Action type                           | Style                                                                                                   |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| Primary CTA (Register, Sign in, Save) | Filled green: `bg-primary text-white`, hover `hover:opacity-95`, `active:scale-[0.98]`                  |
| Secondary (Search, Apply, View)       | Outlined: `border border-primary text-primary hover:bg-primary/10` — **not** a second filled button     |
| Destructive (Delete)                  | Red text/button; require SweetAlert confirmation                                                        |
| Disabled / no permission              | `.disabled` class (grayscale, `cursor: not-allowed`) + SweetAlert `Unauthorized` on click                |

**Clinical status colors** (use only for health-critical meaning)

| State                    | Treatment                                                                                         |
| ------------------------ | ------------------------------------------------------------------------------------------------- |
| Up to date / on track    | `var(--primary)`, `var(--teal-soft)`                                                              |
| Due / upcoming (≤7 days) | `var(--accent-soft)`, warm accent text — always pair with a calendar/schedule icon + label        |
| Overdue / missed         | Red palette (`#fef2f2` bg, `#b91c1c` / `#991b1b` text) — always pair color with icon + label text  |
| Warning / referred       | `var(--accent-soft)`, warm accent text                                                            |
| Critical                 | Red palette — always pair color with icon + label text                                            |

---

## Layout & navigation

- All authenticated pages: `@extends('layouts.app')` → `@section('content')`
- Content sits in the centered main card (`max-w-5xl`, `rounded-2xl`, `bg-surface-elevated`)
- Page structure: title block → optional filters → sections with `space-y-4 lg:space-y-6` or `space-y-5 lg:space-y-6`
- **Sidebar**: collapsible; grouped nav — Dashboard, Services (Household, Patients, Check-ups, Referrals, Immunizations, Maternal), Management, Administration, Settings
- **Header**: DOH green bar with notifications, profile dropdown, logout
- **Breadcrumbs**: auto-generated via `BreadcrumbHelper`; link segments use `text-primary`
- **Footer**: `© {year} Barangay Sta. Ana Health Center. All rights reserved.`

> **Known gap**: the Immunizations module (routes + views) exists but is not yet linked in the sidebar; Maternal is planned. Add them to the Services group as they land.

### Permission-aware UI

- Hide or disable nav items the user cannot access (`hasPermission()`)
- Disabled links: `.disabled` + SweetAlert `Unauthorized` on click — do not silently fail
- Role-sensitive controls (e.g. user permissions) must stay disabled when self-editing

---

## Program modules (DOH-aligned workflows)

These are the UI patterns for BHCIS's expanded scope. Each program follows the same shape: **registry table → due-date/status column → contextual next action → printable DOH record**.

### Maternal tracking (pre-natal & post-natal)

- **Registry**: master list of pregnant women — one row per client: name, zone, LMP/EDD, gravida/parity, weeks gestation, next visit due date
- **Schedule tracking**: EDD-derived visit timeline (pre-natal visits; Tetanus Toxoid doses per PhilHealth Maternity Care Package); due = accent-soft badge, overdue = red + icon + label
- **Visit record**: pre-natal entries (weight, BP, fundal height, TT dose, findings); post-natal visits at 24h / 7d / 14d / 28d per MNCHN
- **Forms**: LMP/EDD/gravida/parity inputs on the client record; high-risk flags route into the existing referral flow
- **Print**: Pregnancy Tracking Form / maternal care record in DOH print mode (fixed grids, black borders)

### Child immunization

- **NIP schedule** (National Immunization Program / EPI): birth — BCG, HepB birth dose; 6 / 10 / 14 weeks — pentavalent, OPV, PCV13; 9 months — MR; 12 months — JE; boosters at 4–6 years
- **Registry**: master lists for newborns (0–28 days), infants, under-5; Fully Immunized Child (FIC) completion indicator per child
- **Dose tracking**: one row per vaccine dose — scheduled date vs administered date; missing/missed doses flagged overdue
- **Print**: immunization record card (baby card) in DOH print mode

### Adult & senior immunization

- Td boosters, HPV (school-based), pneumococcal and influenza for senior citizens; recall/follow-up lists with due-date badges

### Other barangay services

- **Family planning**: method registry, follow-up dates, supply tracking
- **Nutrition / Garantisadong Pambata**: weight-for-age tracking for under-five children
- **NCD follow-up**: hypertension / diabetes monitoring lists with return-visit dates
- **Home visits**: BHW visit log per household/pregnancy (per BHW Reference Manual workflows)

All of the above reuse the same components — see Lists & tables, Status badges, and Components below.

---

## EMR UX patterns

Follow these for clinical and administrative screens.

### Workflow efficiency

- Minimize clicks for high-frequency tasks (register patient, open queue, record vitals, log a vaccine dose)
- Keep related fields on one scrollable page; avoid unnecessary tab fragmentation
- Filters that affect lists should auto-submit on change where appropriate (see Reports period form)
- Show contextual next actions on dashboard KPI cards (e.g. "Register first patient", "Manage appointments")
- Schedule-aware screens (immunization, maternal) should sort by next due date and surface overdue rows first

### Data entry & forms

- Labels above inputs: `class="text-xs font-medium mb-1 text-ink-muted"`
- Inputs: `class="rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-primary text-ink"`
- Required fields: mark visually with red `*`; server-side validation with `@error` display (red text, see Colors)
- Date-derived fields (LMP/EDD, due dates, age-based vaccine eligibility): compute on the server and display read-only where possible
- Checkboxes: normalized to 14–18px in layout CSS with `accent-color: var(--primary)`; add `accent-primary` only if rendering outside the app shell
- Long forms (patient create, pregnancy record): logical sections with clear headings; disable dependent fields when not applicable (e.g. PhilHealth fields)

### Lists & tables

- Table header row: `class="bg-teal-soft"`; header cells `class="text-ink-muted"`
- Row hover: subtle background transition (`hover:bg-black/5`)
- Registries always carry a **next-action date column** with a status badge (see Clinical status colors)
- Empty states: centered icon + short title + helper text + primary CTA button — never a blank table
- Status badges: Up to date → teal soft; Due/referred → accent soft; Overdue/neutral → red / `bg-black/5`

### Alerts & feedback

- **SweetAlert2** (`Swal.fire`) for confirmations, errors, and unauthorized access
- **Inline validation**: field-level errors below inputs (red, see Colors)
- **Clinical/critical alerts**: icon + text label; do not rely on color alone
- **Live consultation toast**: persistent until user acts (Accept / Cancel) — do not auto-dismiss clinical notifications
- **Dashboard KPIs**: use the `.kpi-card` classes (see Components); the left `border-left` accent is handled by the card markup

### Reports (DOH / FHSIS)

- Page subtitle must reference DOH FHSIS where applicable
- Report outputs follow official formats (morbidity by ICD, program summary)
- Cover the three FHSIS components: demographic data, morbidity & mortality, **coverage of public health services** — e.g. maternal care coverage (pre-natal registrants, TT2+), Fully Immunized Child / Child Protected at Birth (CPAB), consultations by ICD
- PDF/print headers include: facility name, "Department of Health • FHSIS", generation date
- See `resources/views/reports/`, `resources/views/pdfs/`

---

## Motion

- **Page / section entrance**: `class="animate-in opacity-0"`
- **Stagger**: only `delay-1` (0.05s) and `delay-2` (0.1s) exist in the layout. For 3+ staggered items use inline `style="animation-delay: 0.15s"` (0.05s increments) — do not use `delay-3`…`delay-6`
- **Hover**: `transition-colors`, `transition-all duration-200`, or `hover:scale-[1.01] hover:shadow-md` on cards
- **Easing**: `cubic-bezier(0.4, 0, 0.2, 1)`
- Keep motion subtle — clinical staff prefer stability over animation

### Reduced motion (required)

Elements start at `opacity-0` before `animate-in` plays, so content must never depend on animation:

- Respect `prefers-reduced-motion: reduce` — disable `fadeSlideUp`/scale effects (see `resources/css/app.css` or layout `<style>`)
- Test that all content is visible with animations disabled
- Avoid motion for non-essential decoration

---

## Accessibility (required)

Health workers use varied lighting, gloves, and rushed input. Meet WCAG AA where practical.

- **Contrast**: minimum 4.5:1 for body text on backgrounds; `text-ink-muted` for secondary copy; `text-ink-subtle` only for decorative/disabled (see Colors)
- **Touch targets**: minimum 44×44px for primary actions on touch devices; desktop buttons may use the layout's autoscaled sizing (~40px)
- **Icons**: `aria-hidden="true"` on decorative icons; meaningful labels on icon-only buttons (`title` or visible text)
- **Forms**: associate `<label for="...">` with every input; use `aria-expanded`, `aria-pressed`, `aria-modal` on interactive widgets
- **Color**: never use color as the only indicator — add icon or text (critical vitals, overdue immunizations, status badges)
- **Focus**: visible focus rings via `focus:ring-primary`
- **Keyboard**: dropdowns and modals must be dismissible and operable without a mouse
- **Reduced motion**: see Motion section

---

## Components (app shell patterns)

### Primary button

```html
<button
    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary text-white font-semibold text-sm px-4 py-2 transition cursor-pointer hover:opacity-95 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
>
    Save
</button>
```

### Outlined / secondary link-button

```html
<a
    class="inline-flex items-center gap-2 text-xs font-bold px-2 py-1 rounded-lg border border-primary text-primary transition hover:bg-primary/10"
>
    View chart
</a>
```

### KPI / stat card

Use the layout's `.kpi-card` classes (see `dashboard.blade.php` for the reference markup):

```html
<div
    class="kpi-card animate-in opacity-0 delay-1 flex items-center gap-2.5 rounded-xl border border-border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
    style="background: var(--bg-surface);"
>
    <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
        <i class="fa-solid fa-users" aria-hidden="true"></i>
    </span>
    <div class="min-w-0">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-muted">Label</p>
        <p class="kpi-card__value truncate">123</p>
    </div>
</div>
```

### Filter panel

```html
<form class="rounded-xl border border-border bg-surface p-4">
    <!-- labeled selects/inputs + Apply button -->
</form>
```

### Status badge (registry rows)

```html
<span
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold"
    style="background: var(--accent-soft);"
>
    <i class="fa-solid fa-calendar-day" aria-hidden="true"></i>
    Due Mar 12
</span>

<span
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold"
    style="background: #fef2f2; color: #b91c1c;"
>
    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
    Overdue 6 days
</span>
```

---

## Standalone pages (login, forgot password)

Mirror app-shell tokens in the page's own `:root` block:

- Same DOH green `--primary` / `--accent` family
- Poppins fonts, grain overlay (`.grain`), rounded card layout
- Reference: `resources/views/auth/login.blade.php`
- Facility subtitle: "Sta. Ana Health Center"

---

## Source of truth

| Concern                                | File                                                                   |
| -------------------------------------- | ---------------------------------------------------------------------- |
| CSS variables, sidebar, header, motion | `resources/views/layouts/app.blade.php`                                |
| DOH form header (iClinicSys)           | `resources/views/consultations/handout/partials/_doh-header.blade.php` |
| Dashboard KPI / empty-state patterns   | `resources/views/dashboard.blade.php`                                  |
| Immunization registry & patient record | `resources/views/immunizations/`, `resources/views/patients/show.blade.php` |
| FHSIS report UI                        | `resources/views/reports/index.blade.php`                              |
| Login / auth standalone styling        | `resources/views/auth/login.blade.php`                                 |

---

## Checklist for new or edited views

### App shell

- [ ] Extends `layouts.app` unless standalone auth page
- [ ] Page title uses `font-display` + `text-ink`; subtitle uses `text-ink-muted`
- [ ] Surfaces use CSS-variable utilities (`text-ink`, `bg-surface-elevated`, `border-border`) — no raw Tailwind color classes for brand (`bg-emerald-*`, `text-sky-*`, etc.) unless clinical red/amber for alerts; no `style="color: var(--…)"` where a utility exists
- [ ] Nested cards have `border border-border` (never white-on-white with shadow alone)
- [ ] `--ink-subtle` used for decorative/disabled only, never body/footer copy
- [ ] `@error` messages are red (`text-red-700`), never the green `--accent`
- [ ] Buttons follow primary / secondary (outlined) / destructive hierarchy
- [ ] Permission-gated actions use `.disabled` + SweetAlert, not broken links
- [ ] Empty states include icon, message, and next-step CTA
- [ ] Registries (patients, pregnant women, immunization, follow-up lists) have a next-action date column with a status badge — due = accent soft + icon, overdue = red + icon + label
- [ ] Stagger uses `delay-1`/`delay-2` or inline `animation-delay` only — never `delay-3`–`delay-6`
- [ ] Content visible with animations disabled (`prefers-reduced-motion`)
- [ ] Form labels, focus rings (`focus:ring-primary`), and touch targets meet accessibility rules
- [ ] Icons have `aria-hidden="true"` when decorative

### DOH forms & print

- [ ] Uses `_doh-header` partial or equivalent FHSIS header block only on itr and patient-enrollment forms.
- [ ] Black borders, fixed grid — not app-shell rounded cards
- [ ] Facility code and DOH logo present
- [ ] Bilingual instructions where template requires EN / Bisaya
- [ ] Maternal records print as Pregnancy Tracking Form / maternal care record; immunization prints as the record (baby) card
- [ ] Print stylesheet tested (`@media print` if applicable)
