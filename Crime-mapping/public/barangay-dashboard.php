<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Barangay Dashboard | La Trinidad Crime Mapping</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/site.css" />
</head>
<body>
    <div class="page-shell">
        <header class="site-header">
            <div class="brand">
                <span class="brand-mark"></span>
                <div>
                    <div class="brand-title">Barangay Dashboard</div>
                    <div class="brand-subtitle">Incident verification and updates</div>
                </div>
            </div>
            <nav class="site-nav">
                <a href="index.php">Dashboard</a>
                <a href="map.php">Map</a>
                <a class="is-active" href="barangay-dashboard.php">Barangay</a>
                <a href="about.php">About & FAQ</a>
            </nav>
        </header>

        <main>
            <section class="hero hero-tight">
                <div class="hero-copy">
                    <p class="eyebrow">Barangay overview</p>
                    <h1>Monitor reports in your assigned area.</h1>
                    <p class="lead">Review new submissions, update case statuses, and track barangay safety metrics.</p>
                </div>
            </section>

            <section class="dashboard-kpis">
                <div class="kpi-card">
                    <div class="kpi-label">Pending reports</div>
                    <div class="kpi-value">12</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Active cases</div>
                    <div class="kpi-value">7</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Resolved this month</div>
                    <div class="kpi-value">5</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">High risk areas</div>
                    <div class="kpi-value">2</div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Report Queue</h2>
                    <button class="btn-secondary">Export CSV</button>
                </div>
                <div class="data-table">
                    <div class="table-row header">
                        <div>Incident</div>
                        <div>Status</div>
                        <div>Date</div>
                        <div>Severity</div>
                    </div>
                    <div class="table-row">
                        <div>Illegal parking complaint</div>
                        <div>Pending</div>
                        <div>Apr 27, 2026</div>
                        <div>Low</div>
                    </div>
                    <div class="table-row">
                        <div>Robbery incident near market</div>
                        <div>Under investigation</div>
                        <div>Apr 26, 2026</div>
                        <div>High</div>
                    </div>
                    <div class="table-row">
                        <div>Vandalism at school</div>
                        <div>Action taken</div>
                        <div>Apr 25, 2026</div>
                        <div>Medium</div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div>Barangay operations dashboard</div>
            <div>Update reports promptly to keep the community informed.</div>
        </footer>
    </div>
</body>
</html>
