<?php
// devotee/view_classes.php
session_start();
require_once "../includes/db_connect.php";

// 🔒 Force login first
if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$classes = [];
$registered = [];   // active registrations only

// ✅ helper to safely read possible column names
function field($row, $options, $default = "") {
    foreach ($options as $name) {
        if (isset($row[$name]) && $row[$name] !== "" && $row[$name] !== null) {
            return $row[$name];
        }
    }
    return $default;
}

// ✅ Get all class IDs already registered (ACTIVE only)
if ($conn) {
    $stmt = $conn->prepare("
        SELECT class_id
        FROM class_registration
        WHERE user_id = ? AND registration_status = 'active'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $registered[(int)$r['class_id']] = true;
    }
    $stmt->close();
}

// ✅ Get all classes (admin created) — order nice
if ($conn) {
    $sql = "
        SELECT *
        FROM cultural_class
        ORDER BY 
            FIELD(schedule_day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
            schedule_time ASC
    ";
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
        $result->free();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cultural Classes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #8a2be2;
            --primary-soft: #f3e8ff;
            --accent: #ff4b8f;
            --text: #2a2344;
            --muted: #7d7a8c;
            --success: #40cf7b;
            --warning: #ffad3b;
            --danger: #ff3b3b;
            --bg-gradient: linear-gradient(135deg, #ff9a5a, #ff5f7c);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins", sans-serif; }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            padding: 30px 16px;
            display: flex;
            justify-content: center;
        }

        .page {
            width: 100%;
            max-width: 1200px;
            background: white;
            padding: 32px 34px;
            border-radius: 28px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to   { opacity: 1; transform: translateY(0);  }
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 26px;
        }

        .header h1 {
            color: var(--text);
            font-size: 26px;
            font-weight: 600;
        }

        .header p {
            color: var(--muted);
            margin-top: 4px;
            font-size: 13px;
        }

        .back-btn {
            padding: 9px 16px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 14px;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
            white-space: nowrap;
        }

        .back-btn:hover { background: var(--primary); color: white; }

        .grid {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 20px;
        }

        .card {
            background: #fdfaff;
            border-radius: 20px;
            padding: 18px 20px;
            border: 1px solid #f0e5ff;
            box-shadow: 0 8px 22px rgba(0,0,0,0.08);
            transition: 0.3s ease;
            display: flex;
            flex-direction: column;
            min-height: 210px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 35px rgba(0,0,0,0.12);
        }

        .c-name {
            font-size: 17px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }

        .tag-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .tag {
            background: white;
            border: 1px solid #eadeff;
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 999px;
            color: var(--muted);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .fee { font-size: 13px; color: var(--primary); font-weight: 600; margin-top: 2px; }
        .capacity { font-size: 12px; color: var(--muted); margin-top: 6px; }

        .footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding-top: 14px;
        }

        .status {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .status.active { background: #eafff1; color: var(--success); }
        .status.inactive { background: #fff5e5; color: var(--warning); }
        .status.registered { background: #eaf2ff; color: #2d6cdf; }

        .btn {
            background: linear-gradient(135deg, #8a2be2, #ff4b8f);
            color: white;
            border: none;
            border-radius: 999px;
            padding: 9px 16px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
            box-shadow: 0 8px 18px rgba(138, 43, 226, 0.28);
            white-space: nowrap;
        }

        .btn:hover { box-shadow: 0 12px 28px rgba(138, 43, 226, 0.4); }

        .btn.disabled {
            background: #e9e6ef;
            color: #8f8a9a;
            box-shadow: none;
            cursor: not-allowed;
            pointer-events: none;
        }

        .msg-full { font-size: 12px; font-weight: 800; color: var(--danger); }

        @media (max-width: 700px) {
            .page { padding: 24px 18px; }
            .header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>

<body>
<div class="page">

    <div class="header">
        <div>
            <h1>Cultural Classes</h1>
            <p>Browse classes and register for those you like.</p>
        </div>

        <a href="devotee_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>

    <?php if (empty($classes)) : ?>
        <p style="text-align:center; color: var(--muted); padding:30px;">No classes available.</p>
    <?php else : ?>
        <div class="grid">
            <?php foreach ($classes as $row): ?>
                <?php
                    $id       = (int) field($row, ['class_id'], 0);
                    $name     = field($row, ['class_name'], 'Cultural Class');
                    $day      = field($row, ['schedule_day'], '');
                    $time     = field($row, ['schedule_time'], '');
                    $fee      = (float) field($row, ['class_fee'], 0);
                    $status   = strtolower(field($row, ['class_status'], 'active'));

                    $current  = (int) field($row, ['current_students'], 0);
                    $max      = (int) field($row, ['max_students'], 0);
                    $isFull   = ($max > 0 && $current >= $max);

                    $isRegistered = isset($registered[$id]);

                    $canRegister = ($status === 'active' && !$isFull && !$isRegistered && $id > 0);
                ?>

                <div class="card">
                    <div class="c-name"><?php echo htmlspecialchars($name); ?></div>

                    <div class="tag-row">
                        <?php if ($day): ?>
                            <span class="tag">📅 <?php echo htmlspecialchars($day); ?></span>
                        <?php endif; ?>
                        <?php if ($time): ?>
                            <span class="tag">⏰ <?php echo htmlspecialchars($time); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="fee">Fee: RM <?php echo number_format($fee, 2); ?></div>

                    <?php if ($max > 0): ?>
                        <div class="capacity">Seats: <?php echo $current; ?> / <?php echo $max; ?></div>
                    <?php endif; ?>

                    <div class="footer">
                        <span class="status <?php echo ($status === 'active') ? 'active' : 'inactive'; ?>">
                            <?php echo ($status === 'active') ? 'Active' : 'Inactive'; ?>
                        </span>

                        <?php if ($isRegistered): ?>
                            <span class="status registered">Already Registered</span>

                        <?php elseif ($canRegister): ?>
                            <a href="classes_registration.php?class_id=<?php echo urlencode((string)$id); ?>" class="btn">Register</a>

                        <?php elseif ($isFull): ?>
                            <span class="msg-full">Class Full</span>

                        <?php else: ?>
                            <span style="color:var(--muted); font-size:11px; font-weight:700;">Closed</span>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
