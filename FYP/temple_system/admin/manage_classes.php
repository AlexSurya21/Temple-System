<?php
/**
 * ============================================================
 * Sri Balathandayuthapani Temple System
 * Manage Classes Page
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

// Handle Delete Class
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $class_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM cultural_class WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
    
    if ($stmt->execute()) {
        $success_message = "Class deleted successfully!";
    } else {
        $error_message = "Failed to delete class.";
    }
    $stmt->close();
}

// Handle Add Class
if (isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name']);
    $class_description = trim($_POST['class_description']);
    $schedule_day = $_POST['schedule_day'];
    $schedule_time = $_POST['schedule_time'];
    $duration_minutes = (int)$_POST['duration_minutes'];
    $max_students = (int)$_POST['max_students'];
    $class_fee = (float)$_POST['class_fee'];
    $current_students = 0;
    
    // Check if class name already exists
    $check_stmt = $conn->prepare("SELECT class_id FROM cultural_class WHERE class_name = ?");
    $check_stmt->bind_param("s", $class_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "This class name already exists in the list. Please use a different name.";
    } else {
        $stmt = $conn->prepare("INSERT INTO cultural_class (class_name, class_description, schedule_day, schedule_time, duration_minutes, max_students, current_students, class_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiiid", $class_name, $class_description, $schedule_day, $schedule_time, $duration_minutes, $max_students, $current_students, $class_fee);
        
        if ($stmt->execute()) {
            $success_message = "Class added successfully!";
        } else {
            $error_message = "Failed to add class.";
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Handle Update Class
if (isset($_POST['update_class'])) {
    $class_id = (int)$_POST['class_id'];
    $class_name = trim($_POST['class_name']);
    $class_description = trim($_POST['class_description']);
    $schedule_day = $_POST['schedule_day'];
    $schedule_time = $_POST['schedule_time'];
    $duration_minutes = (int)$_POST['duration_minutes'];
    $max_students = (int)$_POST['max_students'];
    $class_fee = (float)$_POST['class_fee'];
    
    $stmt = $conn->prepare("UPDATE cultural_class SET class_name = ?, class_description = ?, schedule_day = ?, schedule_time = ?, duration_minutes = ?, max_students = ?, class_fee = ? WHERE class_id = ?");
    $stmt->bind_param("ssssiidi", $class_name, $class_description, $schedule_day, $schedule_time, $duration_minutes, $max_students, $class_fee, $class_id);
    
    if ($stmt->execute()) {
        $success_message = "Class updated successfully!";
    } else {
        $error_message = "Failed to update class.";
    }
    $stmt->close();
}

// Get filter parameters
$filter_day = $_GET['day'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query - Using cultural_class table
$query = "SELECT * FROM cultural_class WHERE 1=1";

if ($filter_day != 'all') {
    $query .= " AND schedule_day = '" . $conn->real_escape_string($filter_day) . "'";
}

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $query .= " AND class_name LIKE '%$search_term%'";
}

$query .= " ORDER BY FIELD(schedule_day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), schedule_time";

$result = $conn->query($query);

// Get statistics
$stats = [
    'total_classes' => 0,
    'total_students' => 0,
    'total_capacity' => 0,
    'total_revenue' => 0
];

$stats_query = "SELECT 
    COUNT(*) as total_count,
    SUM(current_students) as total_students,
    SUM(max_students) as total_capacity,
    SUM(current_students * class_fee) as total_revenue
FROM cultural_class";

$stats_result = $conn->query($stats_query);
if ($stats_row = $stats_result->fetch_assoc()) {
    $stats['total_classes'] = (int)($stats_row['total_count'] ?? 0);
    $stats['total_students'] = (int)($stats_row['total_students'] ?? 0);
    $stats['total_capacity'] = (int)($stats_row['total_capacity'] ?? 0);
    $stats['total_revenue'] = (float)($stats_row['total_revenue'] ?? 0);
}

// Calculate availability
$availability_percent = $stats['total_capacity'] > 0 ? round(($stats['total_students'] / $stats['total_capacity']) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes - Admin Panel</title>
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

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .day-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
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

        .fee-display {
            font-weight: bold;
            color: #28a745;
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
        <h1>📚 Manage Classes</h1>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <div class="page-header-content">
                <h2>Class Management</h2>
                <p>Create and manage temple classes and lessons</p>
            </div>
            <button onclick="openAddModal()" class="btn btn-primary">+ Add New Class</button>
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
                <div class="label">Total Classes</div>
                <div class="number"><?php echo $stats['total_classes']; ?></div>
                <div class="sublabel">Active classes</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Students</div>
                <div class="number"><?php echo $stats['total_students']; ?></div>
                <div class="sublabel">Enrolled</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Capacity</div>
                <div class="number"><?php echo $stats['total_capacity']; ?></div>
                <div class="sublabel"><?php echo $availability_percent; ?>% filled</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Revenue</div>
                <div class="number">RM <?php echo number_format($stats['total_revenue'], 2); ?></div>
                <div class="sublabel">From enrollments</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Schedule Day</label>
                        <select name="day">
                            <option value="all" <?php echo $filter_day == 'all' ? 'selected' : ''; ?>>All Days</option>
                            <option value="Monday" <?php echo $filter_day == 'Monday' ? 'selected' : ''; ?>>Monday</option>
                            <option value="Tuesday" <?php echo $filter_day == 'Tuesday' ? 'selected' : ''; ?>>Tuesday</option>
                            <option value="Wednesday" <?php echo $filter_day == 'Wednesday' ? 'selected' : ''; ?>>Wednesday</option>
                            <option value="Thursday" <?php echo $filter_day == 'Thursday' ? 'selected' : ''; ?>>Thursday</option>
                            <option value="Friday" <?php echo $filter_day == 'Friday' ? 'selected' : ''; ?>>Friday</option>
                            <option value="Saturday" <?php echo $filter_day == 'Saturday' ? 'selected' : ''; ?>>Saturday</option>
                            <option value="Sunday" <?php echo $filter_day == 'Sunday' ? 'selected' : ''; ?>>Sunday</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Search Class</label>
                        <input type="text" name="search" placeholder="Search by class name" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="manage_classes.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- Classes Table -->
        <div class="table-section">
            <h3 style="margin-bottom: 20px; color: #764ba2;">All Classes</h3>
            
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Class Name</th>
                            <th>Schedule</th>
                            <th>Duration</th>
                            <th>Students</th>
                            <th>Fee (RM)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($class = $result->fetch_assoc()): 
                            $current = (int)($class['current_students'] ?? 0);
                            $max = (int)($class['max_students'] ?? 0);
                        ?>
                            <tr>
                                <td>#<?php echo $class['class_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($class['class_name']); ?></strong><br>
                                    <small style="color: #999;"><?php echo htmlspecialchars(substr($class['class_description'] ?? '', 0, 50)); ?>...</small>
                                </td>
                                <td>
                                    <span class="day-badge"><?php echo $class['schedule_day']; ?></span><br>
                                    <small><?php echo date('h:i A', strtotime($class['schedule_time'])); ?></small>
                                </td>
                                <td><?php echo $class['duration_minutes']; ?> mins</td>
                                <td><strong><?php echo $current; ?> / <?php echo $max; ?></strong></td>
                                <td class="fee-display"><?php echo number_format($class['class_fee'], 2); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick='openEditModal(<?php echo json_encode($class); ?>)' class="btn btn-warning">
                                            Edit
                                        </button>
                                        <button onclick="confirmDelete(<?php echo $class['class_id']; ?>)" class="btn btn-danger">
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
                    📚 No classes found matching your criteria.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Class Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Class</h3>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Class Name *</label>
                    <input type="text" name="class_name" required>
                </div>

                <div class="form-group">
                    <label>Class Description *</label>
                    <textarea name="class_description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Schedule Day *</label>
                    <select name="schedule_day" required>
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Schedule Time *</label>
                    <input type="time" name="schedule_time" required>
                </div>

                <div class="form-group">
                    <label>Duration (Minutes) *</label>
                    <input type="number" name="duration_minutes" min="15" step="15" value="60" required>
                </div>

                <div class="form-group">
                    <label>Max Students *</label>
                    <input type="number" name="max_students" min="1" required>
                </div>

                <div class="form-group">
                    <label>Class Fee (RM) *</label>
                    <input type="number" name="class_fee" min="0" step="0.01" required>
                </div>

                <div class="modal-buttons">
                    <button type="submit" name="add_class" class="btn btn-success">Add Class</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Class</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="class_id" id="edit_class_id">
                
                <div class="form-group">
                    <label>Class Name *</label>
                    <input type="text" name="class_name" id="edit_class_name" required>
                </div>

                <div class="form-group">
                    <label>Class Description *</label>
                    <textarea name="class_description" id="edit_class_description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Schedule Day *</label>
                    <select name="schedule_day" id="edit_schedule_day" required>
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Schedule Time *</label>
                    <input type="time" name="schedule_time" id="edit_schedule_time" required>
                </div>

                <div class="form-group">
                    <label>Duration (Minutes) *</label>
                    <input type="number" name="duration_minutes" id="edit_duration_minutes" min="15" step="15" required>
                </div>

                <div class="form-group">
                    <label>Max Students *</label>
                    <input type="number" name="max_students" id="edit_max_students" min="1" required>
                </div>

                <div class="form-group">
                    <label>Class Fee (RM) *</label>
                    <input type="number" name="class_fee" id="edit_class_fee" min="0" step="0.01" required>
                </div>

                <div class="modal-buttons">
                    <button type="submit" name="update_class" class="btn btn-success">Update Class</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Open the Add Class Modal
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        // Close the Add modal
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        // Open the Edit Class Modal
        function openEditModal(classData) {
            document.getElementById('edit_class_id').value = classData.class_id;
            document.getElementById('edit_class_name').value = classData.class_name;
            document.getElementById('edit_class_description').value = classData.class_description;
            document.getElementById('edit_schedule_day').value = classData.schedule_day;
            document.getElementById('edit_schedule_time').value = classData.schedule_time;
            document.getElementById('edit_duration_minutes').value = classData.duration_minutes;
            document.getElementById('edit_max_students').value = classData.max_students;
            document.getElementById('edit_class_fee').value = classData.class_fee;
            
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

        // Confirm the class deletion
        function confirmDelete(classId) {
            if (confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
                window.location.href = 'manage_classes.php?delete=1&id=' + classId;
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


