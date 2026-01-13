<?php
session_start();
require_once "../includes/db_connect.php";
if (!isset($_SESSION['priest_id'])) {
    header("Location: priest_login.php");
    exit;
}

$bookings = [];

// Fixed: Updated to use your actual database structure (user table and booking_type)
$sql = "SELECT b.booking_id, b.booking_date, b.booking_time, b.booking_status, 
               b.booking_type, b.duration_hours, b.number_of_guests, 
               b.special_requirements, u.full_name, u.phone_number
        FROM booking b
        JOIN user u ON b.user_id = u.user_id
        WHERE b.priest_id = ? AND b.booking_status != 'cancelled'
        ORDER BY b.booking_date ASC, b.booking_time ASC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $_SESSION['priest_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
}

// Group bookings by date for better organization
$bookings_by_date = [];
foreach ($bookings as $booking) {
    $date = $booking['booking_date'];
    if (!isset($bookings_by_date[$date])) {
        $bookings_by_date[$date] = [];
    }
    $bookings_by_date[$date][] = $booking;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pooja Schedule</title>
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
        .date-section {
            margin-bottom: 30px;
        }
        .date-header {
            background: #b71c1c;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .booking-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            border-left: 4px solid #b71c1c;
        }
        .booking-card h3 {
            margin: 0 0 10px 0;
            color: #b71c1c;
        }
        .booking-card p {
            margin: 5px 0;
            font-size: 14px;
        }
        .time-badge {
            display: inline-block;
            background: #fff3e0;
            color: #e65100;
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 13px;
        }
        .type-badge {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #fff3e0; color: #e65100; }
        .status-confirmed { background: #e8f5e9; color: #2e7d32; }
        .status-completed { background: #e3f2fd; color: #1565c0; }
        .footer-links {
            margin-top: 30px;
            text-align: center;
        }
        .footer-links a {
            color: #b71c1c;
            text-decoration: underline;
            margin: 0 10px;
        }
        .no-bookings {
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>📅 My Pooja Schedule</h1>
    
    <?php if (empty($bookings)): ?>
        <div class="no-bookings">
            <h3>No upcoming poojas scheduled</h3>
            <p>You don't have any bookings assigned yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($bookings_by_date as $date => $date_bookings): ?>
            <div class="date-section">
                <div class="date-header">
                    📆 <?php echo date('l, F j, Y', strtotime($date)); ?>
                    <span style="float: right; font-weight: normal; font-size: 14px;">
                        <?php echo count($date_bookings); ?> booking<?php echo count($date_bookings) > 1 ? 's' : ''; ?>
                    </span>
                </div>
                
                <?php foreach ($date_bookings as $b): ?>
                    <div class="booking-card">
                        <h3>
                            <span class="time-badge"><?php echo date('g:i A', strtotime($b['booking_time'])); ?></span>
                            <span class="type-badge"><?php echo ucwords(str_replace('_', ' ', $b['booking_type'])); ?></span>
                        </h3>
                        <p><strong>Devotee:</strong> <?php echo htmlspecialchars($b['full_name']); ?> | <strong>Phone:</strong> <?php echo htmlspecialchars($b['phone_number']); ?></p>
                        <p><strong>Duration:</strong> <?php echo $b['duration_hours']; ?> hour<?php echo $b['duration_hours'] > 1 ? 's' : ''; ?> | <strong>Guests:</strong> <?php echo $b['number_of_guests']; ?> people</p>
                        <?php if (!empty($b['special_requirements'])): ?>
                            <p><strong>Special Requirements:</strong> <?php echo htmlspecialchars($b['special_requirements']); ?></p>
                        <?php endif; ?>
                        <p><strong>Status:</strong> <span class="status-badge status-<?php echo $b['booking_status']; ?>"><?php echo ucfirst($b['booking_status']); ?></span></p>
                    </div>
                <?php endforeach; ?>
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