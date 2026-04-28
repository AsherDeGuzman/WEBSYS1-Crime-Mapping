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
                header('Location: login.php?err=Invalid%20Credentials');
                return;
            }

            foreach ($rows as $row) {
                if ($username === $row['username'] && $password === $row['password'] && $row['role'] === 'admin') {
                    $_SESSION['Username'] = $row['username'];
                    $_SESSION['Password'] = $row['password'];
                    header("Location: ../users/admin.php");
                    exit;
                } elseif ($username === $row['username'] && $password === $row['password'] && $row['role'] === 'barangay') {
                    $_SESSION['Username'] = $row['username'];
                    $_SESSION['Password'] = $row['password'];
                    header("Location: ../users/barangay.php");
                    exit;
                }
            }
            header("Location: login.php?err=An%20Error%20Occured");
            exit;
        }

        public function getBarangays() {
            $stmt = $this->conn->prepare('SELECT barangay_name FROM barangays ORDER BY barangay_name');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        public function getCrimeTypes() {
            $stmt = $this->conn->prepare('SELECT name FROM crime_types ORDER BY name');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        public function getStatuses() {
            $stmt = $this->conn->prepare("SELECT DISTINCT status FROM incidents ORDER BY FIELD(status, 'responding', 'pending', 'resolved'), status");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        private function buildIncidentFilters($barangay, $type, $status, $from, $to, array &$params) {
            $clauses = [];

            if ($type && $type !== 'All Crime Types') {
                $clauses[] = 'ct.name = :type';
                $params[':type'] = $type;
            }

            if ($type && $type !== 'All Crime Types') {
                $clauses[] = 'i.title LIKE :type';
                $params[':type'] = "%{$type}%";
            }

            if ($status && $status !== 'All Status') {
                $statusMap = [
                    'Verified' => 'responding',
                    'Pending' => 'pending',
                    'Resolved' => 'resolved'
                ];
                $statusValue = $statusMap[$status] ?? strtolower($status);
                $clauses[] = 'i.status = :status';
                $params[':status'] = $statusValue;
            }

            if ($from) {
                $clauses[] = 'i.created_at >= :from';
                $params[':from'] = $from . ' 00:00:00';
            }

            if ($to) {
                $clauses[] = 'i.created_at <= :to';
                $params[':to'] = $to . ' 23:59:59';
            }

            return $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
        }

        public function getIncidents($barangay = null, $type = null, $status = null, $from = null, $to = null) {
            $params = [];
            $filter = $this->buildIncidentFilters($barangay, $type, $status, $from, $to, $params);

            $sql = 'SELECT i.incident_id, i.title, i.description, i.latitude, i.longitude, i.status, i.created_at, b.barangay_name
                    FROM incidents i
                    JOIN barangays b ON i.barangay_id = b.barangay_id' . $filter . ' ORDER BY i.created_at DESC LIMIT 200';

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        public function getDashboardStats($barangay = null, $type = null, $status = null, $from = null, $to = null) {
            $params = [];
            $filter = $this->buildIncidentFilters($barangay, $type, $status, $from, $to, $params);

            $countSql = 'SELECT
                    COUNT(*) AS total_incidents,
                    SUM(CASE WHEN DATE(i.created_at) = CURDATE() THEN 1 ELSE 0 END) AS incidents_today
                FROM incidents i
                JOIN barangays b ON i.barangay_id = b.barangay_id' . $filter;

            $stmt = $this->conn->prepare($countSql);
            $stmt->execute($params);
            $counts = $stmt->fetch();

            $crimeSql = 'SELECT i.title, COUNT(*) AS cnt
                FROM incidents i
                JOIN barangays b ON i.barangay_id = b.barangay_id' . $filter . ' GROUP BY i.title ORDER BY cnt DESC LIMIT 1';

            $stmt = $this->conn->prepare($crimeSql);
            $stmt->execute($params);
            $crime = $stmt->fetch();

            $barangaySql = 'SELECT b.barangay_name, COUNT(*) AS cnt
                FROM incidents i
                JOIN barangays b ON i.barangay_id = b.barangay_id' . $filter . ' GROUP BY b.barangay_name ORDER BY cnt DESC LIMIT 1';

            $stmt = $this->conn->prepare($barangaySql);
            $stmt->execute($params);
            $mostBarangay = $stmt->fetch();

            return [
                'total_incidents' => (int)($counts['total_incidents'] ?? 0),
                'incidents_today' => (int)($counts['incidents_today'] ?? 0),
                'most_common_crime' => $crime['title'] ?? 'N/A',
                'most_affected_barangay' => $mostBarangay['barangay_name'] ?? 'N/A'
            ];
        }

        public function logout() {
            // session_destroy();
            exit;
        }
    }
?>