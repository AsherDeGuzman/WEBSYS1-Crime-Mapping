<?php
require __DIR__ . '/guard.php';
requireRole(['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard | La Trinidad Crime Mapping</title>
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
                    <div class="brand-title">Admin Dashboard</div>
                    <div class="brand-subtitle">System-wide monitoring</div>
                </div>
            </div>
            <nav class="site-nav">
                <a href="index.php">Dashboard</a>
                <a href="map.php">Map</a>
                <a href="barangay-dashboard.php">Barangay</a>
                <a class="is-active" href="admin-dashboard.php">Administration</a>
            </nav>
        </header>

        <main>
            <section class="hero hero-tight">
                <div class="hero-copy">
                    <p class="eyebrow">Admin control panel</p>
                    <h1>Oversee reports, users, and system analytics.</h1>
                    <p class="lead">Review all incidents, validate community reports, and manage categories for the entire municipality.</p>
                </div>
            </section>

            <section class="dashboard-kpis">
                <div class="kpi-card">
                    <div class="kpi-label">Total reports</div>
                    <div class="kpi-value">128</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Active cases</div>
                    <div class="kpi-value">34</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Resolved cases</div>
                    <div class="kpi-value">79</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">High severity alerts</div>
                    <div class="kpi-value">6</div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Verification Queue</h2>
                    <button class="btn-secondary">Manage users</button>
                </div>
                <div class="data-table">
                    <div class="table-row header">
                        <div>Incident</div>
                        <div>Barangay</div>
                        <div>Status</div>
                        <div>Severity</div>
                    </div>
                    <div class="table-row">
                        <div>Cybercrime report - phishing</div>
                        <div>Poblacion</div>
                        <div>Pending</div>
                        <div>High</div>
                    </div>
                    <div class="table-row">
                        <div>Illegal dumping report</div>
                        <div>Balili</div>
                        <div>Under investigation</div>
                        <div>Medium</div>
                    </div>
                    <div class="table-row">
                        <div>Traffic collision on highway</div>
                        <div>Ambiong</div>
                        <div>Action taken</div>
                        <div>Low</div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div>Admin oversight dashboard</div>
            <div>Keep the system accurate and transparent.</div>
        </footer>
    </div>
</body>
</html>
