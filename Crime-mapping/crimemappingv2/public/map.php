<?php
    require_once __DIR__ . '/conn.php';
    require_once __DIR__ . '/oop.php';

    $oop = new Crime_Mapping(new Connection());
 
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
 
        $barangay = $_GET['barangay'] ?? null;
        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
 
        if ($_GET['action'] === 'incidents') {
            echo json_encode($oop->getIncidents($barangay, $type, $status, $from, $to));
            exit;
        }
 
        if ($_GET['action'] === 'stats') {
            echo json_encode($oop->getDashboardStats($barangay, $type, $status, $from, $to));
            exit;
        }
 
        if ($_GET['action'] === 'barangays') {
            echo json_encode($oop->getBarangays());
            exit;
        }
 
        if ($_GET['action'] === 'crime-types') {
            echo json_encode($oop->getCrimeTypes());
            exit;
        }
 
        if ($_GET['action'] === 'statuses') {
            echo json_encode($oop->getStatuses());
            exit;
        }

        // if ($_GET['action'] === 'logout') {
        //     $oop->logout();
        // }
    }
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City Crime Map</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        :root {
            color-scheme: light;
            font-family: 'Inter', system-ui, sans-serif;
            background: #f4f6f8;
            color: #1f2937;
            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --border: #e5e7eb;
            --shadow: 0 18px 50px rgba(15, 23, 42, 0.06);
            --accent: #111827;
            --blue: #2563eb;
            --green: #16a34a;
            --yellow: #f59e0b;
            --red: #dc2626;
        }
 
        * { box-sizing: border-box; }
 
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        }
 
        /* NAVBAR */
        .navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
 
        .navbar-container {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 64px;
        }
 
        .navbar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--accent);
            text-decoration: none;
            margin: 0;
        }
 
        .navbar-nav {
            display: flex;
            gap: 24px;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
        }
 
        .navbar-login {
            background: black;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background 0.2s ease;
        }
 
        .navbar-login:hover { background: #374151; }
 
        /* PAGE */
        .page-shell {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px;
        }
 
        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
 
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 16px;
        }
 
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
 
        .stat-primary {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }
 
        .stat-label {
            margin: 4px 0 0;
            color: #4b5563;
            font-size: 0.95rem;
        }
 
        .stat-icon.incident  { background: #2563eb; }
        .stat-icon.today     { background: #10b981; }
        .stat-icon.crime     { background: #f59e0b; }
        .stat-icon.barangay  { background: #8b5cf6; }
 
        /* CONTENT GRID */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            align-items: start;
        }
 
        /* MAP */
        .map-panel {
            min-height: 520px;
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            border: 1px solid var(--border);
            background: linear-gradient(180deg, #f9fafb 0%, #eef2ff 100%);
            box-shadow: var(--shadow);
            padding: 0;
        }
 
        #crime-map {
            width: 100%;
            height: 100%;
            min-height: 520px;
        }
 
        /* ── MAP OVERLAY TABS (top-right) ── */
        .map-overlay-tabs {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 10000;
            display: flex;
            gap: 8px;
            pointer-events: auto;
        }
 
        .map-tab-wrapper {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }
 
        .map-tab-btn {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 12px;
            padding: 8px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #111827;
            cursor: pointer;
            backdrop-filter: blur(8px);
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
            transition: background 0.15s, box-shadow 0.15s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }
 
        .map-tab-btn:hover {
            background: #ffffff;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.12);
        }
 
        .map-tab-btn.active {
            background: #111827;
            color: #ffffff;
            border-color: #111827;
        }
 
        .map-tab-btn svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }
 
        /* Panel that drops below the tab button */
        .map-tab-panel {
            background: rgba(255, 255, 255, 0.97);
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 14px;
            width: 240px;
            backdrop-filter: blur(12px);
            box-shadow: 0 20px 48px rgba(15, 23, 42, 0.14);
            display: none;
            animation: panelFadeIn 0.18s ease;
            overflow: hidden;
        }
 
        .map-tab-panel.open { display: flex; flex-direction: column; }
 
        .filter-panel-inner {
            overflow-y: auto;
            max-height: calc(520px - 80px);
            padding: 16px 16px 18px;
            scrollbar-width: thin;
            scrollbar-color: #d1d5db transparent;
        }
 
        .filter-panel-inner::-webkit-scrollbar { width: 4px; }
        .filter-panel-inner::-webkit-scrollbar-track { background: transparent; }
        .filter-panel-inner::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
 
        @keyframes panelFadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
 
        /* LEGEND - permanent bottom-right */
        .map-legend {
            position: absolute;
            z-index: 10000;
            bottom: 22px;
            left: 22px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 20px;
            padding: 16px 18px;
            width: 220px;
            backdrop-filter: blur(8px);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            pointer-events: auto;
        }
 
        .legend-title {
            margin: 0 0 12px;
            font-size: 0.95rem;
            font-weight: 700;
            color: #111827;
        }
 
        .legend-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            color: #475569;
            font-size: 0.95rem;
        }
 
        .legend-badge {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            flex-shrink: 0;
        }
 
        /* FILTER panel */
        .filter-panel-title {
            margin: 0 0 12px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }
 
        .filter-group {
            margin-bottom: 10px;
        }
 
        .filter-group > span {
            display: block;
            font-size: 0.68rem;
            font-weight: 600;
            color: #9ca3af;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
 
        .filter-select,
        .filter-date {
            width: 100%;
            border-radius: 7px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            padding: 6px 8px;
            font-size: 0.8rem;
            color: #111827;
            appearance: none;
            font-family: inherit;
            transition: border-color 0.15s, background 0.15s;
        }
 
        .filter-select:focus,
        .filter-date:focus {
            outline: none;
            border-color: #9ca3af;
            background: #ffffff;
        }
 
        .filter-date-row {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
 
        .filter-date-separator {
            font-size: 0.68rem;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: center;
        }
 
        .filter-divider {
            border: none;
            border-top: 1px solid #f3f4f6;
            margin: 10px 0;
        }
 
        /* Crime type checkboxes */
        .crime-type-list {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
 
        .crime-type-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.79rem;
            color: #374151;
            cursor: pointer;
            padding: 4px 6px;
            border-radius: 6px;
            transition: background 0.12s;
            line-height: 1.3;
        }
 
        .crime-type-item:hover {
            background: #f3f4f6;
        }
 
        .crime-type-item input[type="checkbox"] {
            accent-color: #111827;
            width: 13px;
            height: 13px;
            cursor: pointer;
            flex-shrink: 0;
        }
 
        /* RECENT PANEL */
        .recent-panel {
            display: flex;
            flex-direction: column;
            gap: 18px;
            max-width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 22px;
        }
 
        .recent-panel h2 {
            margin: 0 0 4px;
            font-size: 1.15rem;
            font-weight: 700;
        }
 
        .recent-list {
            display: grid;
            gap: 14px;
            overflow-y: auto;
            padding-right: 4px;
            padding-bottom: 4px;
        }
 
        .incident-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 16px;
            box-shadow: var(--shadow);
        }
 
        .incident-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
 
        .incident-type {
            font-weight: 700;
            color: #111827;
            margin: 0;
        }
 
        .incident-meta {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 6px 0 0;
        }
 
        .incident-tag {
            border-radius: 999px;
            padding: 5px 11px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }
 
        .tag-pending    { background: #dc2626; }
        .tag-responding { background: #2563eb; }
        .tag-resolved   { background: #16a34a; }
 
        /* RESPONSIVE */
        @media (max-width: 980px) {
            .content-grid  { grid-template-columns: 1fr; }
            .stats-grid    { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
 
        @media (max-width: 640px) {
            .page-shell  { padding: 16px; }
            .stats-grid  { grid-template-columns: 1fr; }
            .map-panel   { min-height: 420px; }
            .map-overlay-tabs { top: 10px; right: 10px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <h1 class="navbar-brand">Crime Map</h1>
            <ul class="navbar-nav">
                <li><a href="login.php" class="navbar-login">Login</a></li>
            </ul>
        </div>
    </nav>
 
    <div class="page-shell">
 
        <!-- STATS -->
        <section class="stats-grid">
            <article class="stat-card">
                <div class="stat-icon incident">I</div>
                <div>
                    <p class="stat-primary" id="total-incidents">—</p>
                    <p class="stat-label">Total Incidents</p>
                </div>
            </article>
            <article class="stat-card">
                <div class="stat-icon today">T</div>
                <div>
                    <p class="stat-primary" id="incidents-today">—</p>
                    <p class="stat-label">Incidents Today</p>
                </div>
            </article>
            <article class="stat-card">
                <div class="stat-icon crime">C</div>
                <div>
                    <p class="stat-primary" id="most-common-crime">—</p>
                    <p class="stat-label">Most Common Crime</p>
                </div>
            </article>
            <article class="stat-card">
                <div class="stat-icon barangay">B</div>
                <div>
                    <p class="stat-primary" id="most-affected-barangay">—</p>
                    <p class="stat-label">Most Affected Barangay</p>
                </div>
            </article>
        </section>
 
        <!-- CONTENT -->
        <section class="content-grid">
 
            <!-- MAP -->
            <div class="map-panel">
                <div id="crime-map"></div>
 
                <!-- TOP-RIGHT OVERLAY TABS -->
                <div class="map-overlay-tabs">
 
                    <!-- FILTERS TAB -->
                    <div class="map-tab-wrapper">
                        <button class="map-tab-btn" id="filters-tab-btn" onclick="toggleTab('filters')">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M1 3h14M4 8h8M7 13h2"/>
                            </svg>
                            Filters
                        </button>
                        <div class="map-tab-panel" id="filters-panel">
                            <div class="filter-panel-inner">
                            <p class="filter-panel-title">Filters</p>
 
                            <div class="filter-group">
                                <span>Barangay</span>
                                <select class="filter-select" aria-label="Barangay">
                                    <option>All Barangays</option>
                                </select>
                            </div>
 
                            <div class="filter-group">
                                <span>Crime Type</span>
                                <div class="crime-type-list" id="crime-type-list">
                                    <?php
                                        $crimeTypes = [
                                            'Violent crimes',
                                            'Property crimes',
                                            'White-collar crimes',
                                            'Drug crimes',
                                            'Cybercrime',
                                            'Public order / nuisance offenses',
                                            'Traffic offenses',
                                            'Status offenses',
                                        ];
                                        foreach ($crimeTypes as $ct): ?>
                                        <label class="crime-type-item">
                                            <input type="checkbox" name="crime_type" value="<?= htmlspecialchars($ct) ?>">
                                            <?= htmlspecialchars($ct) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
 
                            <hr class="filter-divider">
 
                            <div class="filter-group">
                                <span>Status</span>
                                <select class="filter-select" aria-label="Status">
                                    <option>All Status</option>
                                </select>
                            </div>
 
                            <div class="filter-group">
                                <span>Date Range</span>
                                <div class="filter-date-row">
                                    <input type="date" class="filter-date" aria-label="From date">
                                    <span class="filter-date-separator">to</span>
                                    <input type="date" class="filter-date" aria-label="To date">
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
 
                </div><!-- end .map-overlay-tabs -->
 
                <!-- ORIGINAL LEGEND - bottom right -->
                <div class="map-legend">
                    <h3 class="legend-title">Legend</h3>
                    <div class="legend-item">
                        <span class="legend-badge" style="background: #FFFF00; border: 1px solid #d1d5db;"></span>
                        Yellow = Low
                    </div>
                    <div class="legend-item">
                        <span class="legend-badge" style="background: #FFA500;"></span>
                        Orange = Medium
                    </div>
                    <div class="legend-item">
                        <span class="legend-badge" style="background: #FF0000;"></span>
                        Red = High
                    </div>
                </div>
            </div>
 
            <!-- RECENT INCIDENTS -->
            <aside class="recent-panel">
                <h2>Recent Incidents</h2>
                <div class="recent-list"></div>
            </aside>
 
        </section>
    </div>
 
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // ── MAP INIT ──────────────────────────────────────────────
        const map = L.map('crime-map', {
            center: [16.46, 120.59],
            zoom: 13,
            scrollWheelZoom: false,
        });
 
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);
 
        const markers = [];
 
        // ── ELEMENT REFS ──────────────────────────────────────────
        const incidentList    = document.querySelector('.recent-list');
        const barangaySelect  = document.querySelector('[aria-label="Barangay"]');
        const statusSelect    = document.querySelector('[aria-label="Status"]');
        const fromDateInput   = document.querySelector('[aria-label="From date"]');
        const toDateInput     = document.querySelector('[aria-label="To date"]');
 
        // ── TAB TOGGLE ────────────────────────────────────────────
        function toggleTab(name) {
            const panel = document.getElementById(name + '-panel');
            const btn   = document.getElementById(name + '-tab-btn');
            const isOpen = panel.classList.contains('open');
            panel.classList.toggle('open', !isOpen);
            btn.classList.toggle('active', !isOpen);
        }
 
        // ── HELPERS ───────────────────────────────────────────────
        function clearMarkers() {
            markers.forEach(m => map.removeLayer(m));
            markers.length = 0;
        }
 
        function getCheckedCrimeTypes() {
            return [...document.querySelectorAll('#crime-type-list input[type="checkbox"]:checked')]
                .map(cb => cb.value);
        }
 
        function buildFilterParams() {
            const params  = {};
            const barangay = barangaySelect.value;
            const status   = statusSelect.value;
            const from     = fromDateInput.value;
            const to       = toDateInput.value;
            const types    = getCheckedCrimeTypes();
 
            if (barangay && barangay !== 'All Barangays') params.barangay = barangay;
            if (types.length === 1) params.type = types[0]; // single type filter
            if (status && status !== 'All Status') params.status = status;
            if (from) params.from = from;
            if (to)   params.to   = to;
 
            return params;
        }
 
        async function fetchJson(action, params = {}) {
            params.action = action;
            const query = new URLSearchParams(params);
            const res = await fetch('map.php?' + query.toString());
            return await res.json();
        }
 
        function formatTimeAgo(value) {
            const diff = Math.floor((new Date() - new Date(value)) / 1000);
            if (diff < 60)    return `${diff}s ago`;
            if (diff < 3600)  return `${Math.floor(diff / 60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
            return `${Math.floor(diff / 86400)}d ago`;
        }
 
        function updateStats(stats) {
            document.getElementById('total-incidents').textContent        = stats.total_incidents;
            document.getElementById('incidents-today').textContent        = stats.incidents_today;
            document.getElementById('most-common-crime').textContent      = stats.most_common_crime;
            document.getElementById('most-affected-barangay').textContent = stats.most_affected_barangay;
        }
 
        function syncMapHeight() {
            const mapPanel    = document.querySelector('.map-panel');
            const recentPanel = document.querySelector('.recent-panel');
            if (!mapPanel || !recentPanel) return;
            mapPanel.style.height = 'auto';
            const rh = recentPanel.offsetHeight;
            if (rh > 520) mapPanel.style.height = `${rh}px`;
            if (typeof map !== 'undefined' && map.invalidateSize) {
                setTimeout(() => map.invalidateSize(), 100);
            }
        }
 
        function renderRecentList(items) {
            if (!items.length) {
                incidentList.innerHTML = '<div class="incident-item"><p class="incident-type">No incidents found</p></div>';
                syncMapHeight();
                return;
            }
 
            // Escape to prevent XSS when inserting server-provided strings
            function escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            incidentList.innerHTML = items.slice(0, 5).map(item => {
                const status      = item.status === 'responding' ? 'Responding' : item.status.charAt(0).toUpperCase() + item.status.slice(1);
                const statusClass = item.status === 'resolved' ? 'tag-resolved' : item.status === 'responding' ? 'tag-responding' : 'tag-pending';
                return `
                    <div class="incident-item">
                        <div class="incident-top">
                            <p class="incident-type">${escapeHtml(item.title)}</p>
                            <span class="incident-tag ${statusClass}">${escapeHtml(status)}</span>
                        </div>
                        <p class="incident-meta">${escapeHtml(item.barangay_name)} · ${escapeHtml(formatTimeAgo(item.created_at))}</p>
                    </div>
                `;
            }).join('');
 
            syncMapHeight();
        }
 
        function renderMarkers(items) {
            clearMarkers();
            const bounds = [];
 
            items.forEach(item => {
                if (!item.latitude || !item.longitude) return;
                const marker = L.circleMarker([parseFloat(item.latitude), parseFloat(item.longitude)], {
                    radius: 10,
                    fillColor: item.status === 'resolved' ? '#16a34a' : item.status === 'responding' ? '#2563eb' : '#dc2626',
                    color: '#ffffff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.9,
                }).addTo(map);
 
                marker.bindPopup(`<strong>${item.title}</strong><br>${item.barangay_name}`);
                markers.push(marker);
                bounds.push(marker.getLatLng());
            });
 
            if (bounds.length) map.fitBounds(bounds, { padding: [80, 80] });
        }
 
        // ── DATA LOADING ──────────────────────────────────────────
        async function loadIncidents() {
            const params    = buildFilterParams();
            const incidents = await fetchJson('incidents', params);
            renderMarkers(incidents);
            renderRecentList(incidents);
        }
 
        async function loadStats() {
            const params = buildFilterParams();
            const stats  = await fetchJson('stats', params);
            updateStats(stats);
        }
 
        function displayStatusLabel(status) {
            if (!status) return status;
            if (status === 'responding') return 'Responding';
            return status.charAt(0).toUpperCase() + status.slice(1);
        }
 
        async function loadBarangays() {
            const names   = await fetchJson('barangays');
            barangaySelect.innerHTML = ['<option>All Barangays</option>', ...names.map(n => `<option value="${n}">${n}</option>`)].join('');
        }
 
        async function loadStatuses() {
            const statuses   = await fetchJson('statuses');
            statusSelect.innerHTML = ['<option>All Status</option>', ...statuses.map(s => `<option value="${s}">${displayStatusLabel(s)}</option>`)].join('');
        }
 
        async function applyFilters() {
            await Promise.all([loadStats(), loadIncidents()]);
        }
 
        barangaySelect.addEventListener('change', applyFilters);
        statusSelect.addEventListener('change', applyFilters);
        fromDateInput.addEventListener('change', applyFilters);
        toDateInput.addEventListener('change', applyFilters);
        document.getElementById('crime-type-list').addEventListener('change', applyFilters);
 
        document.addEventListener('DOMContentLoaded', async () => {
            await Promise.all([loadBarangays(), loadStatuses()]);
            await applyFilters();
        });
    </script>
</body>
</html>