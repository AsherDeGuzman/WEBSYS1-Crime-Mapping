<?php
    include '../conn.php';
    include '../oop.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['Username']) || !isset($_SESSION['UserRole'])) {
        header('Location: login.php');
        exit;
    }

    if (strtolower((string)$_SESSION['UserRole']) !== 'barangay') {
        header('Location: ../index.php');
        exit;
    }

    $oop = new Crime_Mapping(new Connection());
    $conn = $oop->getConnection();

    if (isset($_POST['logout'])) {
        $oop->logout();
    }

    $barangayId = $_SESSION['BarangayID'] ?? null;
    if (!$barangayId) {
        $stmt = $conn->prepare('SELECT barangay_id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$_SESSION['Username']]);
        $row = $stmt->fetch();
        $barangayId = $row['barangay_id'] ?? null;
        $_SESSION['BarangayID'] = $barangayId;
    }

    if (!$barangayId) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php?error=missing_barangay');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_report') {
        header('Content-Type: application/json');

        try {
            $crimeName = trim($_POST['crime_name'] ?? '');
            $crimeType = trim($_POST['crime_type'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $incidentDatetime = trim($_POST['incident_datetime'] ?? '');
            $severity = trim($_POST['severity_level'] ?? '');
            $peopleInvolved = trim($_POST['people_involved'] ?? '');
            $landmark = trim($_POST['landmark'] ?? '');
            $latitude = floatval($_POST['latitude'] ?? 0);
            $longitude = floatval($_POST['longitude'] ?? 0);

            if ($crimeName === '' || $crimeType === '' || $description === '') {
                throw new Exception('Please complete crime name, crime type, and incident description.');
            }

            if ($latitude == 0 || $longitude == 0) {
                throw new Exception('Please select the incident location on the map.');
            }

            $extraLines = [];
            $extraLines[] = 'Crime Type: ' . $crimeType;
            if ($incidentDatetime !== '') {
                $extraLines[] = 'Incident Date/Time: ' . date('Y-m-d h:i A', strtotime($incidentDatetime));
            }
            if ($severity !== '') {
                $extraLines[] = 'Severity Level: ' . ucfirst($severity);
            }
            if ($peopleInvolved !== '') {
                $extraLines[] = 'People Involved: ' . $peopleInvolved;
            }
            if ($landmark !== '') {
                $extraLines[] = 'Nearest Landmark: ' . $landmark;
            }

            $fullDescription = implode("\n", $extraLines) . "\n\nDetails:\n" . $description;

            $stmt = $conn->prepare(
                "INSERT INTO incidents (title, description, barangay_id, latitude, longitude, status, created_at)
                 VALUES (?, ?, ?, ?, ?, 'pending', NOW())"
            );
            $stmt->execute([$crimeName, $fullDescription, $barangayId, $latitude, $longitude]);

            echo json_encode([
                'success' => true,
                'message' => 'Crime report submitted successfully. It is now marked as pending for review.'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        exit;
    }

    $stmt = $conn->prepare('SELECT barangay_name FROM barangays WHERE barangay_id = ? LIMIT 1');
    $stmt->execute([$barangayId]);
    $barangay = $stmt->fetch();
    $barangayName = $barangay['barangay_name'] ?? 'Unknown Barangay';

    $stmt = $conn->prepare(
        "SELECT
            COUNT(*) AS total_incidents,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS incidents_today,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_incidents,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_incidents
         FROM incidents
         WHERE barangay_id = ?"
    );
    $stmt->execute([$barangayId]);
    $stats = $stmt->fetch() ?: [];

    $stmt = $conn->prepare(
        'SELECT title, description, status, created_at
         FROM incidents
         WHERE barangay_id = ?
         ORDER BY created_at DESC
         LIMIT 8'
    );
    $stmt->execute([$barangayId]);
    $recentIncidents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <title>Barangay Dashboard</title>
    <style>
        #report-map {
            height: 320px;
            border-radius: 0.75rem;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../map.php">Crime Mapping</a>
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-primary"><?php echo htmlspecialchars($barangayName); ?></span>
                <form action="barangay_dashboard.php" method="post" class="m-0">
                    <button type="submit" class="btn btn-sm btn-outline-danger" name="logout">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h1 class="h3 mb-1">Barangay Dashboard</h1>
                <p class="text-muted mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['Username']); ?>.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="#submit-report-card" class="btn btn-dark">Submit Report</a>
                <a href="../map.php" class="btn btn-outline-secondary">View Map</a>
            </div>
        </div>

        <div id="submit-report-card" class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Submit Crime Report</h2>
            </div>
            <div class="card-body">
                <div id="report-alert" class="alert d-none" role="alert"></div>

                <form id="crime-report-form">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="crime_name">Crime Name</label>
                            <input type="text" class="form-control" id="crime_name" name="crime_name" placeholder="e.g., Theft" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="crime_type">Crime Type</label>
                            <select class="form-select" id="crime_type" name="crime_type" required>
                                <option value="">Select type</option>
                                <option value="Property Crime">Property Crime</option>
                                <option value="Violent Crime">Violent Crime</option>
                                <option value="Public Safety">Public Safety</option>
                                <option value="Drug-Related">Drug-Related</option>
                                <option value="Traffic Incident">Traffic Incident</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="incident_datetime">Incident Date & Time</label>
                            <input type="datetime-local" class="form-control" id="incident_datetime" name="incident_datetime">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="severity_level">Severity Level</label>
                            <select class="form-select" id="severity_level" name="severity_level">
                                <option value="">Select severity</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="people_involved">People Involved</label>
                            <input type="text" class="form-control" id="people_involved" name="people_involved" placeholder="Names or estimate">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="landmark">Nearest Landmark</label>
                            <input type="text" class="form-control" id="landmark" name="landmark" placeholder="Street, sitio, school, etc.">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Incident Details</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Provide complete details of what happened..." required></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Incident Location (Map)</label>
                        <div id="report-map"></div>
                        <div class="small text-muted mt-2" id="location-label">Click on the map to set the exact location.</div>
                        <input type="hidden" id="latitude" name="latitude" value="0">
                        <input type="hidden" id="longitude" name="longitude" value="0">
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" id="reset-location">Reset Location</button>
                        <button type="submit" class="btn btn-primary" id="submit-report-btn">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Total Reports</p>
                        <h2 class="h4 mb-0"><?php echo (int)($stats['total_incidents'] ?? 0); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Reports Today</p>
                        <h2 class="h4 mb-0"><?php echo (int)($stats['incidents_today'] ?? 0); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Pending</p>
                        <h2 class="h4 mb-0"><?php echo (int)($stats['pending_incidents'] ?? 0); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Resolved</p>
                        <h2 class="h4 mb-0"><?php echo (int)($stats['resolved_incidents'] ?? 0); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Recent Barangay Reports</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentIncidents)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No reports found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentIncidents as $incident): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($incident['title']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($incident['description']); ?></small>
                                    </td>
                                    <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars(ucfirst($incident['status'])); ?></span></td>
                                    <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($incident['created_at']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const reportMap = L.map('report-map', {
            center: [16.4023, 120.5960],
            zoom: 14,
            scrollWheelZoom: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(reportMap);

        let reportMarker = null;

        function setLocation(lat, lng) {
            if (reportMarker) {
                reportMap.removeLayer(reportMarker);
            }

            reportMarker = L.marker([lat, lng]).addTo(reportMap);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            document.getElementById('location-label').textContent = `Selected location: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }

        reportMap.on('click', function (event) {
            setLocation(event.latlng.lat, event.latlng.lng);
        });

        document.getElementById('reset-location').addEventListener('click', function () {
            if (reportMarker) {
                reportMap.removeLayer(reportMarker);
                reportMarker = null;
            }

            document.getElementById('latitude').value = '0';
            document.getElementById('longitude').value = '0';
            document.getElementById('location-label').textContent = 'Click on the map to set the exact location.';
        });

        function showReportAlert(type, message) {
            const alertBox = document.getElementById('report-alert');
            alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
            alertBox.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
            alertBox.textContent = message;
        }

        document.getElementById('crime-report-form').addEventListener('submit', async function (e) {
            e.preventDefault();

            const submitButton = document.getElementById('submit-report-btn');
            const originalLabel = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';

            try {
                const formData = new FormData(this);
                formData.append('action', 'submit_report');

                const response = await fetch('barangay_dashboard.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showReportAlert('success', result.message);
                    this.reset();
                    if (reportMarker) {
                        reportMap.removeLayer(reportMarker);
                        reportMarker = null;
                    }
                    document.getElementById('latitude').value = '0';
                    document.getElementById('longitude').value = '0';
                    document.getElementById('location-label').textContent = 'Click on the map to set the exact location.';

                    setTimeout(() => {
                        window.location.reload();
                    }, 900);
                } else {
                    showReportAlert('error', result.message || 'Unable to submit report.');
                }
            } catch (error) {
                showReportAlert('error', 'Submission failed. Please try again.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalLabel;
            }
        });

        setTimeout(() => {
            reportMap.invalidateSize();
        }, 150);
    </script>
</body>
</html>