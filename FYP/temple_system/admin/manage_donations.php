<?php
/**
 * ============================================================
 * Sri Balathandayuthapani Temple System
 * Manage Donations Page
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

// Handle Delete Donation
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $donation_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM Donation WHERE donation_id = ?");
    $stmt->bind_param("i", $donation_id);
    
    if ($stmt->execute()) {
        $success_message = "Donation record deleted successfully!";
    } else {
        $error_message = "Failed to delete donation.";
    }
    $stmt->close();
}

// Handle Update Status
if (isset($_POST['update_status'])) {
    $donation_id = (int)$_POST['donation_id'];
    $new_status = $_POST['payment_status'];
    
    $stmt = $conn->prepare("UPDATE Donation SET payment_status = ? WHERE donation_id = ?");
    $stmt->bind_param("si", $new_status, $donation_id);
    
    if ($stmt->execute()) {
        $success_message = "Donation status updated successfully!";
    } else {
        $error_message = "Failed to update status.";
    }
    $stmt->close();
}

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT d.*, u.full_name, u.email, u.phone_number 
          FROM Donation d 
          LEFT JOIN User u ON d.user_id = u.user_id 
          WHERE 1=1";

if ($filter_type != 'all') {
    $query .= " AND d.donation_type = '" . $conn->real_escape_string($filter_type) . "'";
}

if ($filter_status != 'all') {
    $query .= " AND d.payment_status = '" . $conn->real_escape_string($filter_status) . "'";
}

if (!empty($date_from)) {
    $query .= " AND d.donation_date >= '" . $conn->real_escape_string($date_from) . "'";
}

if (!empty($date_to)) {
    $query .= " AND d.donation_date <= '" . $conn->real_escape_string($date_to) . "'";
}

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $query .= " AND (u.full_name LIKE '%$search_term%' OR u.email LIKE '%$search_term%')";
}

$query .= " ORDER BY d.donation_date DESC";

$result = $conn->query($query);

// Get statistics - Initialize with default values
$stats = [
    'total_donations' => 0,
    'total_amount' => 0,
    'pending_count' => 0,
    'pending_amount' => 0,
    'completed_count' => 0,
    'completed_amount' => 0,
    'general' => 0,
    'festival' => 0,
    'maintenance' => 0,
    'annadanam' => 0
];

$stats_query = "SELECT 
    COUNT(*) as total_count,
    SUM(amount) as total_amount,
    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as pending_amount,
    SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as completed_amount,
    SUM(CASE WHEN donation_type = 'general' THEN amount ELSE 0 END) as general,
    SUM(CASE WHEN donation_type = 'festival' THEN amount ELSE 0 END) as festival,
    SUM(CASE WHEN donation_type = 'maintenance' THEN amount ELSE 0 END) as maintenance,
    SUM(CASE WHEN donation_type = 'annadanam' THEN amount ELSE 0 END) as annadanam
FROM Donation";

$stats_result = $conn->query($stats_query);
if ($stats_row = $stats_result->fetch_assoc()) {
    $stats['total_donations'] = (int)($stats_row['total_count'] ?? 0);
    $stats['total_amount'] = (float)($stats_row['total_amount'] ?? 0);
    $stats['pending_count'] = (int)($stats_row['pending_count'] ?? 0);
    $stats['pending_amount'] = (float)($stats_row['pending_amount'] ?? 0);
    $stats['completed_count'] = (int)($stats_row['completed_count'] ?? 0);
    $stats['completed_amount'] = (float)($stats_row['completed_amount'] ?? 0);
    $stats['general'] = (float)($stats_row['general'] ?? 0);
    $stats['festival'] = (float)($stats_row['festival'] ?? 0);
    $stats['maintenance'] = (float)($stats_row['maintenance'] ?? 0);
    $stats['annadanam'] = (float)($stats_row['annadanam'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Donations - Admin Panel</title>
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
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #28a745;
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

        .btn-info {
            background: #17a2b8;
            color: white;
            padding: 10px 20px;
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

        .amount {
            font-weight: bold;
            color: #28a745;
            font-size: 16px;
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

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .type-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }

        .type-general {
            background: #d1ecf1;
            color: #0c5460;
        }

        .type-festival {
            background: #f8d7da;
            color: #721c24;
        }

        .type-maintenance {
            background: #fff3cd;
            color: #856404;
        }

        .type-annadanam {
            background: #d4edda;
            color: #155724;
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

        .export-section {
            margin-bottom: 15px;
            text-align: right;
        }

        /* ========================================
           PRINT STYLES - Only print the table
           ======================================== */
        @media print {
            /* Hide everything except the table */
            body {
                background: white !important;
            }
            
            body * {
                visibility: hidden;
            }
            
            .table-section,
            .table-section * {
                visibility: visible;
            }
            
            .table-section {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white !important;
                box-shadow: none !important;
                padding: 20px !important;
            }
            
            /* Add print header */
            .table-section::before {
                content: "Sri Balathandayuthapani Temple - Donations Report";
                display: block;
                text-align: center;
                font-size: 20px;
                font-weight: bold;
                color: #764ba2;
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 2px solid #764ba2;
            }
            
            /* Hide buttons and export section */
            .export-section,
            .action-buttons,
            .btn {
                display: none !important;
            }
            
            /* Keep table title centered */
            .table-section h3 {
                text-align: center;
                margin-bottom: 20px;
            }
            
            /* Adjust table for printing */
            table {
                width: 100%;
                page-break-inside: auto;
                min-width: auto;
            }
            
            table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            table th {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            /* Keep badge colors in print */
            .status-badge,
            .type-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .amount {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            /* Remove hover effects */
            table tr:hover {
                background: none !important;
            }
            
            /* Hide the "Actions" column header and cells */
            table th:last-child,
            table td:last-child {
                display: none;
            }
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
        <h1>💰 Manage Donations</h1>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Donation Management</h2>
            <p>View and manage all temple donations and contributions</p>
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
                <div class="label">Total Donations</div>
                <div class="number"><?php echo $stats['total_donations']; ?></div>
                <div class="sublabel">All time contributions</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Amount</div>
                <div class="number">RM <?php echo number_format($stats['total_amount'], 2); ?></div>
                <div class="sublabel">Total collected</div>
            </div>
            <div class="stat-card">
                <div class="label">Completed</div>
                <div class="number"><?php echo $stats['completed_count']; ?></div>
                <div class="sublabel">RM <?php echo number_format($stats['completed_amount'], 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Pending</div>
                <div class="number"><?php echo $stats['pending_count']; ?></div>
                <div class="sublabel">RM <?php echo number_format($stats['pending_amount'], 2); ?></div>
            </div>
        </div>

        <!-- Donation Type Breakdown -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">General Fund</div>
                <div class="number">RM <?php echo number_format($stats['general'], 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Festival Fund</div>
                <div class="number">RM <?php echo number_format($stats['festival'], 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Maintenance</div>
                <div class="number">RM <?php echo number_format($stats['maintenance'], 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Annadanam</div>
                <div class="number">RM <?php echo number_format($stats['annadanam'], 2); ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Donation Type</label>
                        <select name="type">
                            <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="general" <?php echo $filter_type == 'general' ? 'selected' : ''; ?>>General</option>
                            <option value="festival" <?php echo $filter_type == 'festival' ? 'selected' : ''; ?>>Festival</option>
                            <option value="maintenance" <?php echo $filter_type == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="annadanam" <?php echo $filter_type == 'annadanam' ? 'selected' : ''; ?>>Annadanam</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
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
                        <label>Search Donor</label>
                        <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="manage_donations.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- Donations Table -->
        <div class="table-section">
            <div class="export-section">
                <button onclick="window.print()" class="btn btn-info">🖨️ Print Report</button>
            </div>

            <h3 style="margin-bottom: 20px; color: #764ba2;">All Donations</h3>
            
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Donor</th>
                            <th>Contact</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($donation = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $donation['donation_id']; ?></td>
                                <td><?php echo date('d M Y', strtotime($donation['donation_date'])); ?></td>
                                <td>
                                    <?php if ($donation['is_anonymous']): ?>
                                        <em>Anonymous</em>
                                    <?php else: ?>
                                        <strong><?php echo htmlspecialchars($donation['full_name'] ?? 'N/A'); ?></strong><br>
                                        <small><?php echo htmlspecialchars($donation['email'] ?? ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $donation['is_anonymous'] ? '-' : htmlspecialchars($donation['phone_number'] ?? 'N/A'); ?></td>
                                <td class="amount">RM <?php echo number_format((float)($donation['amount'] ?? 0), 2); ?></td>
                                <td>
                                    <span class="type-badge type-<?php echo $donation['donation_type']; ?>">
                                        <?php echo ucfirst($donation['donation_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($donation['donation_purpose'] ?? '-'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $donation['payment_status']; ?>">
                                        <?php echo ucfirst($donation['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="openStatusModal(<?php echo $donation['donation_id']; ?>, '<?php echo $donation['payment_status']; ?>')" class="btn btn-success">
                                            Update
                                        </button>
                                        <button onclick="confirmDelete(<?php echo $donation['donation_id']; ?>)" class="btn btn-danger">
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
                    💰 No donations found matching your criteria.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Payment Status</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="donation_id" id="modal_donation_id">
                
                <div class="form-group">
                    <label>Select New Status</label>
                    <select name="payment_status" id="modal_status" required>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
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
        function openStatusModal(donationId, currentStatus) {
            document.getElementById('modal_donation_id').value = donationId;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
        }

        function confirmDelete(donationId) {
            if (confirm('Are you sure you want to delete this donation record? This action cannot be undone.')) {
                window.location.href = 'manage_donations.php?delete=1&id=' + donationId;
            }
        }

        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });

        console.log('💰 Manage Donations Page Loaded');
    </script>
</body>
</html>

<?php
if ($conn) {
    $conn->close();
}
?>
