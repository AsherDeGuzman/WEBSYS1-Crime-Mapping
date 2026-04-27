<?php
   if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['Username']) || !isset($_SESSION['UserRole'])) {
        header('Location: public/map.php');
        exit;
    }

    if ($_SESSION['UserRole'] === 'admin') {
        header('Location: users/admin.php');
        exit;
    }

    if ($_SESSION['UserRole'] === 'barangay') {
        header('Location: users/barangay_dashboard.php');
        exit;
    }

    header('Location: public/map.php');
    exit;

?>