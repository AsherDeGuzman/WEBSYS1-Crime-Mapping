<?php
    require_once __DIR__ . '/../public/conn.php';
    require_once __DIR__ . '/../public/oop.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $oop = new Crime_Mapping(new Connection());

    if (!isset($_SESSION['Username']) || !isset($_SESSION['UserRole'])) {
        header('Location: ../public/login.php');
        exit;
    }

    if ($_SESSION['UserRole'] !== 'admin') {
        header('Location: ../index.php');
        exit;
    }

    if (isset($_POST['logout'])) {
        $oop->logout();
    }

    $stats = $oop->getDashboardStats();
    $recentIncidents = array_slice($oop->getIncidents(), 0, 8);

    $conn = $oop->getConnection();
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
    <title>Admin Dashboard</title>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentIncidents)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No incidents found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentIncidents as $incident): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($incident['title']); ?></td>
                                    <td><?php echo htmlspecialchars($incident['barangay_name']); ?></td>
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
</body>
</html>