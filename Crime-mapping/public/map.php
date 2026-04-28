<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Crime Map | La Trinidad</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="../assets/css/site.css" />
</head>
<body class="page-map">
    <div class="map-shell">
        <aside class="map-filters">
            <div class="filters-header">
                <div>
                    <div class="eyebrow">Filters</div>
                    <h2>Crime Map</h2>
                </div>
                <a class="btn-tertiary" href="index.php">Back</a>
            </div>

            <div class="filter-group">
                <label class="filter-label">Search</label>
                <input id="search-input" type="text" placeholder="Search name, keyword, or description" />
            </div>

            <div class="filter-group">
                <label class="filter-label">Type of Crime</label>
                <div class="checkbox-list" id="type-filters"></div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Date Range</label>
                <div class="date-range">
                    <input id="date-start" type="date" />
                    <input id="date-end" type="date" />
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Barangay</label>
                <select id="barangay-filter">
                    <option value="">All barangays</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select id="status-filter">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="under_investigation">Under investigation</option>
                    <option value="action_taken">Action taken</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Marker Style</label>
                <div class="toggle-row">
                    <button class="toggle-btn is-active" data-style="dot">Colored dots</button>
                    <button class="toggle-btn" data-style="icon">Category icon</button>
                </div>
            </div>

            <div class="filter-actions">
                <button class="btn-primary" id="apply-filters">Apply Filters</button>
                <button class="btn-secondary" id="reset-filters">Reset</button>
            </div>
        </aside>

        <main class="map-stage">
            <header class="map-topbar">
                <div class="brand">
                    <span class="brand-mark"></span>
                    <div>
                        <div class="brand-title">La Trinidad Crime Mapping</div>
                        <div class="brand-subtitle">Interactive map view</div>
                    </div>
                </div>
                <nav class="map-nav">
                    <a href="index.php">Dashboard</a>
                    <a class="is-active" href="map.php">Map</a>
                    <a href="about.php">About & FAQ</a>
                    <a href="login.php">Login</a>
                </nav>
            </header>

            <div id="map" class="map-canvas map-full"></div>
        </main>

        <aside class="map-details" id="details-panel">
            <div class="details-header">
                <div>
                    <div class="eyebrow">Incident Details</div>
                    <h2 id="details-title">Select a pin</h2>
                </div>
                <button id="close-details" class="btn-tertiary">Close</button>
            </div>
            <div class="details-body" id="details-body">
                <p class="muted">Click a marker to view the full report.</p>
            </div>
            <div class="details-actions">
                <button class="btn-secondary" id="thumbs-up">Thumbs up</button>
                <button class="btn-secondary" id="thumbs-down">Thumbs down</button>
                <button class="btn-primary" id="report-crime">Report a crime</button>
            </div>

            <div class="report-panel" id="report-panel">
                <div class="details-header">
                    <div>
                        <div class="eyebrow">Submit Report</div>
                        <h2>Report a crime</h2>
                    </div>
                    <button id="close-report" class="btn-tertiary">Close</button>
                </div>
                <form id="report-form" class="form-grid">
                    <label>
                        <span>Crime type</span>
                        <select id="report-type" required></select>
                    </label>
                    <label>
                        <span>Title</span>
                        <input id="report-title" type="text" placeholder="Short incident title" required />
                    </label>
                    <label>
                        <span>Description</span>
                        <textarea id="report-description" rows="4" placeholder="Describe what happened" required></textarea>
                    </label>
                    <label>
                        <span>Barangay</span>
                        <select id="report-barangay" required></select>
                    </label>
                    <label>
                        <span>Date</span>
                        <input id="report-date" type="date" required />
                    </label>
                    <label>
                        <span>Time</span>
                        <input id="report-time" type="time" required />
                    </label>
                    <label>
                        <span>Severity</span>
                        <select id="report-severity" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </label>
                    <label>
                        <span>Coordinates</span>
                        <input id="report-coords" type="text" placeholder="Click on the map to set" readonly />
                    </label>
                    <div class="report-actions">
                        <button class="btn-primary" type="submit">Submit report</button>
                        <button class="btn-secondary" id="report-cancel" type="button">Cancel</button>
                    </div>
                    <p class="muted" id="report-status"></p>
                </form>
            </div>
        </aside>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="../assets/js/map.js"></script>
</body>
</html>
