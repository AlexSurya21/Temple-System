<?php
// devotee/booking_process.php
session_start();
require_once "../includes/db_connect.php";

// ✅ must login
if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? "Devotee";

// ✅ allow only POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: booking.php");
    exit;
}

// ✅ get form data safely
$booking_type = trim($_POST["booking_type"] ?? "");
$booking_date = trim($_POST["booking_date"] ?? "");
$booking_time = trim($_POST["booking_time"] ?? "");
$guests       = (int)($_POST["number_of_guests"] ?? 1);
$note         = trim($_POST["special_requirements"] ?? ""); // ✅ correct field name
$duration     = (float)($_POST["duration_hours"] ?? 1);     // optional

// ✅ simple validation
if ($booking_type === "" || $booking_date === "" || $booking_time === "") {
    die("Error: Please fill booking type, date and time.");
}

if ($guests <= 0) $guests = 1;
if ($duration <= 0) $duration = 1;

// ✅ default status
$status = "pending";

// ✅ IMPORTANT: booking table column name is special_requirements (NOT special_requirement)
$sql = "
    INSERT INTO booking
        (user_id, booking_type, booking_date, booking_time, duration_hours, number_of_guests, special_requirements, booking_status)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "isssdiis",
    $user_id,
    $booking_type,
    $booking_date,
    $booking_time,
    $duration,
    $guests,
    $note,
    $status
);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: my_bookings.php?success=1");
    exit;
} else {
    die("Insert failed: " . $stmt->error);
}
