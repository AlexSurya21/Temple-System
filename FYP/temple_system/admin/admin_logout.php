<?php
/**
 * ============================================================
 * Sri Balathandayuthapani Temple System
 * Admin Logout Script with Security Features
 * 
 * Created by: Avenesh A/L Kumaran (1221106783)
 * Last Modified: December 2025
 * ============================================================
 */

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

session_start();

// Destroy all session data
session_unset();
session_destroy();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page with success message
header("Location: admin_login.php?logout=success");
exit();
?>
