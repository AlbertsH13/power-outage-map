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

## Frontend Asset Compilation

The application uses Vite with Tailwind CSS and Alpine.js. Assets are compiled in two modes:

### Production (Docker Multi-Stage Build)
In the production Docker image, frontend compilation is automated within the multi-stage `Dockerfile`:
1. The `node:22-alpine` build stage installs dependencies (`npm ci`) and executes `npm run build`.
2. The resulting distribution directory (`public/build`) is copied directly into the final PHP-FPM runtime image (`app`).
3. Neither Node.js nor the `node_modules` directory exist in the running production containers, keeping image size minimal and attack surfaces reduced.

### Local Host Development (Optional)
If developing outside Docker with a local Node environment:
```bash
# Install NPM dependencies
npm install

# Compile production bundles
npm run build

# Or launch the live Vite development server
npm run dev
```

---

## Map Provider Configuration

The interactive map is built on Leaflet.js with zero mandatory third-party API keys or paid external service dependencies.

### Default OpenStreetMap Setup
By default, the platform uses public OpenStreetMap tile servers:
- **Tile URL:** `https://tile.openstreetmap.org/{z}/{x}/{y}.png`
- **Cost:** Free, open-source, zero API keys required.
- Evaluators can clone, run, and test the full geospatial capabilities without creating credit card accounts or external provider tokens.

### Configuration & Extensibility
The map coordinates and defaults can be configured via environment variables in `.env`:
```env
MAP_PROVIDER=osm
MAP_DEFAULT_LAT=18.4655
MAP_DEFAULT_LNG=-66.1057
MAP_DEFAULT_ZOOM=12
```
To switch to custom map tiles (e.g., self-hosted tile server or Mapbox raster tiles), update the Leaflet tile layer URL inside `resources/js/outage-map.js`.

---

## Assumptions

During architectural design and implementation, the following key domain assumptions were established:
- **Timezone & Timestamps:** All database timestamps (`created_at`, `expires_at`, `closed_at`, `updated_at`) are strictly stored in UTC within MySQL. Timestamps are formatted into the client's local operating system timezone on the frontend using standard browser APIs (`Intl.DateTimeFormat` and `Date.toLocaleTimeString`), avoiding the need for server-side user timezone configuration.
- **Overlapping / Duplicate Reports:** Outages reported in overlapping or identical coordinates are not rejected; instead, they are rendered with semi-transparent circle overlays (opacity 0.2, solid borders). Overlapping reports visually layer into darker clusters, representing higher outage concentration in that area. Each circle remains individually clickable to inspect its details and expiration.
- **Indefinite Duration ("Until Resolved") Safety Cap:** Outages submitted with `duration_type = 'until_closed'` have a hard cap set at **72 hours** (`now()->addHours(72)`). This prevents orphaned reports from permanently cluttering the public live map if a user forgets to manually resolve their report.
- **Viewport Bounding Box Calculation:** The bounding box algorithm calculates coordinate ranges using standard spherical approximations (~111.32 km per degree of latitude at the equator). Queries strictly filter by `latitude BETWEEN :min_lat AND :max_lat` and `longitude BETWEEN :min_lng AND :max_lng` using the composite index `idx_outages_viewport`, supporting bounding boxes crossing coordinates up to maximum visual zoom bounds.

---

## Optional Features Implemented

Rather than spreading effort across superficial extras, focus was placed on production hardening the core specification:
- **Hybrid Livewire v3 Architecture:** Integrated Livewire v3 components (`ManageReports`, `CreateReportForm`) with `wire:ignore` isolation for the Leaflet canvas, combining stateful reactive pagination and modals with conflict-free 60fps client-side map rendering.
- **Domain Actions Layer:** Encapsulated business logic into dedicated, reusable Action classes (`CreateOutageAction`, `UpdateOutageAction`, `CloseOutageAction`) shared by both Web Livewire controllers and REST API endpoints.
- **Styled Technical Error Pages:** Customized Blade error views (`403`, `404`, `419`, `429`, `500`) styled to match system design tokens with technical monospace status codes, clear diagnostic descriptions, and recovery buttons.
- **Dedicated Redis Rate Limiting:** Configured custom rate limiters (`throttle:create-outage` allowing 5 reports/hour, `throttle:register` allowing 5 registrations/minute, and `throttle:api` allowing 60 queries/minute) backed by Redis counters.
- **Automated Background Expiration:** Scheduled command `outages:expire` running every minute via the Docker `scheduler` container, paired with query scopes that filter out expired records at query time.
- **Full OpenAPI 3.0.3 Specification:** Exhaustive contract definition in `openapi.yaml` covering all four endpoints, request bodies, schemas, and HTTP error responses (401, 403, 422, 429).

---

## Known Limitations & Future Improvements

- **Real-Time WebSockets:** Viewport updates currently trigger via debounced AJAX fetch requests upon map movement or zoom. A natural production evolution would be integrating **Laravel Reverb** to push new outages to active client maps via WebSockets in real time.
- **Marker Clustering at Scale:** For high-density metropolitan incidents with thousands of simultaneous reports, client-side circle rendering could be complemented with `Leaflet.markercluster` or server-side Mapbox Vector Tiles (MVT) for aggregated regional clustering.
- **Native Spatial Geometry (PostGIS / MySQL Spatial):** The current schema uses indexed decimal coordinates with bounding box filters (`idx_outages_viewport`), which delivers sub-millisecond query execution for rectangular viewports. Upgrading to native MySQL spatial types (`ST_Point`, `ST_Buffer`, `ST_Contains`) or PostgreSQL/PostGIS would enable arbitrary polygon shapes and municipal geofencing.
- **Offline / PWA Support:** Adding a Service Worker with CacheStorage to cache base map tiles would allow citizens in low-connectivity or blackout areas to view previously loaded maps offline.

---

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
