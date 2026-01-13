<?php
/**
 * ============================================================
 * Sri Balathandayuthapani Temple System
 * Manage Bookings Page
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

// Handle Delete Booking
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM Booking WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    
    if ($stmt->execute()) {
        $success_message = "Booking deleted successfully!";
    } else {
        $error_message = "Failed to delete booking.";
    }
    $stmt->close();
}

// Handle Update Status
if (isset($_POST['update_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE Booking SET booking_status = ? WHERE booking_id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    
    if ($stmt->execute()) {
        $success_message = "Booking status updated successfully!";
    } else {
        $error_message = "Failed to update status.";
    }
    $stmt->close();
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_type = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT b.*, u.full_name, u.phone_number, u.email, p.priest_name 
          FROM Booking b 
          LEFT JOIN User u ON b.user_id = u.user_id 
          LEFT JOIN Priest p ON b.priest_id = p.priest_id 
          WHERE 1=1";

if ($filter_status != 'all') {
    $query .= " AND b.booking_status = '" . $conn->real_escape_string($filter_status) . "'";
}

if ($filter_type != 'all') {
    $query .= " AND b.booking_type = '" . $conn->real_escape_string($filter_type) . "'";
}

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $query .= " AND (u.full_name LIKE '%$search_term%' OR u.email LIKE '%$search_term%' OR u.phone_number LIKE '%$search_term%')";
}

$query .= " ORDER BY b.created_at DESC";

$result = $conn->query($query);

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$stats_query = "SELECT booking_status, COUNT(*) as count FROM Booking GROUP BY booking_status";
$stats_result = $conn->query($stats_query);

while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['booking_status']] = $row['count'];
    $stats['total'] += $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin Panel</title>
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
        }

        .page-header h2 {
            color: #764ba2;
            margin-bottom: 5px;
        }

        .page-header p {
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
            text-align: center;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #666;
            font-size: 14px;
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

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            font-size: 13px;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
            padding: 8px 15px;
            font-size: 13px;
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
            min-width: 1000px;
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

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
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
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
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

        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🕉️ Manage Bookings</h1>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Booking Management</h2>
            <p>View and manage all wedding hall and priest bookings</p>
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
                <div class="number"><?php echo $stats['total']; ?></div>
                <div class="label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['pending']; ?></div>
                <div class="label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['confirmed']; ?></div>
                <div class="label">Confirmed</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['completed']; ?></div>
                <div class="label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['cancelled']; ?></div>
                <div class="label">Cancelled</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Booking Type</label>
                        <select name="type">
                            <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="wedding_hall" <?php echo $filter_type == 'wedding_hall' ? 'selected' : ''; ?>>Wedding Hall</option>
                            <option value="priest_session" <?php echo $filter_type == 'priest_session' ? 'selected' : ''; ?>>Priest Session</option>
                            <option value="special_pooja" <?php echo $filter_type == 'special_pooja' ? 'selected' : ''; ?>>Special Pooja</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Search by name, email, or phone" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="manage_bookings.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="table-section">
            <h3 style="margin-bottom: 20px; color: #764ba2;">All Bookings</h3>
            
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Type</th>
                            <th>Date & Time</th>
                            <th>Priest</th>
                            <th>Guests</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $booking['booking_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['full_name'] ?? 'N/A'); ?></strong><br>
                                    <small><?php echo htmlspecialchars($booking['email'] ?? ''); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['phone_number'] ?? 'N/A'); ?></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $booking['booking_type'])); ?></td>
                                <td>
                                    <?php echo date('d M Y', strtotime($booking['booking_date'])); ?><br>
                                    <small><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['priest_name'] ?? 'Not Assigned'); ?></td>
                                <td><?php echo $booking['number_of_guests'] ?? '-'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="openStatusModal(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['booking_status']; ?>')" class="btn btn-warning">
                                            Update
                                        </button>
                                        <button onclick="confirmDelete(<?php echo $booking['booking_id']; ?>)" class="btn btn-danger">
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
                    📅 No bookings found matching your criteria.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Booking Status</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="booking_id" id="modal_booking_id">
                
                <div class="form-group">
                    <label>Select New Status</label>
                    <select name="status" id="modal_status" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStatusModal(bookingId, currentStatus) {
            document.getElementById('modal_booking_id').value = bookingId;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
        }

        function confirmDelete(bookingId) {
            if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
                window.location.href = 'manage_bookings.php?delete=1&id=' + bookingId;
            }
        }

        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });

        console.log('🕉️ Manage Bookings Page Loaded');
    </script>
</body>
</html>

<?php
if ($conn) {
    $conn->close();
}

?>
