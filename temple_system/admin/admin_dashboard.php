<?php
/**
 * ============================================================
 * Sri Balathandayuthapani Temple System
 * 
 * Created by: Avenesh A/L Kumaran (1221106783)
 * Last Modified: January 2025
 * ============================================================
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) 
{
    header("Location: admin_login.php");
    exit();
}

// Check session timeout (30 minutes = 1800 seconds)
$timeout_duration = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) 
{
    session_unset();
    session_destroy();
    header("Location: admin_login.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Include database connection
require_once('../includes/db_connect.php');

// Check database connection
if (!$conn) 
{
    die("Database connection failed: " . mysqli_connect_error());
}

// Get admin details
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'staff';

// Initialize statistics
$stats = [
    'bookings' => 0,
    'donations' => 0,
    'donation_amount' => 0,
    'events' => 0,
    'payments' => 0,
    'payment_amount' => 0
];

// Initialize query results
$recent_bookings = null;
$recent_donations = null;
$upcoming_events = null;

// Fetch statistics - FIXED TO MATCH YOUR DATABASE
try {
    // Total Bookings - YOUR TABLE IS 'booking'
    $result = $conn->query("SELECT COUNT(*) as total FROM booking");
    if ($result) {
        $stats['bookings'] = $result->fetch_assoc()['total'];
    }

    // Total Donations - YOUR TABLE IS 'donation'
    $result = $conn->query("SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as total_amount FROM donation");
    if ($result) {
        $donation_data = $result->fetch_assoc();
        $stats['donations'] = $donation_data['total'];
        $stats['donation_amount'] = $donation_data['total_amount'];
    }

    // Total Events - YOUR TABLE IS 'event'
    $result = $conn->query("SELECT COUNT(*) as total FROM event");
    if ($result) {
        $stats['events'] = $result->fetch_assoc()['total'];
    }

    // Total Payments
    $result = $conn->query("SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as total_amount FROM payments");
    if ($result) {
        $payment_data = $result->fetch_assoc();
        $stats['payments'] = $payment_data['total'];
        $stats['payment_amount'] = $payment_data['total_amount'];
    }

    // Fetch recent bookings - FIXED QUERY FOR YOUR DATABASE
    // Your table: booking, columns: booking_id, user_id, booking_type, booking_date, booking_status, created_at
    // Your user table: user, column: full_name
    $recent_bookings = $conn->query("SELECT b.*, u.full_name as customer_name 
                                     FROM booking b 
                                     LEFT JOIN user u ON b.user_id = u.user_id 
                                     ORDER BY b.created_at DESC 
                                     LIMIT 5");

    // Fetch recent donations - FIXED QUERY FOR YOUR DATABASE
    // Your table: donation, columns: donation_id, user_id, amount, donation_type, donation_date, is_anonymous
    $recent_donations = $conn->query("SELECT d.*, u.full_name as donor_name 
                                      FROM donation d 
                                      LEFT JOIN user u ON d.user_id = u.user_id 
                                      ORDER BY d.donation_date DESC 
                                      LIMIT 5");

    // Fetch upcoming events - FIXED QUERY FOR YOUR DATABASE
    // Your table: event, columns: event_id, event_name, event_date, event_time
    $upcoming_events = $conn->query("SELECT * FROM event 
                                     WHERE event_date >= CURDATE() 
                                     ORDER BY event_date ASC 
                                     LIMIT 5");

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sri Balathandayuthapani Temple</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #764ba2;
            font-size: 24px;
        }

        .header .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header .user-info span {
            color: #333;
            font-weight: 500;
        }

        .header .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }

        .header .logout-btn:hover {
            background: #c0392b;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .welcome-section h2 {
            color: #764ba2;
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: #666;
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 5px;
        }

        .stat-card .subtitle {
            color: #999;
            font-size: 14px;
        }

        .quick-actions {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .quick-actions h3 {
            color: #764ba2;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            font-weight: 500;
            display: block;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .data-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .data-section h3 {
            color: #764ba2;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
            font-size: 14px;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status.confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status.completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .no-data {
            text-align: center;
            color: #999;
            padding: 30px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🕉️ Sri Balathandayuthapani Temple - Admin Panel</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($admin_name); ?> (<?php echo ucfirst($admin_role); ?>)</span>
            <a href="admin_logout.php" class="logout-btn" onclick="return confirmLogout();">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h2>Dashboard Overview</h2>
            <p>Manage temple operations, bookings, donations, and events from this central hub.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="number"><?php echo $stats['bookings']; ?></div>
                <div class="subtitle">Wedding & Priest Bookings</div>
            </div>

            <div class="stat-card">
                <h3>Total Donations</h3>
                <div class="number"><?php echo $stats['donations']; ?></div>
                <div class="subtitle">RM <?php echo number_format($stats['donation_amount'], 2); ?> collected</div>
            </div>

            <div class="stat-card">
                <h3>Total Events</h3>
                <div class="number"><?php echo $stats['events']; ?></div>
                <div class="subtitle">Festivals & Activities</div>
            </div>

            <div class="stat-card">
                <h3>Total Payments</h3>
                <div class="number"><?php echo $stats['payments']; ?></div>
                <div class="subtitle">RM <?php echo number_format($stats['payment_amount'], 2); ?> processed</div>
            </div>
        </div>

        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-grid">
                <a href="manage_bookings.php" class="action-btn">📅 Manage Bookings</a>
                <a href="manage_donations.php" class="action-btn">💰 Manage Donations</a>
                <a href="manage_events.php" class="action-btn">🎉 Manage Events</a>
                <a href="manage_classes.php" class="action-btn">📚 Manage Classes</a>
                <a href="view_payments.php" class="action-btn">💳 View Payments</a>
            </div>
        </div>

        <div class="content-grid">
            <div class="data-section">
                <h3>Recent Bookings</h3>
                <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['customer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($booking['booking_type'] ?? 'N/A'))); ?></td>
                                <td><?php echo isset($booking['booking_date']) ? date('d M Y', strtotime($booking['booking_date'])) : 'N/A'; ?></td>
                                <td><span class="status <?php echo strtolower($booking['booking_status'] ?? 'pending'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($booking['booking_status'] ?? 'Pending')); ?>
                                </span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">No bookings available yet. Bookings will appear here once users make reservations.</div>
                <?php endif; ?>
            </div>

            <div class="data-section">
                <h3>Recent Donations</h3>
                <?php if ($recent_donations && $recent_donations->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Donor</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($donation = $recent_donations->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo ($donation['is_anonymous'] ?? 0) ? 'Anonymous' : htmlspecialchars($donation['donor_name'] ?? 'N/A'); ?></td>
                                <td>RM <?php echo number_format($donation['amount'] ?? 0, 2); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($donation['donation_type'] ?? 'General')); ?></td>
                                <td><?php echo isset($donation['donation_date']) ? date('d M Y', strtotime($donation['donation_date'])) : 'N/A'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">No donations available yet. Donations will appear here once devotees contribute.</div>
                <?php endif; ?>
            </div>

            <div class="data-section">
                <h3>Upcoming Events</h3>
                <?php if ($upcoming_events && $upcoming_events->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($event = $upcoming_events->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['event_name'] ?? 'N/A'); ?></td>
                                <td><?php echo isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : 'N/A'; ?></td>
                                <td><?php echo isset($event['event_time']) ? date('h:i A', strtotime($event['event_time'])) : 'N/A'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        No upcoming events scheduled. All events may be in the past or none have been added yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function confirmLogout() {
            return confirm('Are you sure you want to logout from the Admin Panel?');
        }

        console.log('🕉️ Sri Balathandayuthapani Temple System');
        console.log('✅ Dashboard loaded successfully');
    </script>
</body>
</html>

<?php
// Close database connection
if ($conn) {
    $conn->close();
}

?>
