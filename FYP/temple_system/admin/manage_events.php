<?php
/**
 * ============================================================
 * Sri Balathandayuthapani Temple System
 * Manage Events Page
 * 
 * Created by: Avenesh A/L Kumaran (1221106783)
 * Last Modified: December 2025
 * ============================================================
 */

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check session timeout
$timeout_duration = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php?timeout=1");
    exit();
}

$_SESSION['last_activity'] = time();

require_once('../includes/db_connect.php');

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$success_message = '';
$error_message = '';

// Handle Delete Event
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $event_id = (int)$_GET['id'];
    
    // Delete the event
    $stmt = $conn->prepare("DELETE FROM Event WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    
    if ($stmt->execute()) {
        $success_message = "Event deleted successfully!";
    } else {
        $error_message = "Failed to delete event.";
    }
    $stmt->close();
}

// Handle Add Event
if (isset($_POST['add_event'])) {
    $event_name = trim($_POST['event_name']);
    $event_description = trim($_POST['event_description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $venue = trim($_POST['venue']);
    $max_participants = (int)$_POST['max_participants'];
    $event_type = $_POST['event_type'];
    $event_status = $_POST['event_status'];
    $current_participants = 0;
    
    // Validation flag
    $validation_passed = true;
    
    // Check for duplicate event name (case-insensitive)
    $check_name_stmt = $conn->prepare("SELECT event_id FROM Event WHERE LOWER(event_name) = LOWER(?)");
    $check_name_stmt->bind_param("s", $event_name);
    $check_name_stmt->execute();
    $check_name_result = $check_name_stmt->get_result();
    
    if ($check_name_result->num_rows > 0) {
        $error_message = "An event with this name already exists (case-insensitive). Please use a different name.";
        $validation_passed = false;
    }
    $check_name_stmt->close();
    
    // Check for date/time clash at the same venue
    if ($validation_passed) {
        $check_clash_stmt = $conn->prepare("SELECT event_id, event_name FROM Event WHERE event_date = ? AND event_time = ? AND venue = ?");
        $check_clash_stmt->bind_param("sss", $event_date, $event_time, $venue);
        $check_clash_stmt->execute();
        $check_clash_result = $check_clash_stmt->get_result();
        
        if ($check_clash_result->num_rows > 0) {
            $clash_event = $check_clash_result->fetch_assoc();
            $error_message = "Time clash detected! The venue '" . htmlspecialchars($venue) . "' is already booked for '" . htmlspecialchars($clash_event['event_name']) . "' on " . date('d M Y', strtotime($event_date)) . " at " . date('h:i A', strtotime($event_time)) . ".";
            $validation_passed = false;
        }
        $check_clash_stmt->close();
    }
    
    // Insert event if validation passed
    if ($validation_passed) {
        $stmt = $conn->prepare("INSERT INTO Event (event_name, event_description, event_date, event_time, venue, max_participants, current_participants, event_type, event_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiiss", $event_name, $event_description, $event_date, $event_time, $venue, $max_participants, $current_participants, $event_type, $event_status);
        
        if ($stmt->execute()) {
            $success_message = "Event added successfully!";
        } else {
            $error_message = "Failed to add event.";
        }
        $stmt->close();
    }
}

// Handle Update Event
if (isset($_POST['update_event'])) {
    $event_id = (int)$_POST['event_id'];
    $event_name = trim($_POST['event_name']);
    $event_description = trim($_POST['event_description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $venue = trim($_POST['venue']);
    $max_participants = (int)$_POST['max_participants'];
    $event_type = $_POST['event_type'];
    $event_status = $_POST['event_status'];
    
    // Validation flag
    $validation_passed = true;
    
    // Check for duplicate event name (case-insensitive), excluding current event
    $check_name_stmt = $conn->prepare("SELECT event_id FROM Event WHERE LOWER(event_name) = LOWER(?) AND event_id != ?");
    $check_name_stmt->bind_param("si", $event_name, $event_id);
    $check_name_stmt->execute();
    $check_name_result = $check_name_stmt->get_result();
    
    if ($check_name_result->num_rows > 0) {
        $error_message = "An event with this name already exists (case-insensitive). Please use a different name.";
        $validation_passed = false;
    }
    $check_name_stmt->close();
    
    // Check for date/time clash at the same venue, excluding current event
    if ($validation_passed) {
        $check_clash_stmt = $conn->prepare("SELECT event_id, event_name FROM Event WHERE event_date = ? AND event_time = ? AND venue = ? AND event_id != ?");
        $check_clash_stmt->bind_param("sssi", $event_date, $event_time, $venue, $event_id);
        $check_clash_stmt->execute();
        $check_clash_result = $check_clash_stmt->get_result();
        
        if ($check_clash_result->num_rows > 0) {
            $clash_event = $check_clash_result->fetch_assoc();
            $error_message = "Time clash detected! The venue '" . htmlspecialchars($venue) . "' is already booked for '" . htmlspecialchars($clash_event['event_name']) . "' on " . date('d M Y', strtotime($event_date)) . " at " . date('h:i A', strtotime($event_time)) . ".";
            $validation_passed = false;
        }
        $check_clash_stmt->close();
    }
    
    // Update event if validation passed
    if ($validation_passed) {
        $stmt = $conn->prepare("UPDATE Event SET event_name = ?, event_description = ?, event_date = ?, event_time = ?, venue = ?, max_participants = ?, event_type = ?, event_status = ? WHERE event_id = ?");
        $stmt->bind_param("sssssissi", $event_name, $event_description, $event_date, $event_time, $venue, $max_participants, $event_type, $event_status, $event_id);
        
        if ($stmt->execute()) {
            $success_message = "Event updated successfully!";
        } else {
            $error_message = "Failed to update event.";
        }
        $stmt->close();
    }
}

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM Event WHERE 1=1";

if ($filter_type != 'all') {
    $query .= " AND event_type = '" . $conn->real_escape_string($filter_type) . "'";
}

if ($filter_status != 'all') {
    $query .= " AND event_status = '" . $conn->real_escape_string($filter_status) . "'";
}

if (!empty($date_from)) {
    $query .= " AND event_date >= '" . $conn->real_escape_string($date_from) . "'";
}

if (!empty($date_to)) {
    $query .= " AND event_date <= '" . $conn->real_escape_string($date_to) . "'";
}

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $query .= " AND (event_name LIKE '%$search_term%' OR venue LIKE '%$search_term%')";
}

$query .= " ORDER BY event_date DESC";

$result = $conn->query($query);

// Get statistics
$stats = [
    'total_events' => 0,
    'upcoming_events' => 0,
    'completed_events' => 0,
    'cancelled_events' => 0,
    'total_participants' => 0,
    'festival_count' => 0,
    'puja_count' => 0,
    'pooja_count' => 0,
    'cultural_count' => 0,
    'other_count' => 0
];

$stats_query = "SELECT 
    COUNT(*) as total_count,
    SUM(CASE WHEN event_status = 'upcoming' THEN 1 ELSE 0 END) as upcoming_count,
    SUM(CASE WHEN event_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN event_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
    SUM(current_participants) as total_participants,
    SUM(CASE WHEN event_type = 'festival' THEN 1 ELSE 0 END) as festival_count,
    SUM(CASE WHEN event_type = 'puja' THEN 1 ELSE 0 END) as puja_count,
    SUM(CASE WHEN event_type = 'pooja' THEN 1 ELSE 0 END) as pooja_count,
    SUM(CASE WHEN event_type = 'cultural' THEN 1 ELSE 0 END) as cultural_count,
    SUM(CASE WHEN event_type = 'other' THEN 1 ELSE 0 END) as other_count
FROM Event";

$stats_result = $conn->query($stats_query);
if ($stats_row = $stats_result->fetch_assoc()) {
    $stats['total_events'] = (int)($stats_row['total_count'] ?? 0);
    $stats['upcoming_events'] = (int)($stats_row['upcoming_count'] ?? 0);
    $stats['completed_events'] = (int)($stats_row['completed_count'] ?? 0);
    $stats['cancelled_events'] = (int)($stats_row['cancelled_count'] ?? 0);
    $stats['total_participants'] = (int)($stats_row['total_participants'] ?? 0);
    $stats['festival_count'] = (int)($stats_row['festival_count'] ?? 0);
    $stats['puja_count'] = (int)($stats_row['puja_count'] ?? 0);
    $stats['pooja_count'] = (int)($stats_row['pooja_count'] ?? 0);
    $stats['cultural_count'] = (int)($stats_row['cultural_count'] ?? 0);
    $stats['other_count'] = (int)($stats_row['other_count'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Admin Panel</title>
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

        .header .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .header a {
            color: #764ba2;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .header a:hover {
            background: #f0f0f0;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header-content h2 {
            color: #764ba2;
            margin-bottom: 5px;
        }

        .page-header-content p {
            color: #666;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #666;
            font-size: 13px;
            margin-bottom: 3px;
        }

        .stat-card .sublabel {
            color: #999;
            font-size: 12px;
        }

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(118, 75, 162, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            font-size: 13px;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
            padding: 8px 15px;
            font-size: 13px;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            font-size: 13px;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .table-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            border-bottom: 2px solid #dee2e6;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
            font-size: 14px;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status-upcoming {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .type-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }

        .type-festival {
            background: #fff3cd;
            color: #856404;
        }

        .type-puja, .type-pooja {
            background: #f8d7da;
            color: #721c24;
        }

        .type-cultural {
            background: #d1ecf1;
            color: #0c5460;
        }

        .type-other {
            background: #e2e3e5;
            color: #383d41;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            margin: 20px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #764ba2;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .participants-info {
            font-weight: bold;
            color: #764ba2;
        }

        .participants-full {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📅 Manage Events</h1>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <div class="page-header-content">
                <h2>Event Management</h2>
                <p>Create and manage temple events and festivals</p>
            </div>
            <button onclick="openAddModal()" class="btn btn-primary">+ Add New Event</button>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Events</div>
                <div class="number"><?php echo $stats['total_events']; ?></div>
                <div class="sublabel">All events</div>
            </div>
            <div class="stat-card">
                <div class="label">Upcoming Events</div>
                <div class="number"><?php echo $stats['upcoming_events']; ?></div>
                <div class="sublabel">Scheduled</div>
            </div>
            <div class="stat-card">
                <div class="label">Completed Events</div>
                <div class="number"><?php echo $stats['completed_events']; ?></div>
                <div class="sublabel">Finished</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Participants</div>
                <div class="number"><?php echo $stats['total_participants']; ?></div>
                <div class="sublabel">Registrations</div>
            </div>
        </div>

        <!-- Event Type Breakdown -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Festival Events</div>
                <div class="number"><?php echo $stats['festival_count']; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Puja/Pooja Events</div>
                <div class="number"><?php echo $stats['puja_count'] + $stats['pooja_count']; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Cultural Events</div>
                <div class="number"><?php echo $stats['cultural_count']; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Other Events</div>
                <div class="number"><?php echo $stats['other_count']; ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Event Type</label>
                        <select name="type">
                            <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="festival" <?php echo $filter_type == 'festival' ? 'selected' : ''; ?>>Festival</option>
                            <option value="puja" <?php echo $filter_type == 'puja' ? 'selected' : ''; ?>>Puja</option>
                            <option value="pooja" <?php echo $filter_type == 'pooja' ? 'selected' : ''; ?>>Pooja</option>
                            <option value="cultural" <?php echo $filter_type == 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                            <option value="other" <?php echo $filter_type == 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="upcoming" <?php echo $filter_status == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>From Date</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>

                    <div class="filter-group">
                        <label>To Date</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>

                    <div class="filter-group">
                        <label>Search Event</label>
                        <input type="text" name="search" placeholder="Search by name or venue" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="manage_events.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- Events Table -->
        <div class="table-section">
            <h3 style="margin-bottom: 20px; color: #764ba2;">All Events</h3>
            
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Event Name</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Type</th>
                            <th>Participants</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $row_number = 1;
                        while ($event = $result->fetch_assoc()): 
                            $current = (int)($event['current_participants'] ?? 0);
                            $max = (int)($event['max_participants'] ?? 0);
                            $is_full = $current >= $max;
                        ?>
                            <tr>
                                <td><?php echo $row_number++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($event['event_name']); ?></strong><br>
                                    <small style="color: #999;"><?php echo htmlspecialchars(substr($event['event_description'] ?? '', 0, 50)); ?>...</small>
                                </td>
                                <td>
                                    <?php echo date('d M Y', strtotime($event['event_date'])); ?><br>
                                    <small><?php echo date('h:i A', strtotime($event['event_time'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($event['venue']); ?></td>
                                <td>
                                    <span class="type-badge type-<?php echo $event['event_type']; ?>">
                                        <?php echo ucfirst($event['event_type']); ?>
                                    </span>
                                </td>
                                <td class="participants-info">
                                    <?php echo $max; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $event['event_status']; ?>">
                                        <?php echo ucfirst($event['event_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick='openEditModal(<?php echo json_encode($event); ?>)' class="btn btn-warning">
                                            Update
                                        </button>
                                        <button onclick="confirmDelete(<?php echo $event['event_id']; ?>)" class="btn btn-danger">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    📅 No events found matching your criteria.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Event</h3>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Event Name *</label>
                    <input type="text" name="event_name" required>
                </div>

                <div class="form-group">
                    <label>Event Description *</label>
                    <textarea name="event_description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Event Date *</label>
                    <input type="date" name="event_date" required>
                </div>

                <div class="form-group">
                    <label>Event Time *</label>
                    <input type="time" name="event_time" required>
                </div>

                <div class="form-group">
                    <label>Venue *</label>
                    <select name="venue" required>
                        <option value="">Select Venue</option>
                        <option value="Main Hall">Main Hall</option>
                        <option value="Temple Courtyard">Temple Courtyard</option>
                        <option value="Prayer Hall">Prayer Hall</option>
                        <option value="Community Center">Community Center</option>
                        <option value="Outdoor Pavilion">Outdoor Pavilion</option>
                        <option value="Wedding Hall">Wedding Hall</option>
                        <option value="Conference Room">Conference Room</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Max Participants *</label>
                    <input type="number" name="max_participants" min="1" required>
                </div>

                <div class="form-group">
                    <label>Event Type *</label>
                    <select name="event_type" required>
                        <option value="festival">Festival</option>
                        <option value="puja">Puja</option>
                        <option value="pooja">Pooja</option>
                        <option value="cultural">Cultural</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status *</label>
                    <select name="event_status" required>
                        <option value="upcoming">Upcoming</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="submit" name="add_event" class="btn btn-success">Add Event</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Event</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="event_id" id="edit_event_id">
                
                <div class="form-group">
                    <label>Event Name *</label>
                    <input type="text" name="event_name" id="edit_event_name" required>
                </div>

                <div class="form-group">
                    <label>Event Description *</label>
                    <textarea name="event_description" id="edit_event_description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Event Date *</label>
                    <input type="date" name="event_date" id="edit_event_date" required>
                </div>

                <div class="form-group">
                    <label>Event Time *</label>
                    <input type="time" name="event_time" id="edit_event_time" required>
                </div>

                <div class="form-group">
                    <label>Venue *</label>
                    <select name="venue" id="edit_venue" required>
                        <option value="">Select Venue</option>
                        <option value="Main Hall">Main Hall</option>
                        <option value="Temple Courtyard">Temple Courtyard</option>
                        <option value="Prayer Hall">Prayer Hall</option>
                        <option value="Community Center">Community Center</option>
                        <option value="Outdoor Pavilion">Outdoor Pavilion</option>
                        <option value="Wedding Hall">Wedding Hall</option>
                        <option value="Conference Room">Conference Room</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Max Participants *</label>
                    <input type="number" name="max_participants" id="edit_max_participants" min="1" required>
                </div>

                <div class="form-group">
                    <label>Event Type *</label>
                    <select name="event_type" id="edit_event_type" required>
                        <option value="festival">Festival</option>
                        <option value="puja">Puja</option>
                        <option value="pooja">Pooja</option>
                        <option value="cultural">Cultural</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status *</label>
                    <select name="event_status" id="edit_event_status" required>
                        <option value="upcoming">Upcoming</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="submit" name="update_event" class="btn btn-success">Update Event</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Open the Add Event Modal
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        // Close the Add modal
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        // Open the Edit Event Modal
        function openEditModal(eventData) {
            // Populate the form fields with event data
            document.getElementById('edit_event_id').value = eventData.event_id;
            document.getElementById('edit_event_name').value = eventData.event_name;
            document.getElementById('edit_event_description').value = eventData.event_description;
            document.getElementById('edit_event_date').value = eventData.event_date;
            document.getElementById('edit_event_time').value = eventData.event_time;
            document.getElementById('edit_venue').value = eventData.venue;
            document.getElementById('edit_max_participants').value = eventData.max_participants;
            document.getElementById('edit_event_type').value = eventData.event_type;
            document.getElementById('edit_event_status').value = eventData.event_status;
            
            // Show the modal
            document.getElementById('editModal').classList.add('active');
        }

        // Close the Edit modal
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }

        // Confirm the event deletion
        function confirmDelete(eventId) {
            if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
                window.location.href = 'manage_events.php?delete=1&id=' + eventId;
            }
        }
    </script>

</body>
</html>

<?php
// Close the database connection
if ($conn) {
    $conn->close();
}
?>
