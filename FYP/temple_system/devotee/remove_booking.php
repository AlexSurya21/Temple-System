<?php
// devotee/remove_booking.php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");  
    exit;
}

$userId = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: my_bookings.php");
    exit;
}

if (!$conn) {
    die("DB connection failed.");
}

// Delete only if the booking belongs to this user
$stmt = $conn->prepare("DELETE FROM booking WHERE booking_id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$stmt->close();

header("Location: my_bookings.php");
exit;

