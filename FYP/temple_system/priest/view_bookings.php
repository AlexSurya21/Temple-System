<?php
session_start();
require_once "../includes/db_connect.php";
if (!isset($_SESSION['priest_id'])) {
    header("Location: priest_login.php");
    exit;
}
 
$bookings = [];

// Updated to match your actual database structure
// Show only bookings for the logged-in priest
$sql = "SELECT b.booking_id, b.booking_date, b.booking_time, b.booking_status, 
               b.booking_type, b.duration_hours, b.number_of_guests, 
               b.special_requirements, u.full_name, u.phone_number
        FROM booking b
        JOIN user u ON b.user_id = u.user_id
        WHERE b.priest_id = ?
        ORDER BY b.booking_date DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $_SESSION['priest_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Bookings</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #fbe9e7, #ffcdd2);
            margin: 0;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            padding: 30px;
        }
        h1 {
            color: #b71c1c;
            margin-bottom: 20px;
        }
        .booking-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        .booking-card h3 {
            margin: 0;
            color: #b71c1c;
        }
        .booking-card p {
            margin: 5px 0;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3e0; color: #e65100; }
        .status-confirmed { background: #e8f5e9; color: #2e7d32; }
        .status-completed { background: #e3f2fd; color: #1565c0; }
        .status-cancelled { background: #ffebee; color: #c62828; }
        .footer-links {
            margin-top: 30px;
            text-align: center;
        }
        .footer-links a {
            color: #b71c1c;
            text-decoration: underline;
            margin: 0 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>My Bookings</h1>
    <?php if (empty($bookings)): ?>
        <p>No bookings assigned to you yet.</p>
    <?php else: ?>
        <?php foreach ($bookings as $b): ?>
            <div class="booking-card">
                <h3><?php echo htmlspecialchars($b['full_name']); ?> - <?php echo ucwords(str_replace('_', ' ', $b['booking_type'])); ?></h3>
                <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($b['booking_date'])); ?> | <strong>Time:</strong> <?php echo date('g:i A', strtotime($b['booking_time'])); ?></p>
                <p><strong>Duration:</strong> <?php echo $b['duration_hours']; ?> hours | <strong>Guests:</strong> <?php echo $b['number_of_guests']; ?> people</p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($b['phone_number']); ?></p>
                <?php if (!empty($b['special_requirements'])): ?>
                    <p><strong>Special Requirements:</strong> <?php echo htmlspecialchars($b['special_requirements']); ?></p>
                <?php endif; ?>
                <p><strong>Status:</strong> <span class="status-badge status-<?php echo $b['booking_status']; ?>"><?php echo ucfirst($b['booking_status']); ?></span></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <div class="footer-links">
        <a href="priest_dashboard.php">← Back to Dashboard</a>
        <a href="priest_logout.php">Logout</a>
    </div>
</div>
</body>
</html>