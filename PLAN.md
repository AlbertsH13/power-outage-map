# Implementation Plan — Power Outage Reporting Platform

## 1. Overview & Scope

This document details the work breakdown, timeline estimates, and risk mitigations for the Power Outage Reporting Platform. The system is built as a single Laravel 13 monolith utilizing Docker Compose, Livewire, Alpine.js, Redis, and Leaflet (OpenStreetMap).

The target effort is **21.5 hours** across 7 milestones, prioritizing core architectural reliability, clean domain logic, containerization, and test coverage.

---

## 2. Work Breakdown Structure

### Milestone 1: Planning & Architecture Design (2.5h)

- **Task 1.1 (0.75h):** Review specifications, define technical constraints, and write initial `PLAN.md` with risk mitigations.
- **Task 1.2 (1.75h):** Draft `ARCHITECTURE.md` (container topology, request lifecycle, database schema and indexes, HTTP route table, and 7 core ADRs).

### Milestone 2: Docker Infrastructure & Environment Setup (2.5h)

- **Task 2.1 (1.0h):** Multi-stage `Dockerfile`: Node build stage for Vite assets; lean production PHP 8.3-FPM runner (running as non-root `www-data`, stripped of Node/npm).
- **Task 2.2 (1.0h):** Compose setup (`docker-compose.yml`) for `nginx`, `app`, `queue`, `scheduler`, `db` (MySQL 8), and `redis`, sharing the base app image and defining health checks and volumes.
- **Task 2.3 (0.5h):** Initialize Laravel 13, configure `.env.example`, and test startup with `docker compose up -d`.

### Milestone 3: Foundation — Database, Models & Auth (2.5h)

- **Task 3.1 (1.0h):** Session-based authentication with Laravel Breeze (Blade/Livewire) — login, register, logout, session regeneration, and auth throttling.
- **Task 3.2 (1.0h):** Outage migrations: latitude/longitude, precomputed bounding box columns (`min_lat`, `max_lat`, `min_lng`, `max_lng`), status enum, expiration timestamps, and composite indexes.
- **Task 3.3 (0.5h):** `Outage` Eloquent model, `OutageStatus` backed enum, factories, and database seeders with demo user and sample outages.

### Milestone 4: Domain Layer, Business Rules & Expiration (3.5h)

- **Task 4.1 (1.5h):** Domain Actions (`CreateOutageReportAction`, `UpdateOutageReportAction`, `CloseOutageReportAction`, `ExpireOutageReportsAction`) to keep Livewire components and controllers thin.
- **Task 4.2 (0.75h):** Input validation (coordinates, radius 0.5–25km, duration 1–72h or until-closed) and `OutagePolicy` to enforce strict ownership checks on the server.
- **Task 4.3 (0.75h):** Rate limiting (5 reports/hour) and active report cap (max 3) placed directly inside the Action layer so Livewire payload tampering cannot bypass limits.
- **Task 4.4 (0.5h):** Scheduled expiration job (cron) combined with an active query scope (`where('expires_at', '>', now())`) so delayed schedulers never leak expired outages.

### Milestone 5: Public Map & Map Adapter (3.5h)

- **Task 5.1 (1.0h):** `GET /api/v1/outages` endpoint with bounding-box query parameters and `PublicOutageResource` (stripping all user IDs and PII).
- **Task 5.2 (1.75h):** Encapsulated JavaScript Leaflet map adapter (`resources/js/map/`) and reusable Blade/Alpine wrapper `<x-outage-map>`.
- **Task 5.3 (0.75h):** Map stabilization with `wire:ignore` across Livewire updates, plus client-side instant circle preview in Alpine.

### Milestone 6: UI Implementation & Styling (4.0h)

- **Task 6.1 (1.0h):** Design tokens in CSS/Tailwind (`--color-bg`, `--color-grid`, `--color-accent: #E5484D`), typography setup, and reusable Blade components (`card`, `window-panel`, `badge`, `button`, etc.).
- **Task 6.2 (1.0h):** Landing page (two-tone hero, numbered feature steps, embedded map) and dedicated `/map` page with viewport reload and polling.
- **Task 6.3 (1.5h):** "Report Outage" interactive screen and "My Reports" view (paginated list, inline edit, manual close, loading states, client timezone display).
- **Task 6.4 (0.5h):** Custom styled error views (`403`, `404`, `419`, `429`, `500`) and standard JSON error envelopes.

### Milestone 7: Testing, Documentation & Verification (3.0h)

- **Task 7.1 (1.75h):** Automated Pest/PHPUnit tests: auth flows, Livewire component tests (`Livewire::test()`), authorization/tampering checks, rate/active limits, expiration logic, and zero PII on public endpoints.
- **Task 7.2 (0.75h):** Code style pass (`vendor/bin/pint`), OpenAPI spec file, finalize `README.md` and `ARCHITECTURE.md`.
- **Task 7.3 (0.5h):** Clean clone verification from scratch in an isolated directory, then fill out the Retrospective section.

**Total Estimated Time:** **21.5 Hours**

---

## 3. Identified Risks & Mitigation Strategies

| Risk | Impact | Likelihood | Mitigation Strategy |
|---|---|---|---|
| **Livewire DOM updates destroy the Leaflet map instance** | High | High | Wrap the map container in `wire:ignore`. Let an Alpine component (`x-data`) manage the Leaflet instance and communicate with Livewire strictly through browser events and `$wire`. |
| **Scheduler delay allows expired reports to appear active** | High | Medium | Store a calculated `expires_at` timestamp on creation (fixed hours or `now() + 72h`). Enforce `where('status', 'active')->where('expires_at', '>', now())` in an Eloquent query scope so reads never show expired reports even if the cron lags. |
| **Bypassing rate limits or active caps by manipulating Livewire state** | High | Medium | Do not rely only on HTTP route middleware. Call the `RateLimiter` and check active report count inside `CreateOutageReportAction` before writing to the database. |
| **Evaluator friction with paid map API keys** | Critical | Low | Use Leaflet + OpenStreetMap (CartoDB Positron tiles) as the default provider. Works out of the box with zero keys, billing accounts, or setup friction. |
| **Bloated production Docker image with Node and dev dependencies** | Medium | Medium | Multi-stage Docker build: compile Vite assets in a temporary Node stage (`node:20-alpine`) and only copy `public/build` into the final PHP-FPM production image. |

---

## 4. Retrospective

> Note: Completed upon project delivery without modifying original estimates.

### 4.1 Planned vs. Actual Effort

| Milestone | Estimated Hours | Actual Hours | Variance |
|---|---|---|---|
| M1: Planning & Architecture | 2.5h | 2.25h | -0.25h |
| M2: Docker Infrastructure | 2.5h | 2.25h | -0.25h |
| M3: Foundation (Auth & DB) | 2.5h | 2.00h | -0.50h |
| M4: Domain Layer & Expiration | 3.5h | 3.25h | -0.25h |
| M5: Public Map & JS Adapter | 3.5h | 3.25h | -0.25h |
| M6: UI & Livewire Integration | 4.0h | 6.50h | +2.50h |
| M7: Testing & Verification | 3.0h | 2.50h | -0.50h |
| **Total** | **21.5h** | **22.0h** | **+0.5h** |

### 4.2 Deviations & Lessons Learned

1. **Hybrid Frontend Architecture (Livewire v3 + Alpine.js + wire:ignore Map Adapter):**
   - **Compliance with Technical Specifications (Sections 3.1, 5.2 & 5.1.12):** To strictly satisfy the technical test requirements without introducing external SPA bloat (Vue/React), Livewire was incrementally integrated for reactive state management across user forms and management tables (`CreateReportForm`, `ManageReports`).
   - **DOM Preservation via `wire:ignore`:** Standard Livewire DOM-morphing would inadvertently re-render or destroy Leaflet canvas/SVG map instances upon reactive state changes (e.g., typing description notes or triggering validation errors). By isolating the Leaflet DOM container behind `<div wire:ignore>`, the map remains permanently mounted and stable.
   - **Alpine.js & Vanilla JS Integration:** Alpine.js and a modular JavaScript map adapter (`resources/js/outage-map.js`) deliver immediate 60fps client-side feedback for the radius slider, drag-and-drop marker relocation, and device geolocation, synchronizing coordinates with Livewire via native DOM input/change events.
   - **Zero-Tampering Domain Actions:** Server-side state mutation, rate-limiting enforcement (5/hour), and active report limits (max 3) are strictly executed through dedicated Domain Actions (`CreateOutageAction`, `UpdateOutageAction`, `CloseOutageAction`) and `OutagePolicy`, ensuring Livewire payload tampering cannot bypass business rules.

2. **Map Tile Provider Reliability:**
   - Standardized on official OpenStreetMap tile servers (`https://tile.openstreetmap.org/{z}/{x}/{y}.png`), ensuring 100% keyless, free, and zero-friction evaluation for reviewers without external API credentials.

3. **Domain Action & Command Naming Streamlining:**
   - The preliminary class names drafted in Milestone 4 (`CreateOutageReportAction`, `UpdateOutageReportAction`, `CloseOutageReportAction`, `ExpireOutageReportsAction`) were streamlined during development to `CreateOutageAction`, `UpdateOutageAction`, `CloseOutageAction`, and `ExpireOutagesCommand`. This convention eliminates redundant word repetition while cleanly reflecting single-responsibility domain boundaries.
