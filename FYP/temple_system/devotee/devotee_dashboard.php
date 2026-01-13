<?php
// devotee/devotee_dashboard.php
session_start();
require_once "../includes/db_connect.php";

// 🔒 If not logged in, send back to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");
    exit;
}

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? "Devotee";

// =============================
//  SIMPLE DASHBOARD STATISTICS
//  (Safe even if your columns differ)
// =============================

$upcomingBookings = 0;
$classEnrollments = 0;
$totalDonations   = 0.00;

// Try to count upcoming bookings (you can adjust column names later)
if ($conn) {
    if ($stmt = @$conn->prepare("SELECT COUNT(*) AS c FROM booking WHERE user_id = ?")) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $upcomingBookings = (int)($res['c'] ?? 0);
        $stmt->close();
    }

    // Count class enrollments
    if ($stmt = @$conn->prepare("SELECT COUNT(*) AS c FROM class_registration WHERE user_id = ?")) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $classEnrollments = (int)($res['c'] ?? 0);
        $stmt->close();
    }

    // Sum donations (assumes donation.amount and donation.user_id exist)
    if ($stmt = @$conn->prepare("SELECT SUM(amount) AS s FROM donation WHERE user_id = ?")) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $totalDonations = (float)($res['s'] ?? 0);
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Devotee Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #ff8a4a, #ff5f7c);
            --card-bg: #ffffff;
            --purple: #7d2cff;
            --purple-soft: #f3ecff;
            --pink: #ff4b8f;
            --text-main: #2a2344;
            --muted: #7c7a90;
            --border-soft: #eee6ff;
            --success: #37c978;
            --warning: #ffaf3d;
            --danger: #ff5c5c;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Poppins", sans-serif;
        }

        body {
            min-height: 100vh;
            background: var(--bg-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        .dashboard-shell {
            max-width: 1100px;
            width: 100%;
            background: var(--card-bg);
            border-radius: 28px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
            padding: 28px 32px 24px;
        }

        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .logo-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .om-badge {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 20%, #ffe6ff, #b833ff);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 30px;
        }

        .title-block h1 {
            font-size: 24px;
            color: var(--text-main);
            font-weight: 600;
        }

        .title-block p {
            font-size: 13px;
            color: var(--muted);
        }

        .welcome-pill {
            background: var(--purple-soft);
            border-radius: 999px;
            padding: 10px 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-main);
        }

        .welcome-pill span.avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #ffe6cc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .welcome-pill strong {
            font-weight: 600;
            color: var(--purple);
        }

        /* STAT CARDS */
        .stats-row {
            display: grid;
            grid-template-columns: 2fr 2fr 2fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #faf7ff;
            border-radius: 18px;
            padding: 16px 18px;
            border: 1px solid var(--border-soft);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--muted);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--purple);
        }

        .stat-sub {
            font-size: 11px;
            color: var(--muted);
        }

        /* MAIN GRID */
        .main-grid {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 18px;
        }

        .panel {
            background: #faf7ff;
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            padding: 16px 18px 14px;
        }

        .panel h2 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 10px;
        }

        .panel small {
            font-size: 11px;
            color: var(--muted);
        }

        /* QUICK ACTIONS */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .qa-btn {
            border-radius: 999px;
            padding: 9px 14px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            font-size: 13px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
            box-shadow: 0 8px 20px rgba(125, 44, 255, 0.09);
            background: #fff;
            color: var(--purple);
        }

        .qa-btn.primary {
            background: linear-gradient(135deg, #7d2cff, #ff4b8f);
            color: #fff;
        }

        .qa-btn span.icon {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .qa-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
        }

        /* AT A GLANCE LIST */
        .glance-item {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px dashed #e7ddff;
            font-size: 12px;
        }

        .glance-item:last-child {
            border-bottom: none;
        }

        .glance-title {
            font-weight: 500;
            color: var(--text-main);
        }

        .glance-sub {
            font-size: 11px;
            color: var(--muted);
        }

        .badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 999px;
            font-weight: 500;
        }

        .badge.success {
            background: #e8fff3;
            color: var(--success);
        }

        .badge.warning {
            background: #fff5e5;
            color: var(--warning);
        }

        .badge.info {
            background: #eef5ff;
            color: #4d7bff;
        }

        .badge.muted {
            background: #f1edf7;
            color: var(--muted);
        }

        .panel-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .panel-header-row small {
            font-size: 11px;
            color: var(--muted);
        }

        /* FOOTER */
        .footer-row {
            margin-top: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: var(--muted);
        }

        .footer-links {
            display: flex;
            gap: 8px;
        }

        .link-btn {
            border: none;
            background: none;
            color: var(--purple);
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
        }

        .link-btn:hover {
            text-decoration: underline;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .stats-row {
                grid-template-columns: 1fr;
            }

            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .dashboard-shell {
                padding: 20px 18px 18px;
                border-radius: 22px;
            }

            .top-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .welcome-pill {
                align-self: stretch;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-shell">

    <!-- TOP BAR -->
    <div class="top-row">
        <div class="logo-group">
            <div class="om-badge">ॐ</div>
            <div class="title-block">
                <h1>Devotee Dashboard</h1>
                <p>Manage your bookings, donations, and cultural class registrations.</p>
            </div>
        </div>

        <div class="welcome-pill">
            <span class="avatar">😊</span>
            <span>Welcome,&nbsp;<strong><?php echo htmlspecialchars($userName); ?></strong></span>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Upcoming Bookings</div>
            <div class="stat-value"><?php echo $upcomingBookings; ?></div>
            <div class="stat-sub">Next: check your events list.</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Class Enrollments</div>
            <div class="stat-value"><?php echo $classEnrollments; ?></div>
            <div class="stat-sub">Bharatanatyam, Vocal, Hindu Studies & more.</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Total Donations</div>
            <div class="stat-value">RM <?php echo number_format($totalDonations, 2); ?></div>
            <div class="stat-sub">Thank you for your contribution 🙏</div>
        </div>
    </div>

    <!-- MAIN GRID -->
    <div class="main-grid">
        <!-- LEFT: QUICK ACTIONS -->
        <div class="panel">
            <div class="panel-header-row">
                <h2>Quick Actions</h2>
                <small>What would you like to do today?</small>
            </div>

            <div class="quick-actions">
                <!-- View Cultural Classes -->
                <a class="qa-btn primary" href="view_classes.php">
                    <span class="icon">🎭</span>
                    <span>View Classes</span>
                </a>
				
				<!-- My Classes -->
                <a class="qa-btn" href="my_classes.php">
                    <span class="icon">📚</span>
                    <span>My Classes</span>
                    </a>

                <!-- View Temple Events (you can change link later) -->
                <a class="qa-btn" href="event_list.php">
                    <span class="icon">📅</span>
                    <span>View Temple Events</span>
                </a>


                <!--calendar -->
                <a class="qa-btn" href="calendar.php">
                    <span class="icon">🗓️</span>
                    <span>Calendar</span>
                </a>

               <!-- Make Donation -->
               <a class="qa-btn primary" href="donation.php">
                  <span class="icon">💰</span>
                  <span>Make Donation</span>
               </a>

               <!-- My Donations -->
               <a class="qa-btn" href="my_donations.php">
                  <span class="icon">💗</span>
                  <span>My Donations</span>
               </a>
			   
			   <!-- Make Booking -->
               <a class="qa-btn" href="booking.php">
                  <span class="icon">📝</span>
                  <span>Make Booking</span>
               </a>

              <!-- My Bookings -->
              <a class="qa-btn" href="my_bookings.php">
                 <span class="icon">📌</span>
                 <span>My Bookings</span>
              </a>
            </div>
        </div>

        <!-- RIGHT: AT A GLANCE -->
        <div class="panel">
            <div class="panel-header-row">
                <div>
                    <h2>At a Glance</h2>
                    <small>Your recent activities</small>
                </div>
            </div>

            <!-- You can later make this dynamic from DB -->
            <div class="glance-item">
                <div>
                    <div class="glance-title">Bharatanatyam Class – Sat 4:00 PM</div>
                    <div class="glance-sub">Cultural Hall 1</div>
                </div>
                <span class="badge success">Active</span>
            </div>

            <div class="glance-item">
                <div>
                    <div class="glance-title">Ganapathi Homam – 18 June</div>
                    <div class="glance-sub">Main Temple</div>
                </div>
                <span class="badge.warning badge warning">Upcoming</span>
            </div>

            <div class="glance-item">
                <div>
                    <div class="glance-title">Temple Maintenance Donation</div>
                    <div class="glance-sub">RM 100 this month</div>
                </div>
                <span class="badge info">Donation</span>
            </div>

            <div class="glance-item">
                <div>
                    <div class="glance-title">Vocal Music Class – Thu 7:30 PM</div>
                    <div class="glance-sub">Cultural Centre</div>
                </div>
                <span class="badge success">Active</span>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer-row">
        <span>“Serving with devotion brings peace to the heart.”</span>

        <div class="footer-links">
            <a class="link-btn" href="logout.php">Logout</a>
        </div>
    </div>

</div>
</body>
</html>

