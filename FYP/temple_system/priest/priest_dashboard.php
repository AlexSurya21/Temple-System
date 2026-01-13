<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['priest_id'])) {
    header("Location: priest_login.php");
    exit;
}

$priestName = $_SESSION['priest_name'] ?? "Priest";

$totalBookings = 0;
$pendingBookings = 0;
$recentBookings = [];

if ($conn) {
    if ($stmt = @$conn->prepare("SELECT COUNT(*) AS c FROM booking")) {
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $totalBookings = (int)($res['c'] ?? 0);
        $stmt->close();
    }

    // FIXED: Changed 'status' to 'booking_status'
    if ($stmt = @$conn->prepare("SELECT COUNT(*) AS c FROM booking WHERE booking_status = 'pending'")) {
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $pendingBookings = (int)($res['c'] ?? 0);
        $stmt->close();
    }

    // FIXED: Changed to match YOUR database structure (user table instead of devotee, and booking_type instead of pooja)
    $sql = "SELECT b.booking_date, b.booking_time, b.booking_status, b.booking_type, u.full_name
            FROM booking b
            JOIN user u ON b.user_id = u.user_id
            ORDER BY b.created_at DESC
            LIMIT 5";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recentBookings[] = $row;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Priest Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #fbe9e7, #ffcdd2);
            margin: 0; color: #333;
        }
        .dashboard-container { max-width: 1200px; margin: auto; padding: 30px; }
        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            color: #b71c1c; margin-bottom: 30px;
        }
        .top-bar h1 { margin: 0; font-size: 28px; }
        .welcome { background: #fff3f3; padding: 10px 20px; border-radius: 30px; font-weight: 500; }
        .cards { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .card {
            flex: 1; min-width: 220px; background: white; padding: 20px;
            border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center;
        }
        .card h2 { margin: 0; font-size: 18px; color: #b71c1c; }
        .card p { font-size: 14px; color: #666; }
        .quick-actions { display: flex; flex-wrap: wrap; gap: 15px; }
        .qa-btn {
            flex: 1 1 45%; min-width: 180px; background: #fff;
            padding: 15px; border-radius: 8px; text-align: center;
            text-decoration: none; color: #b71c1c; font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1); transition: 0.3s;
        }
        .qa-btn:hover { background: #ffe5e5; }
        h2 { color: #333; font-size: 20px; margin-bottom: 10px; }
        .footer { margin-top: 40px; text-align: center; color: #b71c1c; }
        .footer a { color: #b71c1c; text-decoration: underline; margin: 0 10px; }
        .recent { margin-top: 30px; }
        .recent-item {
            background: white; padding: 15px; border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin-bottom: 10px;
        }
        .recent-item strong { color: #b71c1c; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="top-bar">
        <h1>Priest Dashboard</h1>
        <div class="welcome">🙏 Welcome, <?php echo htmlspecialchars($priestName); ?></div>
    </div>

    <div class="cards">
        <div class="card"><h2>Total Bookings</h2><div><?php echo $totalBookings; ?></div><p>All temple bookings received.</p></div>
        <div class="card"><h2>Pending Bookings</h2><div><?php echo $pendingBookings; ?></div><p>Awaiting approval.</p></div>
    </div>

    <h2>Quick Actions</h2>
    <div class="quick-actions">
        <a class="qa-btn" href="view_bookings.php">📝 View All Bookings</a>
        <a class="qa-btn" href="pooja_schedule.php">📅 Pooja Schedule</a>
    </div>

    <div class="recent">
        <h2>Recent Notifications</h2>
        <?php if (empty($recentBookings)): ?>
            <p>No recent bookings found.</p>
        <?php else: ?>
            <?php foreach ($recentBookings as $b): ?>
                <div class="recent-item">
                    <strong><?php echo htmlspecialchars($b['full_name']); ?></strong> booked <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $b['booking_type']))); ?>
                    <br>Date: <?php echo htmlspecialchars($b['booking_date']); ?> | Time: <?php echo htmlspecialchars($b['booking_time']); ?>
                    <br>Status: <?php echo ucfirst($b['booking_status']); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>"Guiding devotees with wisdom and devotion."</p>
        <a href="../index.php">← Back to Home</a>
        <a href="priest_logout.php">Logout</a>
    </div>
</div>
</body>


</html>
