<?php
    include 'conn.php';
    include 'oop.php';

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

        if ($_GET['action'] === 'crime_types') {
            echo json_encode($oop->getCrimeTypes());
            exit;
        }

        if ($_GET['action'] === 'statuses') {
            echo json_encode($oop->getStatuses());
            exit;
        }
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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        }

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

        .navbar-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }

        .navbar-link:hover {
            color: var(--white);
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

        .navbar-login:hover {
            background: white;
        }

        .page-shell {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px;
        }

        header {
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
        }

        .page-title {
            margin: 0;
            font-size: clamp(1.8rem, 2.2vw, 2.6rem);
            letter-spacing: -0.03em;
        }

        .page-subtitle {
            margin: 6px 0 0;
            color: #4b5563;
            font-size: 1rem;
            max-width: 560px;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding:  22px 22px 0px 22px;
        }

        .filter-bar {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .filter-item,
        .filter-button {
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--surface-soft);
            padding: 14px 16px;
            font-size: 0.95rem;
            color: #111827;
            width: 100%;
        }

        .filter-item {
            appearance: none;
        }

        .filter-button {
            background: var(--blue);
            color: #ffffff;
            border: none;
            font-weight: 600;
            cursor: pointer;
        }

        .filter-button:hover {
            background: #1d4ed8;
        }

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

        .stat-icon.incident { background: #2563eb; }
        .stat-icon.today { background: #10b981; }
        .stat-icon.crime { background: #f59e0b; }
        .stat-icon.barangay { background: #8b5cf6; }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            align-items: start;
        }

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

        .map-placeholder {
            display: none;
        }

        .map-placeholder::before {
            content: 'Map placeholder';
            position: absolute;
            top: 24px;
            left: 24px;
            font-size: 0.95rem;
            color: #334155;
        }

        .marker-dot {
            position: absolute;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.9);
        }

        .marker-high { background: #dc2626; top: 24%; left: 28%; }
        .marker-med { background: #f59e0b; top: 42%; left: 52%; }
        .marker-res { background: #16a34a; top: 65%; left: 34%; }
        .marker-high2 { background: #dc2626; top: 58%; left: 72%; }

        .map-legend {
            position: static;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 20px;
            padding: 16px 18px;
            width: 100%;
            margin-top: 8px;
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

        .legend-item:last-child {
            margin-bottom: 0;
        }

        .legend-badge {
            width: 14px;
            height: 14px;
            border-radius: 4px;
        }

        .recent-panel {
            display: flex;
            flex-direction: column;
            gap: 18px;
            max-width: 100%;
            padding: 22px !important;
        }

        .recent-panel h2 {
            margin: 0 0 16px;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .incident-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 16px;
            box-shadow: var(--shadow);
        }

        .recent-list {
            display: grid;
            gap: 14px;
            overflow-y: auto;
            padding-right: 4px;
            padding-bottom: 16px;
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
            font-size: 0.95rem;
            margin: 6px 0 0;
        }

        .incident-tag {
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .tag-pending { background: #dc2626; }
        .tag-responding { background: #2563eb; }
        .tag-resolved { background: #16a34a; }

        @media (max-width: 980px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .filter-bar {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .page-shell {
                padding: 16px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .map-panel {
                min-height: 420px;
            }

            .map-legend {
                position: static;
                width: auto;
                margin: 16px auto 0;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <h1 class="navbar-brand">Crime Map</h1>
            <ul class="navbar-nav">
                <li><a href="users/login.php" class="navbar-link navbar-login">Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="page-shell">
        <section class="panel">
            <div class="filter-bar">
                <select class="filter-item" aria-label="Barangay">
                    <option>All Barangays</option>
                </select>
                <select class="filter-item" aria-label="Crime type">
                    <option>All Crime Types</option>
                </select>
                <select class="filter-item" aria-label="Status">
                    <option>All Status</option>
                </select>
                <div style="display: grid; gap: 12px;">
                    <input type="date" class="filter-item" aria-label="From date" placeholder="From" />
                    <input type="date" class="filter-item" aria-label="To date" placeholder="To" />
                </div>
                <button type="button" class="filter-button">Apply Filters</button>
            </div>
        </section>
        <br>

        <section class="stats-grid">
            <article class="stat-card">
                <div class="stat-icon incident">I</div>
                <div>
                    <p class="stat-primary" id="total-incidents"></p>
                    <p class="stat-label">Total Incidents</p>
                </div>
            </article>
            <article class="stat-card">
                <div class="stat-icon today">T</div>
                <div>
                    <p class="stat-primary" id="incidents-today"></p>
                    <p class="stat-label">Incidents Today</p>
                </div>
            </article>
            <article class="stat-card">
                <div class="stat-icon crime">C</div>
                <div>
                    <p class="stat-primary" id="most-common-crime"></p>
                    <p class="stat-label">Most Common Crime</p>
                </div>
            </article>
            <article class="stat-card">
                <div class="stat-icon barangay">B</div>
                <div>
                    <p class="stat-primary" id="most-affected-barangay"></p>
                    <p class="stat-label">Most Affected Barangay</p>
                </div>
            </article>
        </section>

        <section class="content-grid">
            <div class="map-panel">
                <div id="crime-map"></div>
            </div>

            <aside class="recent-panel panel">
                <h2>Recent Incidents</h2>
                <div class="recent-list"></div>
                <div class="map-legend">
                    <h3 class="legend-title">Legend</h3>
                    <div class="legend-item">
                        <span class="legend-badge" style="background: #dc2626;"></span>
                        Red = Pending
                    </div>
                    <div class="legend-item">
                        <span class="legend-badge" style="background: #2563eb;"></span>
                        Blue = Responding
                    </div>
                    <div class="legend-item">
                        <span class="legend-badge" style="background: #16a34a;"></span>
                        Green = Resolved
                    </div>
                </div>
            </aside>
        </section>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const map = L.map('crime-map', {
            center: [16.46, 120.59],
            zoom: 13,
            scrollWheelZoom: false,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        const markers = [];

        const filterButton = document.querySelector('.filter-button');
        const incidentList = document.querySelector('.recent-list');
        const barangaySelect = document.querySelector('[aria-label="Barangay"]');
        const crimeTypeSelect = document.querySelector('[aria-label="Crime type"]');
        const statusSelect = document.querySelector('[aria-label="Status"]');
        const fromDateInput = document.querySelector('[aria-label="From date"]');
        const toDateInput = document.querySelector('[aria-label="To date"]');
        const fixedBarangays = [
            'Alapang',
            'Alno',
            'Ambiong',
            'Bahong',
            'Balili',
            'Beckel',
            'Betag',
            'Bineng',
            'Cruz',
            'Lubas',
            'Pico',
            'Poblacion',
            'Puguis',
            'Shilan',
            'Tawang',
            'Wangal'
        ];

        function clearMarkers() {
            markers.forEach(marker => {
                map.removeLayer(marker);
            });
            markers.length = 0;
        }

        function buildFilterParams() {
            const params = {};
            const barangay = barangaySelect.value;
            const type = crimeTypeSelect.value;
            const status = statusSelect.value;
            const from = fromDateInput.value;
            const to = toDateInput.value;

            if (barangay && barangay !== 'All Barangays') params.barangay = barangay;
            if (type && type !== 'All Crime Types') params.type = type;
            if (status && status !== 'All Status') params.status = status;
            if (from) params.from = from;
            if (to) params.to = to;

            return params;
        }

        async function fetchJson(action, params = {}) {
            params.action = action;
            const query = new URLSearchParams(params);
            const response = await fetch('map.php?' + query.toString());
            return await response.json();
        }

        function formatTimeAgo(value) {
            const date = new Date(value);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            if (diff < 60) return `${diff}s ago`;
            if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
            return `${Math.floor(diff / 86400)}d ago`;
        }

        function updateStats(stats) {
            document.getElementById('total-incidents').textContent = stats.total_incidents;
            document.getElementById('incidents-today').textContent = stats.incidents_today;
            document.getElementById('most-common-crime').textContent = stats.most_common_crime;
            document.getElementById('most-affected-barangay').textContent = stats.most_affected_barangay;
        }

        function syncMapHeight() {
            const mapPanel = document.querySelector('.map-panel');
            const recentPanel = document.querySelector('.recent-panel');
            const defaultMapHeight = 520;

            if (!mapPanel || !recentPanel) return;

            mapPanel.style.height = 'auto';
            const recentHeight = recentPanel.offsetHeight;

            if (recentHeight > defaultMapHeight) {
                mapPanel.style.height = `${recentHeight}px`;
            }

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

            incidentList.innerHTML = items.slice(0, 5).map(item => {
                const status = item.status === 'responding' ? 'Responding' : item.status.charAt(0).toUpperCase() + item.status.slice(1);
                const statusClass = item.status === 'resolved' ? 'tag-resolved' : item.status === 'responding' ? 'tag-responding' : 'tag-pending';

                return `
                    <div class="incident-item">
                        <div class="incident-top">
                            <p class="incident-type">${item.title}</p>
                            <span class="incident-tag ${statusClass}">${status}</span>
                        </div>
                        <p class="incident-meta">${item.barangay_name} · ${formatTimeAgo(item.created_at)}</p>
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

            if (bounds.length) {
                map.fitBounds(bounds, {padding: [80, 80]});
            }
        }

        async function loadIncidents() {
            const params = buildFilterParams();
            const incidents = await fetchJson('incidents', params);
            renderMarkers(incidents);
            renderRecentList(incidents);
        }

        async function loadStats() {
            const params = buildFilterParams();
            const stats = await fetchJson('stats', params);
            updateStats(stats);
        }

        function displayStatusLabel(status) {
            if (!status) return status;
            if (status === 'responding') return 'Responding';
            return status.charAt(0).toUpperCase() + status.slice(1);
        }

        async function loadBarangays() {
            let names = [];

            try {
                const dbNames = await fetchJson('barangays');
                if (Array.isArray(dbNames)) {
                    names = dbNames;
                }
            } catch (error) {
                names = [];
            }

            const merged = Array.from(new Set([...fixedBarangays, ...names]))
                .sort((a, b) => a.localeCompare(b));

            const options = ['<option>All Barangays</option>', ...merged.map(name => `<option value="${name}">${name}</option>`)].join('');
            barangaySelect.innerHTML = options;
        }

        async function loadCrimeTypes() {
            const types = await fetchJson('crime_types');
            const options = ['<option>All Crime Types</option>', ...types.map(type => `<option value="${type}">${type}</option>`)].join('');
            crimeTypeSelect.innerHTML = options;
        }

        async function loadStatuses() {
            const statuses = await fetchJson('statuses');
            const options = ['<option>All Status</option>', ...statuses.map(status => `<option value="${status}">${displayStatusLabel(status)}</option>`)].join('');
            statusSelect.innerHTML = options;
        }

        async function applyFilters() {
            await Promise.all([loadStats(), loadIncidents()]);
        }

        filterButton.addEventListener('click', applyFilters);

        document.addEventListener('DOMContentLoaded', async () => {
            await Promise.all([loadBarangays(), loadCrimeTypes(), loadStatuses()]);
            await applyFilters();
        });
    </script>
</body>
</html>
