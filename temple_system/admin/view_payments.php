<?php
/**
 * ============================================================
 * Sri Balathandayuthapani Temple System
 * View Payments Page - FIXED
 * 
 * Created by: Avenesh A/L Kumaran (1221106783)
 * Last Modified: January 2026
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

// Handle Update Payment Status
if (isset($_POST['update_status'])) {
    $payment_id = (int)$_POST['payment_id'];
    $payment_status = $_POST['payment_status'];
    
    // FIXED: Changed 'payment' to 'payments'
    $stmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE payment_id = ?");
    $stmt->bind_param("si", $payment_status, $payment_id);
    
    if ($stmt->execute()) {
        $success_message = "Payment status updated successfully!";
    } else {
        $error_message = "Failed to update payment status.";
    }
    $stmt->close();
}

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$filter_method = $_GET['method'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query - FIXED: Changed 'payment' to 'payments'
$query = "SELECT * FROM payments WHERE 1=1";

if ($filter_type != 'all') {
    $query .= " AND payment_type = '" . $conn->real_escape_string($filter_type) . "'";
}

if ($filter_status != 'all') {
    $query .= " AND payment_status = '" . $conn->real_escape_string($filter_status) . "'";
}

if ($filter_method != 'all') {
    $query .= " AND payment_method = '" . $conn->real_escape_string($filter_method) . "'";
}

if (!empty($date_from)) {
    $query .= " AND payment_date >= '" . $conn->real_escape_string($date_from) . "'";
}

if (!empty($date_to)) {
    $query .= " AND payment_date <= '" . $conn->real_escape_string($date_to) . " 23:59:59'";
}

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $query .= " AND (transaction_id LIKE '%$search_term%' OR receipt_number LIKE '%$search_term%')";
}

$query .= " ORDER BY payment_date DESC";

$result = $conn->query($query);

// Get statistics - FIXED: Changed 'payment' to 'payments'
$stats = [
    'total_payments' => 0,
    'total_amount' => 0,
    'completed_payments' => 0,
    'completed_amount' => 0,
    'pending_payments' => 0,
    'pending_amount' => 0,
    'failed_payments' => 0,
    'failed_amount' => 0
];

$stats_query = "SELECT 
    COUNT(*) as total_count,
    SUM(amount) as total_amount,
    SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as completed_amount,
    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as pending_amount,
    SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed_count,
    SUM(CASE WHEN payment_status = 'failed' THEN amount ELSE 0 END) as failed_amount
FROM payments";

$stats_result = $conn->query($stats_query);
if ($stats_row = $stats_result->fetch_assoc()) {
    $stats['total_payments'] = (int)($stats_row['total_count'] ?? 0);
    $stats['total_amount'] = (float)($stats_row['total_amount'] ?? 0);
    $stats['completed_payments'] = (int)($stats_row['completed_count'] ?? 0);
    $stats['completed_amount'] = (float)($stats_row['completed_amount'] ?? 0);
    $stats['pending_payments'] = (int)($stats_row['pending_count'] ?? 0);
    $stats['pending_amount'] = (float)($stats_row['pending_amount'] ?? 0);
    $stats['failed_payments'] = (int)($stats_row['failed_count'] ?? 0);
    $stats['failed_amount'] = (float)($stats_row['failed_amount'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Payments - Admin Panel</title>
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
            max-width: 1600px;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .stat-card.success .number {
            color: #28a745;
        }

        .stat-card.warning .number {
            color: #ffc107;
        }

        .stat-card.danger .number {
            color: #dc3545;
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
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
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

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .type-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
        }

        .amount-display {
            font-weight: bold;
            color: #28a745;
            font-size: 15px;
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
            margin: 20px;
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
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
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
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>💳 View Payments</h1>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Payment Management</h2>
            <p>View and manage all payment transactions</p>
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
                <div class="label">Total Payments</div>
                <div class="number"><?php echo $stats['total_payments']; ?></div>
                <div class="sublabel">RM <?php echo number_format($stats['total_amount'], 2); ?></div>
            </div>
            <div class="stat-card success">
                <div class="label">Completed</div>
                <div class="number"><?php echo $stats['completed_payments']; ?></div>
                <div class="sublabel">RM <?php echo number_format($stats['completed_amount'], 2); ?></div>
            </div>
            <div class="stat-card warning">
                <div class="label">Pending</div>
                <div class="number"><?php echo $stats['pending_payments']; ?></div>
                <div class="sublabel">RM <?php echo number_format($stats['pending_amount'], 2); ?></div>
            </div>
            <div class="stat-card danger">
                <div class="label">Failed</div>
                <div class="number"><?php echo $stats['failed_payments']; ?></div>
                <div class="sublabel">RM <?php echo number_format($stats['failed_amount'], 2); ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Payment Type</label>
                        <select name="type">
                            <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="donation" <?php echo $filter_type == 'donation' ? 'selected' : ''; ?>>Donation</option>
                            <option value="booking" <?php echo $filter_type == 'booking' ? 'selected' : ''; ?>>Booking</option>
                            <option value="class_registration" <?php echo $filter_type == 'class_registration' ? 'selected' : ''; ?>>Class Registration</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?php echo $filter_status == 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $filter_status == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Payment Method</label>
                        <select name="method">
                            <option value="all" <?php echo $filter_method == 'all' ? 'selected' : ''; ?>>All Methods</option>
                            <option value="cash" <?php echo $filter_method == 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="online" <?php echo $filter_method == 'online' ? 'selected' : ''; ?>>Online</option>
                            <option value="bank_transfer" <?php echo $filter_method == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
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
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Transaction ID or Receipt" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="view_payments.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="table-section">
            <h3 style="margin-bottom: 20px; color: #764ba2;">All Payments</h3>
            
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Transaction ID</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Receipt</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $payment['payment_id']; ?></strong></td>
                                <td>
                                    <small style="font-family: monospace; font-size: 11px;">
                                        <?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?>
                                    </small>
                                </td>
                                <td class="amount-display">RM <?php echo number_format($payment['amount'], 2); ?></td>
                                <td>
                                    <span class="type-badge">
                                        <?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'] ?? 'N/A')); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('d M Y', strtotime($payment['payment_date'])); ?><br>
                                    <small style="color: #999;"><?php echo date('h:i A', strtotime($payment['payment_date'])); ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($payment['receipt_number'])): ?>
                                        <small style="font-family: monospace;"><?php echo htmlspecialchars($payment['receipt_number']); ?></small>
                                    <?php else: ?>
                                        <small style="color: #999;">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick='openStatusModal(<?php echo json_encode($payment); ?>)' class="btn btn-info">
                                        Update
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    💳 No payments found matching your criteria.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Payment Status</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="payment_id" id="status_payment_id">
                
                <div class="form-group">
                    <label>Payment ID</label>
                    <input type="text" id="display_payment_id" readonly style="background: #f5f5f5;">
                </div>

                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" id="display_amount" readonly style="background: #f5f5f5;">
                </div>

                <div class="form-group">
                    <label>Current Status</label>
                    <input type="text" id="display_current_status" readonly style="background: #f5f5f5;">
                </div>

                <div class="form-group">
                    <label>New Status *</label>
                    <select name="payment_status" id="status_payment_status" required>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
                    <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStatusModal(paymentData) {
            document.getElementById('status_payment_id').value = paymentData.payment_id;
            document.getElementById('display_payment_id').value = '#' + paymentData.payment_id;
            document.getElementById('display_amount').value = 'RM ' + parseFloat(paymentData.amount).toFixed(2);
            document.getElementById('display_current_status').value = paymentData.payment_status.charAt(0).toUpperCase() + paymentData.payment_status.slice(1);
            document.getElementById('status_payment_status').value = paymentData.payment_status;
            
            document.getElementById('statusModal').classList.add('active');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
        }

        window.onclick = function(event) {
            const statusModal = document.getElementById('statusModal');
            if (event.target == statusModal) {
                closeStatusModal();
            }
        }
    </script>

</body>
</html>

<?php
if ($conn) {
    $conn->close();
}
?>