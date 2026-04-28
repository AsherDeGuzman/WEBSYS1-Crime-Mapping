<?php
require __DIR__ . '/guard.php';
requireRole(['barangay']);
?>
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
                    <div class="kpi-value" id="kpi-pending">--</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Active cases</div>
                    <div class="kpi-value" id="kpi-active">--</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Resolved this month</div>
                    <div class="kpi-value" id="kpi-resolved">--</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">High risk areas</div>
                    <div class="kpi-value" id="kpi-high-risk">--</div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Report Queue</h2>
                    <button class="btn-secondary">Export CSV</button>
                </div>
                <div class="data-table" id="incident-table">
                    <div class="table-row header">
                        <div>Incident</div>
                        <div>Status</div>
                        <div>Date</div>
                        <div>Severity</div>
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

    <script>
        const statusLabels = {
            pending: 'Pending',
            under_investigation: 'Under investigation',
            action_taken: 'Action taken',
            resolved: 'Resolved',
            dismissed: 'Dismissed'
        };

        const severityLabels = {
            low: 'Low',
            medium: 'Medium',
            high: 'High'
        };

        async function loadDashboard() {
            try {
                const response = await fetch('../api/barangay-incidents.php');
                const data = await response.json();

                if (!data.ok) {
                    console.error(data.error);
                    return;
                }

                // Update KPIs
                document.getElementById('kpi-pending').textContent = data.kpis.pending;
                document.getElementById('kpi-active').textContent = data.kpis.active;
                document.getElementById('kpi-resolved').textContent = data.kpis.resolved_month;
                document.getElementById('kpi-high-risk').textContent = data.kpis.high_risk;

                // Populate table
                const table = document.getElementById('incident-table');
                data.incidents.slice(0, 10).forEach(incident => {
                    const row = document.createElement('div');
                    row.className = 'table-row';
                    row.innerHTML = `
                        <div>${incident.title}</div>
                        <div>${statusLabels[incident.status] || incident.status}</div>
                        <div>${incident.date}</div>
                        <div>${severityLabels[incident.severity] || incident.severity}</div>
                    `;
                    table.appendChild(row);
                });

                if (data.incidents.length === 0) {
                    const row = document.createElement('div');
                    row.className = 'table-row';
                    row.innerHTML = '<div colspan="4">No reports yet.</div>';
                    table.appendChild(row);
                }
            } catch (error) {
                console.error('Failed to load dashboard:', error);
            }
        }

        loadDashboard();
    </script>
