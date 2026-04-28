<?php
    include '../conn.php';
    include '../oop.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $oop = new Crime_Mapping(new Connection());

    if(isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $oop->login($username, $password);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            border: none;
            border-radius: 1.25rem;
            overflow: hidden;
        }
        .login-card .card-body {
            padding: 2rem;
        }
        .social-btn {
            border-radius: 999px;
            font-weight: 600;
            padding: 0.9rem 1.25rem;
            text-transform: none;
        }
        .divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
            color: #6b7280;
        }
        .divider::before,
        .divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #d1d5db;
        }
        .divider::before {
            left: 0;
        }
        .divider::after {
            right: 0;
        }
        .divider span {
            position: relative;
            background: #ffffff;
            padding: 0 0.75rem;
        }
        a.text-muted:hover {
            color: #111827 !important;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card login-card shadow-lg">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h1 class="h3 fw-bold mb-1">Crime Map</h1>
                </div>

                <form action="login.php" method="post">
                    <div class="mb-3">
                        <input type="text" class="form-control form-control-lg" id="username" name="username" placeholder="Username" required>
                    </div>
                    <div class="mb-4">
                        <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" name="login" class="btn btn-dark btn-lg">Sign In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>




