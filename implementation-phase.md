# Implementation Phases - La Trinidad Crime Mapping

## Goals
Build a crime mapping web app for La Trinidad, Benguet with role-based access, a live crime map, and a report workflow. Tech stack: HTML, CSS, JS, PHP (PDO), AJAX, MySQL (phpMyAdmin), Leaflet, XAMPP.

## Scope Summary
- Guest landing dashboard with stats and a mini map
- Full map page with filters, search, and marker details
- Reporting flow for registered users
- Barangay dashboard and report verification
- Admin dashboard for full management
- Community validation (thumbs up/down)
- Notifications and alerts for high severity

## Phase 0 - Project Setup
Deliverables:
- XAMPP project folder and vhost (optional)
- Base folder structure (public, api, assets, uploads)
- Environment config (db settings)

Acceptance:
- Local server runs index page
- Database connection test passes

## Phase 1 - Database and Data Model
Deliverables:
- Database schema (crime-db.sql)
- Barangay seed data for La Trinidad
- Base admin account

Acceptance:
- Schema loads without errors
- Basic seed data is visible in phpMyAdmin

## Phase 2 - Authentication and Roles
Deliverables:
- Register, login, logout (sessions)
- Role guard middleware
- Role-based navigation

Acceptance:
- Guest can browse public pages only
- Registered users can submit reports
- Barangay users can verify reports in their area
- Admin can access all management pages

## Phase 3 - Public Dashboard and Mini Map
Deliverables:
- Landing dashboard with stats
- Recent crime feed
- Mini map scoped to La Trinidad

Acceptance:
- Dashboard loads without login
- Stats update via AJAX
- Mini map shows recent incidents only

## Phase 4 - Full Map and Filtering
Deliverables:
- Full-screen map with filters
- Filter sets: crime type, date range, barangay, status
- Marker styles toggle (colored dots vs icon)
- Search across crime details

Acceptance:
- Default filter shows all data
- Filtered results update without reload
- Marker hover shows preview
- Marker click opens right-side details panel

## Phase 5 - Report Submission
Deliverables:
- Report form (side panel on map)
- Map click to choose location
- Optional image upload
- Report status tracking

Acceptance:
- Report stored as pending
- Confirmation shows instantly (AJAX)

## Phase 6 - Barangay Workflow
Deliverables:
- Barangay dashboard with KPIs
- Report queue (pending, investigating, resolved)
- Ability to add incidents directly
- Export reports (CSV)

Acceptance:
- Barangay views incidents filtered to their area
- Status updates are logged

## Phase 7 - Admin Workflow
Deliverables:
- Admin dashboard (global KPIs)
- Manage users, crime categories, reports
- Remove false reports

Acceptance:
- Admin can review and approve/reject any report
- All admin actions are logged

## Phase 8 - Notifications and Alerts
Deliverables:
- Notifications table + API
- High severity alerts on dashboard
- Optional email/SMS hook (future)

Acceptance:
- New report generates a notification
- High severity triggers alert UI

## Phase 9 - QA and Deployment
Deliverables:
- Test checklist
- Security review (input validation, upload limits)
- Production config guide

Acceptance:
- Major user flows pass
- No SQL injection in API endpoints
- Uploads restricted by MIME and size

## API Endpoint Plan (PHP + AJAX)
- GET /api/incidents?filters
- GET /api/incident?id=123
- POST /api/report
- POST /api/incident/status
- POST /api/validation
- GET /api/stats
- GET /api/notifications
- POST /api/auth/login
- POST /api/auth/register
- POST /api/auth/logout

## Mapping to PDF Features
- Entry point, dashboard, submit report, live feed, filter/search, status tracking
- Real-time alerts, map visualization, community validation
- Admin access, analytics, export, system management

