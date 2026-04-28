<?php
session_start();

function requireRole(array $roles): void
{
    $role = $_SESSION['role'] ?? null;
    if (!$role || !in_array($role, $roles, true)) {
        header('Location: login.php');
        exit;
    }
}
