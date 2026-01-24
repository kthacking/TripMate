<?php
// auth.php - Helper functions for authentication and roles

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function checkAdmin() {
    checkLogin();
    if ($_SESSION['role'] !== 'admin') {
        // Redirect to dashboard if not authorized
        header("Location: dashboard.php");
        exit();
    }
}

function checkTripMaker() {
    checkLogin();
    if ($_SESSION['role'] !== 'tripmaker' && $_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}
?>
