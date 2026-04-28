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
            $result = $oop->getIncidents($barangay, $type, $status, $from, $to);
            echo json_encode($result);
            exit;
        }

        if ($_GET['action'] === 'stats') {
            $result = $oop->getDashboardStats($barangay, $type, $status, $from, $to);
            echo json_encode($result);
            exit;
        }

        if ($_GET['action'] === 'barangays') {
            $result = $oop->getBarangays();
            echo json_encode($result);
            exit;
        }

        if ($_GET['action'] === 'crime_types') {
            $result = $oop->getCrimeTypes();
            echo json_encode($result);
            exit;
        }

        if ($_GET['action'] === 'statuses') {
            $result = $oop->getStatuses();
            echo json_encode($result);
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
            color: var(--blue);
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
            background: #333333;
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
            padding: 22px;
        }

        .filter-bar {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .filter-item {
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--surface-soft);
            padding: 14px 16px;
            font-size: 0.95rem;
            color: #111827;
            width: 100%;
            appearance: none;
            position: relative;
        }

        .filter-item:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .filter-dropdown {
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--surface-soft);
            padding: 14px 16px;
            font-size: 0.95rem;
            color: #111827;
            width: 100%;
            overflow-y: auto;
            max-height: 200px;
            position: relative;
        }

        .filter-dropdown:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .filter-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 4px;
            cursor: pointer;
            border-radius: 8px;
            transition: background 0.2s ease;
        }

        .filter-option:hover {
            background: rgba(37, 99, 235, 0.1);
        }

        .filter-option input[type="checkbox"] {
            cursor: pointer;
            width: 16px;
            height: 16px;
        }

        .filter-option label {
            cursor: pointer;
            flex: 1;
            margin: 0;
            user-select: none;
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
            font-size: 1.25rem;
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

        .map-legend {
            position: absolute;
            z-index: 10000 !important;
            bottom: 22px;
            right: 22px;
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
        }

        .recent-panel {
            display: flex;
            flex-direction: column;
            gap: 18px;
            padding: 22px;
        }

        .recent-panel h2 {
            margin: 0 0 16px;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .incident-item {
            background: var(--surface-soft);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
            transition: all 0.2s ease;
        }

        .incident-item:hover {
            box-shadow: var(--shadow);
            border-color: var(--blue);
        }

        .recent-list {
            display: grid;
            gap: 12px;
            overflow-y: auto;
            max-height: 520px;
            padding-right: 8px;
        }

        .recent-list::-webkit-scrollbar {
            width: 6px;
        }

        .recent-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .recent-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .recent-list::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .incident-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 8px;
        }

        .incident-type {
            font-weight: 700;
            color: #111827;
            margin: 0;
            font-size: 0.95rem;
        }

        .incident-meta {
            color: #6b7280;
            font-size: 0.85rem;
            margin: 0;
        }

        .incident-tag {
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #ffffff;
            white-space: nowrap;
        }

        .tag-pending { background: #dc2626; }
        .tag-responding { background: #2563eb; }
        .tag-resolved { background: #16a34a; }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .filter-bar {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filter-bar {
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

            .filter-bar {
                grid-template-columns: 1fr;
            }

            .filter-item {
                padding: 12px 14px;
            }

            .map-panel {
                min-height: 400px;
            }

            #crime-map {
                min-height: 400px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <h1 class="navbar-brand">🗺️ Crime Mapping System</h1>
            <ul class="navbar-nav">
                <li><a href="index.php" class="navbar-link">Home</a></li>
                <li><a href="admin.php" class="navbar-link">Dashboard</a></li>
                <li><a href="login.php" class="navbar-login">Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="page-shell">
        <header>
            <div>
                <h1 class="page-title">City Crime Map</h1>
                <p class="page-subtitle">Real-time crime incident tracking and analytics</p>
            </div>
        </header>

        <section class="panel">
            <div class="filter-bar">
                <div class="filter-dropdown" id="barangay-container"></div>
                <div class="filter-dropdown" id="crime-type-container"></div>
                <div class="filter-dropdown" id="status-container"></div>
                <input type="date" class="filter-item" id="from-date" aria-label="From date" />
                <input type="date" class="filter-item" id="to-date" aria-label="To date" />
            </div>
        </section>
        <br>

        <section class="stats-grid">
            <article class="stat-card">
                <div class="stat-icon incident">I</div>
                <div>
                    <p class="stat-primary" id="total-incidents">0</p>
                    <p class="stat-label">Total Incidents</p>
                </div>
            </article>
            <article class="stat-card">
                <div class="stat-icon today">T</div>
                <div>
                    <p class="stat-primary" id="incidents-today">0</p>
                    <p class="stat-label">Incidents Today</p>
                </div>
            </article>
            <article class="stat-card">
                <div class="stat-icon crime">C</div>
                <div>
                    <p class="stat-primary" id="most-common-crime">N/A</p>
                    <p class="stat-label">Most Common Crime</p>
                </div>
            </article>
            <article class="stat-card">
                <div class="stat-icon barangay">B</div>
                <div>
                    <p class="stat-primary" id="most-affected-barangay">N/A</p>
                    <p class="stat-label">Most Affected Barangay</p>
                </div>
            </article>
        </section>

        <section class="content-grid">
            <div class="map-panel">
                <div id="crime-map"></div>
                <div class="map-legend">
                    <h3 class="legend-title">Legend</h3>
                    <div class="legend-item">
                        <div class="legend-badge" style="background: #2563eb;"></div>
                        <span>Responding</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-badge" style="background: #dc2626;"></div>
                        <span>Pending</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-badge" style="background: #16a34a;"></div>
                        <span>Resolved</span>
                    </div>
                </div>
            </div>

            <div class="panel recent-panel">
                <h2>Recent Incidents</h2>
                <div class="recent-list"></div>
            </div>
        </section>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const map = L.map('crime-map', {
            center: [14.5995, 120.9842],
            zoom: 13,
            scrollWheelZoom: false,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        const markers = [];
        const incidentList = document.querySelector('.recent-list');
        const fromDateInput = document.getElementById('from-date');
        const toDateInput = document.getElementById('to-date');

        function clearMarkers() {
            markers.forEach(marker => map.removeLayer(marker));
            markers.length = 0;
        }

        function getSelectedValues(containerId) {
            const checkboxes = document.querySelectorAll(`#${containerId} input[type="checkbox"]:checked`);
            return Array.from(checkboxes)
                .map(cb => cb.value)
                .filter(v => v !== 'All');
        }

        function buildFilterParams() {
            const barangays = getSelectedValues('barangay-container');
            const types = getSelectedValues('crime-type-container');
            const statuses = getSelectedValues('status-container');

            const params = {
                action: 'incidents'
            };

            if (barangays.length > 0) params.barangay = barangays.join(',');
            if (types.length > 0) params.type = types.join(',');
            if (statuses.length > 0) params.status = statuses.join(',');
            if (fromDateInput.value) params.from = fromDateInput.value;
            if (toDateInput.value) params.to = toDateInput.value;

            return params;
        }

        function buildStatsParams() {
            const barangays = getSelectedValues('barangay-container');
            const types = getSelectedValues('crime-type-container');
            const statuses = getSelectedValues('status-container');

            const params = {
                action: 'stats'
            };

            if (barangays.length > 0) params.barangay = barangays.join(',');
            if (types.length > 0) params.type = types.join(',');
            if (statuses.length > 0) params.status = statuses.join(',');
            if (fromDateInput.value) params.from = fromDateInput.value;
            if (toDateInput.value) params.to = toDateInput.value;

            return params;
        }

        async function fetchJson(params = {}) {
            try {
                const queryString = new URLSearchParams(params).toString();
                console.log('Fetching:', `?${queryString}`);
                
                const response = await fetch(`?${queryString}`);
                
                if (!response.ok) {
                    console.error(`HTTP error! status: ${response.status}`);
                    return [];
                }
                
                const data = await response.json();
                console.log('Response data:', data);
                return Array.isArray(data) ? data : (data && typeof data === 'object' ? data : []);
            } catch (error) {
                console.error('Fetch error:', error);
                return [];
            }
        }

        function formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);

            if (seconds < 60) return 'just now';
            if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
            if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
            if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
            return date.toLocaleDateString();
        }

        function updateStats(stats) {
            console.log('Updating stats with:', stats);
            if (!stats) return;
            document.getElementById('total-incidents').textContent = stats.total_incidents || 0;
            document.getElementById('incidents-today').textContent = stats.incidents_today || 0;
            document.getElementById('most-common-crime').textContent = stats.most_common_crime || 'N/A';
            document.getElementById('most-affected-barangay').textContent = stats.most_affected_barangay || 'N/A';
        }

        function renderRecentList(items) {
            console.log('Rendering recent list with items:', items);
            if (!items || items.length === 0) {
                incidentList.innerHTML = '<p style="color: #6b7280; text-align: center; padding: 20px;">No incidents found</p>';
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
        }

        function renderMarkers(items) {
            console.log('Rendering markers with items:', items);
            clearMarkers();
            const bounds = [];

            if (!items || items.length === 0) {
                console.log('No items to render as markers');
                return;
            }

            items.forEach(item => {
                if (!item.latitude || !item.longitude) {
                    console.log('Skipping item - missing coordinates:', item);
                    return;
                }

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

            console.log('Total markers rendered:', markers.length);
            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [80, 80] });
            }
        }

        async function loadIncidents() {
            console.log('Loading incidents...');
            const params = buildFilterParams();
            console.log('Filter params:', params);
            const incidents = await fetchJson(params);
            console.log('Incidents received:', incidents);
            renderRecentList(incidents);
            renderMarkers(incidents);
        }

        async function loadStats() {
            console.log('Loading stats...');
            const params = buildStatsParams();
            console.log('Stats params:', params);
            const stats = await fetchJson(params);
            console.log('Stats received:', stats);
            updateStats(stats);
        }

        async function applyFilters() {
            console.log('=== Applying filters ===');
            const barangays = getSelectedValues('barangay-container');
            const types = getSelectedValues('crime-type-container');
            const statuses = getSelectedValues('status-container');
            
            console.log('Selected Barangays:', barangays);
            console.log('Selected Types:', types);
            console.log('Selected Statuses:', statuses);
            console.log('From Date:', fromDateInput.value);
            console.log('To Date:', toDateInput.value);
            
            await Promise.all([loadIncidents(), loadStats()]);
        }

        function createCheckboxGroup(containerId, items, groupName) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';

            // "All" option
            const allDiv = document.createElement('div');
            allDiv.className = 'filter-option';
            const allCheckbox = document.createElement('input');
            allCheckbox.type = 'checkbox';
            allCheckbox.id = `${groupName}-all`;
            allCheckbox.value = 'All';
            allCheckbox.checked = true;
            
            allCheckbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                        if (cb.id !== `${groupName}-all`) cb.checked = false;
                    });
                }
                applyFilters();
            });

            const allLabel = document.createElement('label');
            allLabel.htmlFor = `${groupName}-all`;
            allLabel.textContent = `All ${groupName}`;

            allDiv.appendChild(allCheckbox);
            allDiv.appendChild(allLabel);
            container.appendChild(allDiv);

            // Individual options
            if (items && items.length > 0) {
                items.forEach((item, index) => {
                    const div = document.createElement('div');
                    div.className = 'filter-option';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.id = `${groupName}-${index}`;
                    checkbox.value = item;

                    checkbox.addEventListener('change', (e) => {
                        if (e.target.checked) {
                            allCheckbox.checked = false;
                        }
                        applyFilters();
                    });

                    const label = document.createElement('label');
                    label.htmlFor = `${groupName}-${index}`;
                    label.textContent = item.charAt(0).toUpperCase() + item.slice(1);

                    div.appendChild(checkbox);
                    div.appendChild(label);
                    container.appendChild(div);
                });
            }
        }

        async function loadBarangays() {
            console.log('Loading barangays...');
            const barangays = await fetchJson({ action: 'barangays' });
            console.log('Barangays loaded:', barangays);
            createCheckboxGroup('barangay-container', barangays, 'Barangay');
        }

        async function loadCrimeTypes() {
            console.log('Loading crime types...');
            const types = await fetchJson({ action: 'crime_types' });
            console.log('Crime types loaded:', types);
            createCheckboxGroup('crime-type-container', types, 'Crime Type');
        }

        async function loadStatuses() {
            console.log('Loading statuses...');
            const statuses = await fetchJson({ action: 'statuses' });
            console.log('Statuses loaded:', statuses);
            createCheckboxGroup('status-container', statuses, 'Status');
        }

        let dateFilterTimeout;
        fromDateInput.addEventListener('change', () => {
            clearTimeout(dateFilterTimeout);
            dateFilterTimeout = setTimeout(applyFilters, 500);
        });

        toDateInput.addEventListener('change', () => {
            clearTimeout(dateFilterTimeout);
            dateFilterTimeout = setTimeout(applyFilters, 500);
        });

        // Initialize on page load
        async function initializePage() {
            console.log('=== Initializing page ===');
            await loadBarangays();
            await loadCrimeTypes();
            await loadStatuses();
            // Load initial data with no filters (show all incidents)
            await loadIncidents();
            await loadStats();
            console.log('=== Page initialization complete ===');
        }

        // Run initialization when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePage);
        } else {
            initializePage();
        }
    </script>
</body>
</html>