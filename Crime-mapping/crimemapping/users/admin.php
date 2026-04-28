<?php
    include '../conn.php';
    include '../oop.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $oop = new Crime_Mapping(new Connection());

    if (!isset($_SESSION['Username'])) {
        echo"<script>
            alert('Not accessible by the public');
            window.location.href='login.php';
        </script>";
        exit;
    }

    if (isset($_POST['logout'])) {
        $oop->logout();
    }
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Document</title>
</head>
<body>
    <form action="admin.php" method="post">
        <button name='logout'>logout</button>
    </form>
</body>
</html>