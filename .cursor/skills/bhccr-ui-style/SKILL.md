---
name: bhccr-ui-style
description: DOH-aligned BHCCR System Sta. Ana UI/UX style guide. Use when adding or changing UI (Blade views, forms, buttons, tables, login, dashboard, reports, print handouts). Ensures typography, colors, clinical workflows, accessibility, and DOH compliance stay consistent.
---

# DOH EMR System — UI/UX Style Guide

Apply this guide whenever creating or editing frontend views for **BHCCR System Sta. Ana** (Barangay Health Center Consultation and Referral System). The app serves barangay health workers as a college capstone project, we do not have any affiliation with DOH, and must feel trustworthy, efficient, and aligned with public-health reporting standards.

## System context

| Item         | Standard                                                                         |
| ------------ | -------------------------------------------------------------------------------- |
| Product name | **BHCR** (sidebar); **BHCCR** in page titles                                     |
| Facility     | Barangay Sta. Ana Health Center, Tagoloan                                        |
| DOH programs | FHSIS (Field Health Service Information System), iClinicSys forms                |
| Users        | BHW, admin, clinical staff (nurse, doctor) — often non-technical, time-pressured |
| Primary goal | Fast, accurate patient care and RHU-compliant record-keeping                     |

## Two UI modes

Use the correct mode for the surface being built.

### 1. App shell (interactive screens)

Dashboard, patient lists, forms, settings, reports index — anything inside `@extends('layouts.app')`.

- Modern, calm, clinical — white surfaces with DOH green chrome
- Tailwind utility classes + CSS variables from `resources/views/layouts/app.blade.php`
- Rounded cards, soft shadows, subtle motion

### 2. DOH official forms & print/PDF

Patient handouts, enrollment forms, lab slips, morbidity PDFs — anything that leaves the facility or follows iClinicSys layout.

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
- **Avoid**: Generic AI look (Inter, Roboto, Arial as primary fonts; purple/sky gradients; decorative UI that slows workflows)

---

## Typography

- **Display & body**: Poppins — `font-display`, `font-sans`, or `font-family: var(--font-display)`
- **Page titles**: `class="font-display font-semibold text-2xl lg:text-3xl"` + `style="color: var(--ink);"`
- **Subtitles / descriptions**: `class="text-sm mt-1"` + `style="color: var(--ink-muted);"`
- **Section labels / KPI captions**: `class="text-[11px] font-semibold uppercase tracking-wider"` + `style="color: var(--ink-muted);"`
- **DOH print forms**: 8–12px fixed sizes; do not use Tailwind display scale

Do not introduce Inter, Roboto, or Arial as primary fonts in the app shell.

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
| `--ink-subtle`                              | Hints, placeholders, footer                          |
| `--border`                                  | Dividers, input borders                              |
| `--primary`                                 | Links, secondary actions, DOH green accent           |
| `--primary-hover`                           | Hover state for primary elements                     |
| `--accent`                                  | Primary CTAs (Register, Sign in) — same green family |
| `--teal-soft`                               | Soft green fill (badges, table headers, KPI icons)   |
| `--accent-soft`                             | Soft warm fill (referred / warning badges)           |
| `--shadow-sm`, `--shadow-md`, `--shadow-lg` | Elevation                                            |

**Button hierarchy**

| Action type                           | Style                                                                                                        |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| Primary CTA (Register, Sign in, Save) | `style="background: var(--primary); color: #fff;"`                                                           |
| Secondary (Search, Apply, View)       | `style="background: var(--primary);"` or outlined with `border-color: var(--border); color: var(--primary);` |
| Destructive (Delete)                  | Red text/button; require SweetAlert confirmation                                                             |
| Disabled / no permission              | `.disabled` class (grayscale, `cursor: not-allowed`)                                                         |

**Clinical status colors** (use only for health-critical meaning)

| State              | Treatment                                                                                         |
| ------------------ | ------------------------------------------------------------------------------------------------- |
| Normal / on track  | `var(--primary)`, `var(--teal-soft)`                                                              |
| Warning / referred | `var(--accent-soft)`, warm accent text                                                            |
| Critical / overdue | Red palette (`#fef2f2` bg, `#b91c1c` / `#991b1b` text) — always pair color with icon + label text |

---

## Layout & navigation

- All authenticated pages: `@extends('layouts.app')` → `@section('content')`
- Content sits in the centered main card (`max-w-5xl`, `rounded-2xl`, `bg-surface-elevated`)
- Page structure: title block → optional filters → sections with `space-y-4 lg:space-y-6` or `space-y-5 lg:space-y-6`
- **Sidebar**: collapsible; grouped nav — Dashboard, Services (Household, Patients, Check-ups, Immunizations, Labs), Management, Administration, Settings
- **Header**: DOH green bar with notifications, profile dropdown, logout
- **Breadcrumbs**: auto-generated via `BreadcrumbHelper`; link segments use `text-primary`
- **Footer**: `© {year} Barangay Sta. Ana Health Center. All rights reserved.`

### Permission-aware UI

- Hide or disable nav items the user cannot access (`hasPermission()`)
- Disabled links: `.disabled` + SweetAlert `Unauthorized` on click — do not silently fail
- Role-sensitive controls (e.g. user permissions) must stay disabled when self-editing

---

## EMR UX patterns

Follow these for clinical and administrative screens.

### Workflow efficiency

- Minimize clicks for high-frequency tasks (register patient, open queue, record vitals)
- Keep related fields on one scrollable page; avoid unnecessary tab fragmentation
- Filters that affect lists should auto-submit on change where appropriate (see Reports period form)
- Show contextual next actions on dashboard KPI cards (e.g. "Register first patient", "Manage appointments")

### Data entry & forms

- Labels above inputs: `class="text-xs font-medium mb-1"` + `style="color: var(--ink-muted);"`
- Inputs: `rounded-lg border focus:outline-none focus:ring-2` + `style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"`
- Required fields: mark visually; server-side validation with `@error` display
- Checkboxes: 16×16px, `accent-color: var(--primary)` (layout normalizes these)
- Long forms (patient create): logical sections with clear headings; disable dependent fields when not applicable (e.g. PhilHealth fields)

### Lists & tables

- Table header row: `style="background: var(--teal-soft);"`; header cells `style="color: var(--ink-muted);"`
- Row hover: subtle background transition
- Empty states: centered icon + short title + helper text + primary CTA button — never a blank table
- Status badges: Completed → teal soft; Referred → accent soft; Neutral → `rgba(0,0,0,0.06)`

### Alerts & feedback

- **SweetAlert2** (`Swal.fire`) for confirmations, errors, and unauthorized access
- **Inline validation**: field-level errors below inputs
- **Clinical/critical alerts**: icon + text label; do not rely on color alone
- **Live consultation toast**: persistent until user acts (Accept / Cancel) — do not auto-dismiss clinical notifications
- **Dashboard KPIs**: left border accent (`border-left: 4px solid var(--primary)`) to distinguish metric cards

### Reports (DOH / FHSIS)

- Page subtitle must reference DOH FHSIS where applicable
- Report outputs follow official formats (morbidity by ICD, program summary)
- PDF/print headers include: facility name, "Department of Health • FHSIS", generation date
- See `resources/views/reports/`, `resources/views/pdfs/`

---

## Motion

- **Page / section entrance**: `class="animate-in opacity-0"`; stagger with `delay-1` … `delay-6`
- **Hover**: `transition-colors`, `transition-all duration-200`, or `hover:scale-[1.01] hover:shadow-md` on cards
- **Easing**: `cubic-bezier(0.4, 0, 0.2, 1)`
- Keep motion subtle — clinical staff prefer stability over animation

---

## Accessibility (required)

Health workers use varied lighting, gloves, and rushed input. Meet WCAG AA where practical.

- **Contrast**: minimum 4.5:1 for body text on backgrounds
- **Touch targets**: minimum 44×44px for primary actions on touch devices
- **Icons**: `aria-hidden="true"` on decorative icons; meaningful labels on icon-only buttons (`title` or visible text)
- **Forms**: associate `<label for="...">` with every input; use `aria-expanded`, `aria-pressed`, `aria-modal` on interactive widgets
- **Color**: never use color as the only indicator — add icon or text (critical vitals, overdue immunizations, status badges)
- **Focus**: visible focus rings via `--tw-ring-color: var(--primary)`
- **Keyboard**: dropdowns and modals must be dismissible and operable without a mouse

---

## Components (app shell patterns)

### Primary button

```html
<button
    class="rounded-xl text-white font-semibold text-sm px-4 py-2 transition"
    style="background: var(--primary); box-shadow: var(--shadow-sm);"
>
    Save
</button>
```

### Outlined / secondary link-button

```html
<a
    class="text-xs font-bold px-2 py-1 rounded-lg border transition"
    style="border-color: var(--border); color: var(--primary);"
>
    View chart
</a>
```

### KPI / stat card

```html
<div
    class="p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
    style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);"
>
    <p
        class="text-[11px] font-semibold uppercase tracking-wider mb-2"
        style="color: var(--ink-muted);"
    >
        Label
    </p>
    <p
        class="font-display font-semibold text-2xl lg:text-3xl"
        style="color: var(--ink);"
    >
        123
    </p>
</div>
```

### Filter panel

```html
<form
    class="rounded-xl border p-4"
    style="background: var(--bg-surface); border-color: var(--border);"
>
    <!-- labeled selects/inputs + Apply button -->
</form>
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
| FHSIS report UI                        | `resources/views/reports/index.blade.php`                              |
| Login / auth standalone styling        | `resources/views/auth/login.blade.php`                                 |

---

## Checklist for new or edited views

### App shell

- [ ] Extends `layouts.app` unless standalone auth page
- [ ] Page title uses `font-display` + `var(--ink)`; subtitle uses `var(--ink-muted)`
- [ ] Surfaces use CSS variables — no raw Tailwind color classes for brand (`bg-emerald-*`, `text-sky-*`, etc.) unless clinical red/amber for alerts
- [ ] Buttons follow primary / secondary / destructive hierarchy
- [ ] Permission-gated actions use `.disabled` + SweetAlert, not broken links
- [ ] Empty states include icon, message, and next-step CTA
- [ ] Form labels, focus rings, and touch targets meet accessibility rules
- [ ] Icons have `aria-hidden="true"` when decorative

### DOH forms & print

- [ ] Uses `_doh-header` partial or equivalent FHSIS header block
- [ ] Black borders, fixed grid — not app-shell rounded cards
- [ ] Facility code and DOH logo present
- [ ] Bilingual instructions where template requires EN / Bisaya
- [ ] Print stylesheet tested (`@media print` if applicable)
