<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['priest_id'])) {
    header("Location: priest_login.php");
    exit;
}
 
$bookings = [];

$sql = "SELECT b.booking_date, b.booking_time, b.status, d.full_name, p.pooja_name
        FROM booking b
        JOIN devotee d ON b.devotee_id = d.devotee_id
        JOIN pooja p ON b.pooja_id = p.pooja_id
        ORDER BY b.booking_date DESC";

if ($stmt = $conn->prepare($sql)) {
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
    <h1>All Bookings</h1>
    <?php if (empty($bookings)): ?>
        <p>No bookings found.</p>
    <?php else: ?>
        <?php foreach ($bookings as $b): ?>
            <div class="booking-card">
                <h3><?php echo htmlspecialchars($b['full_name']); ?> booked <?php echo htmlspecialchars($b['pooja_name']); ?></h3>
                <p>Date: <?php echo htmlspecialchars($b['booking_date']); ?> | Time: <?php echo htmlspecialchars($b['booking_time']); ?></p>
                <p>Status: <strong><?php echo ucfirst($b['status']); ?></strong></p>
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
