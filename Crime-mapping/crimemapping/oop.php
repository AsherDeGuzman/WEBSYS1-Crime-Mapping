<?php
    class Crime_Mapping {
        private $conn;

        public function __construct($connection) {
            if ($connection instanceof Connection) {
                $this->conn = $connection->getConnection();
            } else {
                $this->conn = $connection;
            }
        }

        public function getConnection() {
            return $this->conn;
        }

        public function login($username, $password) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
            $stmt->execute([
                $username,
                $password
            ]);

            $rows = $stmt->fetchAll();

            if (!$rows || count($rows) === 0) {
                echo "<script>
                        alert('Incorrect Password or Username');
                        window.location.href='login.php';
                    </script>";
                return;
            }

            foreach ($rows as $row) {
                if ($username === $row['username'] && $password === $row['password'] && $row['role'] === 'admin') {
                    $_SESSION['Username'] = $row['username'];
                    $_SESSION['Password'] = $row['password'];
                    header("Location: /crime_mapping_app/users/admin.php");
                    exit;
                } elseif ($username === $row['username'] && $password === $row['password'] && $row['role'] === 'barangay') {
                    $_SESSION['Username'] = $row['username'];
                    $_SESSION['Password'] = $row['password'];
                    header("Location: /crime_mapping_app/users/barangay.php");
                    exit;
                }
            }

            echo "<script>
                    alert('Incorrect Password or Username');
                    window.location.href='login.php';
                </script>";
        }

        public function getBarangays() {
            try {
                $stmt = $this->conn->prepare('SELECT DISTINCT barangay_name FROM barangays ORDER BY barangay_name');
                if (!$stmt->execute()) {
                    error_log("Execute failed: " . implode(", ", $stmt->errorInfo()));
                    return [];
                }
                $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
                error_log("Barangays: " . json_encode($result));
                return is_array($result) ? $result : [];
            } catch (Exception $e) {
                error_log("Error in getBarangays: " . $e->getMessage());
                return [];
            }
        }

        public function getCrimeTypes() {
            try {
                $stmt = $this->conn->prepare('SELECT DISTINCT title FROM incidents WHERE title IS NOT NULL AND title != "" ORDER BY title');
                if (!$stmt->execute()) {
                    error_log("Execute failed: " . implode(", ", $stmt->errorInfo()));
                    return [];
                }
                $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
                error_log("Crime Types: " . json_encode($result));
                return is_array($result) ? $result : [];
            } catch (Exception $e) {
                error_log("Error in getCrimeTypes: " . $e->getMessage());
                return [];
            }
        }

        public function getStatuses() {
            try {
                $stmt = $this->conn->prepare("SELECT DISTINCT status FROM incidents WHERE status IS NOT NULL AND status != '' ORDER BY status");
                if (!$stmt->execute()) {
                    error_log("Execute failed: " . implode(", ", $stmt->errorInfo()));
                    return [];
                }
                $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
                error_log("Statuses: " . json_encode($result));
                return is_array($result) ? $result : [];
            } catch (Exception $e) {
                error_log("Error in getStatuses: " . $e->getMessage());
                return [];
            }
        }

        public function getIncidents($barangay = null, $type = null, $status = null, $from = null, $to = null) {
            try {
                $where = [];
                $params = [];

                error_log("=== getIncidents called ===");
                error_log("barangay: " . var_export($barangay, true));
                error_log("type: " . var_export($type, true));
                error_log("status: " . var_export($status, true));
                error_log("from: " . var_export($from, true));
                error_log("to: " . var_export($to, true));

                // Build WHERE clause
                if (!empty($barangay) && $barangay !== 'null') {
                    $barangayList = array_filter(array_map('trim', explode(',', $barangay)));
                    error_log("Barangay list: " . json_encode($barangayList));
                    if (!empty($barangayList)) {
                        $placeholders = implode(',', array_fill(0, count($barangayList), '?'));
                        $where[] = "b.barangay_name IN ($placeholders)";
                        $params = array_merge($params, $barangayList);
                    }
                }

                if (!empty($type) && $type !== 'null') {
                    $typeList = array_filter(array_map('trim', explode(',', $type)));
                    error_log("Type list: " . json_encode($typeList));
                    if (!empty($typeList)) {
                        $placeholders = implode(',', array_fill(0, count($typeList), '?'));
                        $where[] = "i.title IN ($placeholders)";
                        $params = array_merge($params, $typeList);
                    }
                }

                if (!empty($status) && $status !== 'null') {
                    $statusList = array_filter(array_map('trim', explode(',', $status)));
                    error_log("Status list: " . json_encode($statusList));
                    if (!empty($statusList)) {
                        $placeholders = implode(',', array_fill(0, count($statusList), '?'));
                        $where[] = "i.status IN ($placeholders)";
                        $params = array_merge($params, $statusList);
                    }
                }

                if (!empty($from) && $from !== 'null') {
                    $where[] = 'DATE(i.created_at) >= ?';
                    $params[] = $from;
                }

                if (!empty($to) && $to !== 'null') {
                    $where[] = 'DATE(i.created_at) <= ?';
                    $params[] = $to;
                }

                $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

                $sql = 'SELECT i.incident_id, i.title, i.description, i.latitude, i.longitude, i.status, i.created_at, b.barangay_name
                        FROM incidents i
                        LEFT JOIN barangays b ON i.barangay_id = b.barangay_id' . $whereClause . ' ORDER BY i.created_at DESC LIMIT 200';

                error_log("SQL Query: " . $sql);
                error_log("Params: " . json_encode($params));

                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    error_log("Prepare failed: " . implode(", ", $this->conn->errorInfo()));
                    return [];
                }

                if (!$stmt->execute($params)) {
                    error_log("Execute failed: " . implode(", ", $stmt->errorInfo()));
                    return [];
                }

                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("Incidents returned: " . count($result));
                error_log("First incident: " . json_encode(isset($result[0]) ? $result[0] : 'none'));
                
                return is_array($result) ? $result : [];
            } catch (Exception $e) {
                error_log("Exception in getIncidents: " . $e->getMessage());
                error_log("Exception trace: " . $e->getTraceAsString());
                return [];
            }
        }

        public function getDashboardStats($barangay = null, $type = null, $status = null, $from = null, $to = null) {
            try {
                $where = [];
                $params = [];

                error_log("=== getDashboardStats called ===");

                // Build WHERE clause
                if (!empty($barangay) && $barangay !== 'null') {
                    $barangayList = array_filter(array_map('trim', explode(',', $barangay)));
                    if (!empty($barangayList)) {
                        $placeholders = implode(',', array_fill(0, count($barangayList), '?'));
                        $where[] = "b.barangay_name IN ($placeholders)";
                        $params = array_merge($params, $barangayList);
                    }
                }

                if (!empty($type) && $type !== 'null') {
                    $typeList = array_filter(array_map('trim', explode(',', $type)));
                    if (!empty($typeList)) {
                        $placeholders = implode(',', array_fill(0, count($typeList), '?'));
                        $where[] = "i.title IN ($placeholders)";
                        $params = array_merge($params, $typeList);
                    }
                }

                if (!empty($status) && $status !== 'null') {
                    $statusList = array_filter(array_map('trim', explode(',', $status)));
                    if (!empty($statusList)) {
                        $placeholders = implode(',', array_fill(0, count($statusList), '?'));
                        $where[] = "i.status IN ($placeholders)";
                        $params = array_merge($params, $statusList);
                    }
                }

                if (!empty($from) && $from !== 'null') {
                    $where[] = 'DATE(i.created_at) >= ?';
                    $params[] = $from;
                }

                if (!empty($to) && $to !== 'null') {
                    $where[] = 'DATE(i.created_at) <= ?';
                    $params[] = $to;
                }

                $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

                // Get counts
                $sql = 'SELECT 
                        COUNT(*) as total_incidents,
                        SUM(CASE WHEN DATE(i.created_at) = CURDATE() THEN 1 ELSE 0 END) as incidents_today
                        FROM incidents i
                        LEFT JOIN barangays b ON i.barangay_id = b.barangay_id' . $whereClause;

                error_log("Stats SQL: " . $sql);
                error_log("Stats Params: " . json_encode($params));

                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    error_log("Stats Prepare failed: " . implode(", ", $this->conn->errorInfo()));
                    return [
                        'total_incidents' => 0,
                        'incidents_today' => 0,
                        'most_common_crime' => 'N/A',
                        'most_affected_barangay' => 'N/A'
                    ];
                }

                if (!$stmt->execute($params)) {
                    error_log("Stats Execute failed: " . implode(", ", $stmt->errorInfo()));
                    return [
                        'total_incidents' => 0,
                        'incidents_today' => 0,
                        'most_common_crime' => 'N/A',
                        'most_affected_barangay' => 'N/A'
                    ];
                }

                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                // Get most common crime
                $sql2 = 'SELECT i.title FROM incidents i
                         LEFT JOIN barangays b ON i.barangay_id = b.barangay_id' . $whereClause . ' 
                         GROUP BY i.title ORDER BY COUNT(*) DESC LIMIT 1';

                $stmt2 = $this->conn->prepare($sql2);
                if (!$stmt2 || !$stmt2->execute($params)) {
                    error_log("Crime query failed");
                    $crimeResult = null;
                } else {
                    $crimeResult = $stmt2->fetch(PDO::FETCH_ASSOC);
                }

                // Get most affected barangay
                $sql3 = 'SELECT b.barangay_name FROM incidents i
                         LEFT JOIN barangays b ON i.barangay_id = b.barangay_id' . $whereClause . ' 
                         GROUP BY b.barangay_name ORDER BY COUNT(*) DESC LIMIT 1';

                $stmt3 = $this->conn->prepare($sql3);
                if (!$stmt3 || !$stmt3->execute($params)) {
                    error_log("Barangay query failed");
                    $barangayResult = null;
                } else {
                    $barangayResult = $stmt3->fetch(PDO::FETCH_ASSOC);
                }

                $output = [
                    'total_incidents' => (int)($result['total_incidents'] ?? 0),
                    'incidents_today' => (int)($result['incidents_today'] ?? 0),
                    'most_common_crime' => $crimeResult['title'] ?? 'N/A',
                    'most_affected_barangay' => $barangayResult['barangay_name'] ?? 'N/A'
                ];

                error_log("Stats result: " . json_encode($output));
                return $output;
            } catch (Exception $e) {
                error_log("Exception in getDashboardStats: " . $e->getMessage());
                error_log("Exception trace: " . $e->getTraceAsString());
                return [
                    'total_incidents' => 0,
                    'incidents_today' => 0,
                    'most_common_crime' => 'N/A',
                    'most_affected_barangay' => 'N/A'
                ];
            }
        }

        public function logout() {
            session_destroy();
            echo "<script>
                alert('Logged Out');
                window.location.href='login.php';
            </script>";
        }
    }
?>