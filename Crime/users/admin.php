<?php
    include '../conn.php';
    include '../oop.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $oop = new Crime_Mapping(new Connection());

    if (!isset($_SESSION['Username']) || !isset($_SESSION['UserRole'])) {
        header('Location: login.php');
        exit;
    }

    if (strtolower((string)$_SESSION['UserRole']) !== 'admin') {
        header('Location: ../index.php');
        exit;
    }

    $conn = $oop->getConnection();
    $alert = null;

    if (isset($_POST['logout'])) {
        $oop->logout();
    }

    if (($_GET['action'] ?? '') === 'reverse_geocode') {
        header('Content-Type: application/json');

        try {
            $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 0;
            $lng = isset($_GET['lng']) ? (float)$_GET['lng'] : 0;

            if ($lat == 0 || $lng == 0) {
                throw new Exception('Invalid coordinates.');
            }

            $url = 'https://nominatim.openstreetmap.org/reverse?' . http_build_query([
                'format' => 'jsonv2',
                'lat' => $lat,
                'lon' => $lng,
                'zoom' => 18,
                'addressdetails' => 1
            ]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: CrimeMapping/1.0\r\nAccept: application/json\r\n",
                    'timeout' => 8
                ]
            ]);

            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                throw new Exception('Unable to resolve location address.');
            }

            $data = json_decode($response, true);
            $address = trim($data['display_name'] ?? '');

            echo json_encode([
                'success' => $address !== '',
                'address' => $address,
                'message' => $address !== '' ? 'Address resolved.' : 'Address not found.'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'address' => '',
                'message' => $e->getMessage()
            ]);
        }

        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_incident') {
        $incidentId = (int)($_POST['incident_id'] ?? 0);

        if ($incidentId > 0) {
            try {
                try {
                    $stmt = $conn->prepare("UPDATE incidents SET status = 'responding' WHERE incident_id = ? AND status = 'pending'");
                    $stmt->execute([$incidentId]);
                } catch (PDOException $e) {
                    $stmt = $conn->prepare("UPDATE incidents SET status = 'under_investigation' WHERE incident_id = ? AND status = 'pending'");
                    $stmt->execute([$incidentId]);
                }

                $alert = ['type' => 'success', 'message' => 'Incident approved successfully.'];
            } catch (Exception $e) {
                $alert = ['type' => 'danger', 'message' => 'Unable to approve incident.'];
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_admin_report') {
        try {
            $crimeName = trim($_POST['crime_name'] ?? '');
            $crimeType = trim($_POST['crime_type'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $barangayIdInput = (int)($_POST['barangay_id'] ?? 0);
            $incidentDatetime = trim($_POST['incident_datetime'] ?? '');
            $severity = trim($_POST['severity_level'] ?? '');
            $locationAddress = trim($_POST['location_address'] ?? '');
            $latitude = (float)($_POST['latitude'] ?? 0);
            $longitude = (float)($_POST['longitude'] ?? 0);

            if ($crimeName === '' || $crimeType === '' || $description === '' || $barangayIdInput <= 0) {
                throw new Exception('Please complete all required report fields.');
            }

            if ($latitude == 0 || $longitude == 0) {
                throw new Exception('Please pin the incident location on the map.');
            }

            $fullDescription = "Crime Type: {$crimeType}";
            if ($severity !== '') {
                $fullDescription .= "\nSeverity Level: " . ucfirst($severity);
            }
            if ($locationAddress !== '') {
                $fullDescription .= "\nAddress: {$locationAddress}";
            }
            $fullDescription .= "\n\nDetails:\n{$description}";

            if ($incidentDatetime !== '' && strtotime($incidentDatetime) !== false) {
                $createdAt = date('Y-m-d H:i:s', strtotime($incidentDatetime));
                $stmt = $conn->prepare(
                    "INSERT INTO incidents (title, description, barangay_id, latitude, longitude, status, created_at)
                     VALUES (?, ?, ?, ?, ?, 'pending', ?)"
                );
                $stmt->execute([$crimeName, $fullDescription, $barangayIdInput, $latitude, $longitude, $createdAt]);
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO incidents (title, description, barangay_id, latitude, longitude, status, created_at)
                     VALUES (?, ?, ?, ?, ?, 'pending', NOW())"
                );
                $stmt->execute([$crimeName, $fullDescription, $barangayIdInput, $latitude, $longitude]);
            }

            $alert = ['type' => 'success', 'message' => 'Report submitted successfully.'];
        } catch (Exception $e) {
            $alert = ['type' => 'danger', 'message' => $e->getMessage()];
        }
    }

    $stats = $oop->getDashboardStats();
    $recentIncidents = array_slice($oop->getIncidents(), 0, 12);

    $stmt = $conn->prepare('SELECT barangay_id, barangay_name FROM barangays ORDER BY barangay_name');
    $stmt->execute();
    $barangays = $stmt->fetchAll();

    $roleCounts = [
        'admin' => 0,
        'barangay' => 0
    ];

    $stmt = $conn->prepare('SELECT role, COUNT(*) AS total FROM users GROUP BY role');
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        $role = strtolower($row['role']);
        if (isset($roleCounts[$role])) {
            $roleCounts[$role] = (int)$row['total'];
        }
    }
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <title>Admin Dashboard</title>
    <style>
        #admin-report-map {
            height: 280px;
            border: 1px solid #dee2e6;
            border-radius: 0.75rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../map.php">Crime Mapping</a>
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-dark">Admin: <?php echo htmlspecialchars($_SESSION['Username']); ?></span>
                <form action="admin.php" method="post" class="m-0">
                    <button type="submit" class="btn btn-sm btn-outline-danger" name="logout">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h1 class="h3 mb-1">Admin Dashboard</h1>
                <p class="text-muted mb-0">Overview of incident reports and system activity.</p>
            </div>
            <a href="../map.php" class="btn btn-dark">Open Map</a>
        </div>

        <?php if ($alert): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?>" role="alert">
                <?php echo htmlspecialchars($alert['message']); ?>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Total Incidents</p>
                        <h2 class="h4 mb-0"><?php echo (int)$stats['total_incidents']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Incidents Today</p>
                        <h2 class="h4 mb-0"><?php echo (int)$stats['incidents_today']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Admin Users</p>
                        <h2 class="h4 mb-0"><?php echo (int)$roleCounts['admin']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Barangay Users</p>
                        <h2 class="h4 mb-0"><?php echo (int)$roleCounts['barangay']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Submit Incident Report (Admin)</h2>
            </div>
            <div class="card-body">
                <form action="admin.php" method="post" id="admin-report-form">
                    <input type="hidden" name="action" value="submit_admin_report">

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="crime_name">Crime Name</label>
                            <input type="text" class="form-control" id="crime_name" name="crime_name" required>
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
                            <label class="form-label" for="barangay_id">Barangay</label>
                            <select class="form-select" id="barangay_id" name="barangay_id" required>
                                <option value="">Select barangay</option>
                                <?php foreach ($barangays as $barangay): ?>
                                    <option value="<?php echo (int)$barangay['barangay_id']; ?>"><?php echo htmlspecialchars($barangay['barangay_name']); ?></option>
                                <?php endforeach; ?>
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

                        <div class="col-12">
                            <label class="form-label" for="description">Incident Details</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Incident Location</label>
                        <div id="admin-report-map"></div>
                        <div class="small text-muted mt-2" id="admin-location-label">Click the map to pin incident location.</div>
                        <div class="mt-2">
                            <label class="form-label" for="admin-location-address">Resolved Address</label>
                            <input type="text" class="form-control" id="admin-location-address" name="location_address" placeholder="Address will auto-fill after map click" readonly>
                        </div>
                        <input type="hidden" id="admin-latitude" name="latitude" value="0">
                        <input type="hidden" id="admin-longitude" name="longitude" value="0">
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" id="admin-reset-location">Reset Location</button>
                        <button type="submit" class="btn btn-primary">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Recent Incidents</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Barangay</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentIncidents)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No incidents found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentIncidents as $incident): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($incident['title']); ?></td>
                                    <td><?php echo htmlspecialchars($incident['barangay_name']); ?></td>
                                    <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars(ucfirst($incident['status'])); ?></span></td>
                                    <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($incident['created_at']))); ?></td>
                                    <td class="text-end">
                                        <?php if (strtolower((string)$incident['status']) === 'pending'): ?>
                                            <form action="admin.php" method="post" class="d-inline">
                                                <input type="hidden" name="action" value="approve_incident">
                                                <input type="hidden" name="incident_id" value="<?php echo (int)$incident['incident_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">Reviewed</span>
                                        <?php endif; ?>
                                    </td>
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
        const adminMap = L.map('admin-report-map', {
            center: [16.4023, 120.5960],
            zoom: 14,
            scrollWheelZoom: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(adminMap);

        let adminMarker = null;

        async function resolveAdminAddress(lat, lng) {
            const params = new URLSearchParams({ action: 'reverse_geocode', lat, lng });

            try {
                const response = await fetch(`admin.php?${params.toString()}`);
                const result = await response.json();

                if (result.success && result.address) {
                    document.getElementById('admin-location-address').value = result.address;
                    document.getElementById('admin-location-label').textContent = `Address: ${result.address}`;
                } else {
                    document.getElementById('admin-location-address').value = '';
                    document.getElementById('admin-location-label').textContent = 'Address not found for this pin. You may click another location.';
                }
            } catch (error) {
                document.getElementById('admin-location-address').value = '';
                document.getElementById('admin-location-label').textContent = 'Unable to resolve address right now. Please try another click.';
            }
        }

        async function setAdminLocation(lat, lng) {
            if (adminMarker) {
                adminMap.removeLayer(adminMarker);
            }

            adminMarker = L.marker([lat, lng]).addTo(adminMap);
            document.getElementById('admin-latitude').value = lat;
            document.getElementById('admin-longitude').value = lng;
            document.getElementById('admin-location-label').textContent = 'Resolving address...';
            await resolveAdminAddress(lat, lng);
        }

        adminMap.on('click', function (event) {
            setAdminLocation(event.latlng.lat, event.latlng.lng);
        });

        document.getElementById('admin-reset-location').addEventListener('click', function () {
            if (adminMarker) {
                adminMap.removeLayer(adminMarker);
                adminMarker = null;
            }

            document.getElementById('admin-latitude').value = '0';
            document.getElementById('admin-longitude').value = '0';
            document.getElementById('admin-location-label').textContent = 'Click the map to pin incident location.';
            document.getElementById('admin-location-address').value = '';
        });

        setTimeout(() => adminMap.invalidateSize(), 100);
    </script>
</body>
</html>