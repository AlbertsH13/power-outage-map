# Power Outage Reporting Platform

A containerized Laravel 13 monolith for community-driven electrical outage reporting and real-time geospatial visualization on an interactive Leaflet map.

---

## Architecture Overview

The system is built as a single Laravel monolith adhering strictly to modern standards:
- **Backend:** Laravel 13 running on PHP 8.3-FPM, MySQL 8.0, and Redis 7.
- **Frontend (Hybrid Architecture):** Reactive Livewire components (`ManageReports`, `CreateReportForm`) handle stateful form submission, real-time validation, and AJAX-driven pagination (`WithPagination`). The interactive Leaflet (OpenStreetMap) canvas is isolated with `wire:ignore` and driven by Alpine.js and modular JavaScript (`resources/js/outage-map.js`) for instant 60fps client-side preview without DOM-morphing conflicts.
- **Styled Error Pages:** Custom technical error templates (`403`, `404`, `419`, `429`, `500`) styled consistently with system design tokens, monospace HTTP status codes, and user recovery navigation.
- **Zero Third-Party Map Fees:** Uses official OpenStreetMap tile servers (`https://tile.openstreetmap.org/{z}/{x}/{y}.png`). Evaluators can run and test the full application without creating API keys or billing accounts.
- **Zero PII Exposure:** Viewport API endpoints expose only anonymous geospatial and status attributes. User identities and credentials are never leaked.
- **Dual-Layer Expiration:** Enforced at query scope (`where('expires_at', '>', now())`) and by an automated background scheduler job (`outages:expire` running every minute).

Detailed system topology, database indexes, and architecture decision records are available in [ARCHITECTURE.md](ARCHITECTURE.md) and [PLAN.md](PLAN.md).

---

## Container Topology

The application runs via Docker Compose with 6 orchestrated services sharing a unified internal network:

| Service | Base Image | Role | Port |
|---|---|---|---|
| `nginx` | `nginx:1.27-alpine` | Reverse proxy, static asset delivery, HTTP termination | `8080` |
| `app` | `outage-map-app:latest` | Monolithic Laravel application (PHP 8.3-FPM, non-root `www-data`) | `9000` (internal) |
| `queue` | `outage-map-app:latest` | Background worker processing asynchronous tasks | Internal |
| `scheduler` | `outage-map-app:latest` | Executes `php artisan schedule:work` every minute | Internal |
| `db` | `mysql:8.0` | MySQL database with precomputed bounding-box decimal indexes | `3306` (internal) |
| `redis` | `redis:7-alpine` | Session storage, cache store, and rate-limiting counters | `6379` (internal) |

The multi-stage `Dockerfile` compiles frontend assets using Node 22 in a build-only stage and copies compiled artifacts into the final production image. Neither Node.js nor `node_modules` exist in the running PHP container.

---

## Quickstart & Installation

### Prerequisites
- Docker Engine 24+ and Docker Compose v2.
- Git.
- *(No local PHP, Composer, or Node.js required on the host).*

### 1. Clone the Repository
```bash
git clone https://github.com/AlbertsH13/power-outage-map.git
cd power-outage-map
```

### 2. Configure Environment
```bash
cp .env.example .env
```

### 3. Build and Start Containers
```bash
docker compose up -d --build
```

### 4. Initialize Database & Demo Seed Data
```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### 5. Access the Application
Open your browser and navigate to:
- **Landing Page & Map:** [http://localhost:8080](http://localhost:8080)
- **Full-Screen Live Map:** [http://localhost:8080/map](http://localhost:8080/map)
- **Submit Report:** [http://localhost:8080/reports/create](http://localhost:8080/reports/create)
- **User Dashboard:** [http://localhost:8080/my-reports](http://localhost:8080/my-reports)

---

## Demo Credentials

The database seeder pre-populates sample outages and two test accounts:

| User | Email | Password |
|---|---|---|
| Primary Demo User | `demo@example.com` | `password` |
| Community Reporter | `jane@example.com` | `password` |

---

## API Specification

The REST API is documented in [openapi.yaml](openapi.yaml).

### Available Endpoints:
- `GET /api/v1/outages`: Returns active outages inside the specified bounding box (`min_lat`, `max_lat`, `min_lng`, `max_lng` or `bbox=min_lat,min_lng,max_lat,max_lng`). Public, throttled to 60 req/min.
- `POST /api/v1/outages`: Submits a new outage report. Requires authentication, validated against a 5 report/hour rate limit and max 3 active reports per user.
- `PUT /api/v1/outages/{id}`: Updates an active outage report's radius, duration, and notes. Requires authentication and ownership verification.
- `POST /api/v1/outages/{id}/close`: Marks an active report as resolved. Requires authentication and strict ownership verification via `OutagePolicy`.

---

## Automated Tests & Code Quality

### Running Tests
Execute the complete PHPUnit test suite inside the application container:
```bash
docker compose exec app php artisan test
```
*Current test suite: **69 tests passing (301 assertions)** covering authentication, geospatial queries, rate limits, quota enforcement, ownership policies, expiration jobs, Livewire components, styled error pages, and UI rendering.*

### Code Style Verification
Run Laravel Pint to verify compliance with PSR-12 and Laravel conventions:
```bash
docker compose exec app ./vendor/bin/pint --test
```

---

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
