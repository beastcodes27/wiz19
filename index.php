<?php
session_start();

// Check if user is logged in (using common session variables)
if (isset($_SESSION['user_id']) || isset($_SESSION['logged_in'])) {
    // User is logged in, send to dashboard
    header("Location: dashboard");
    exit;
} else {
    // User is not logged in, send to login page
    header("Location: login");
    exit;
}
?>
