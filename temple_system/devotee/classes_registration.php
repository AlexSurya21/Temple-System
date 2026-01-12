<?php
// devotee/classes_registration.php
session_start();
require_once "../includes/db_connect.php";

// ✅ must login
if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");
    exit;
}

if (!$conn) {
    die("Database connection failed.");
}

$user_id  = (int)$_SESSION['user_id'];
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

if ($class_id <= 0) {
    die("Invalid class id.");
}

$error = "";

// helper: fetch class (optionally lock row FOR UPDATE)
function fetchClass(mysqli $conn, int $class_id, bool $lock = false): ?array
{
    $sql = "
        SELECT class_id, class_name, class_description, schedule_day, schedule_time,
               max_students, current_students, class_fee, class_status
        FROM cultural_class
        WHERE class_id = ?
        LIMIT 1
    ";
    if ($lock) $sql .= " FOR UPDATE";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $class = $res->fetch_assoc();
    $stmt->close();

    return $class ?: null;
}

// helper: check active registration (for display)
function isAlreadyRegisteredActive(mysqli $conn, int $user_id, int $class_id): bool
{
    $stmt = $conn->prepare("
        SELECT 1
        FROM class_registration
        WHERE user_id = ? AND class_id = ? AND registration_status = 'active'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $user_id, $class_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = ($res->num_rows > 0);
    $stmt->close();
    return $ok;
}

// ✅ 1) Fetch class for display
$class = fetchClass($conn, $class_id, false);
if (!$class) {
    die("Class not found.");
}

// ✅ 2) Display checks (error priority)
$status  = strtolower(trim($class['class_status'] ?? 'active'));
$current = (int)($class['current_students'] ?? 0);
$max     = (int)($class['max_students'] ?? 0);
$isFull  = ($max > 0 && $current >= $max);

$isRegisteredActive = isAlreadyRegisteredActive($conn, $user_id, $class_id);

// priority: registered > inactive > full
if ($isRegisteredActive) {
    $error = "You already registered for this class.";
} elseif ($status !== "active") {
    $error = "This class is not active right now.";
} elseif ($isFull) {
    $error = "This class is full.";
}

// ✅ 3) When user clicks Confirm Register
if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($error)) {

    $conn->begin_transaction();

    try {
        // 🔒 Lock class row to prevent last-second seat clash
        $classLocked = fetchClass($conn, $class_id, true);
        if (!$classLocked) {
            throw new Exception("Class not found.");
        }

        $statusLocked  = strtolower(trim($classLocked['class_status'] ?? 'active'));
        $currentLocked = (int)($classLocked['current_students'] ?? 0);
        $maxLocked     = (int)($classLocked['max_students'] ?? 0);
        $isFullLocked  = ($maxLocked > 0 && $currentLocked >= $maxLocked);

        if ($statusLocked !== "active") {
            throw new Exception("This class is not active right now.");
        }
        if ($isFullLocked) {
            throw new Exception("Sorry, class is full.");
        }

        // ✅ Check if ANY record already exists (active OR inactive)
        $stmt = $conn->prepare("
            SELECT registration_id, registration_status
            FROM class_registration
            WHERE user_id = ? AND class_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $user_id, $class_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing = $res->fetch_assoc();
        $stmt->close();

        if ($existing) {
            // if already active, block
            if (strtolower($existing['registration_status']) === 'active') {
                throw new Exception("You already registered for this class.");
            }

            // if inactive, re-activate (prevents duplicate key)
            $stmt = $conn->prepare("
                UPDATE class_registration
                SET registration_status = 'active', registration_date = NOW()
                WHERE registration_id = ?
            ");
            $stmt->bind_param("i", $existing['registration_id']);
            $stmt->execute();
            $stmt->close();

        } else {
            // no record yet -> insert new
            $stmt = $conn->prepare("
                INSERT INTO class_registration (user_id, class_id, registration_date, registration_status)
                VALUES (?, ?, NOW(), 'active')
            ");
            $stmt->bind_param("ii", $user_id, $class_id);
            $stmt->execute();
            $stmt->close();
        }

        // ✅ Increase current_students (ONLY when registration becomes active now)
        $stmt = $conn->prepare("
            UPDATE cultural_class
            SET current_students = current_students + 1
            WHERE class_id = ?
              AND (max_students IS NULL OR current_students < max_students)
              AND LOWER(class_status) = 'active'
        ");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            throw new Exception("Sorry, class just became full or inactive.");
        }
        $stmt->close();

        $conn->commit();

        header("Location: my_classes.php?success=1");
        exit;

    } catch (Throwable $e) {
        $conn->rollback();

        // clean friendly message (no raw MySQL)
        $msg = $e->getMessage();
        if (stripos($msg, "Duplicate entry") !== false) {
            $error = "You already registered for this class.";
        } else {
            $error = $msg;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Class Registration</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg-gradient: linear-gradient(135deg, #ff8a4a, #ff5f7c);
      --card-bg:#ffffff;
      --text:#2a2344;
      --muted:#7c7a90;
      --purple:#7d2cff;
      --purple-soft:#f3ecff;
      --border:#eee6ff;
      --success:#37c978;
      --danger:#ff3b3b;
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:Poppins,sans-serif;}
    body{min-height:100vh;background:var(--bg-gradient);display:flex;justify-content:center;align-items:center;padding:32px 16px;}
    .shell{max-width:900px;width:100%;background:var(--card-bg);border-radius:26px;box-shadow:0 24px 60px rgba(0,0,0,.18);padding:24px 30px;}
    .top{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:16px;}
    h1{font-size:22px;color:var(--text);font-weight:700;}
    .btn-back{font-size:13px;color:var(--purple);text-decoration:none;padding:8px 12px;border-radius:999px;background:var(--purple-soft);}
    .card{border:1px solid var(--border);background:#faf7ff;border-radius:18px;padding:16px 18px;box-shadow:0 10px 24px rgba(0,0,0,.05);}
    .title{font-weight:700;color:var(--text);font-size:18px;margin-bottom:10px;}
    .meta{display:flex;flex-wrap:wrap;gap:8px;color:var(--muted);font-size:12px;margin-bottom:10px;}
    .chip{background:#fff;border:1px solid var(--border);border-radius:999px;padding:5px 10px;}
    .desc{color:var(--text);font-size:13px;line-height:1.5;margin-top:6px;}
    .row{margin-top:14px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;}
    .price{font-weight:700;color:var(--purple);}
    .status{font-size:12px;font-weight:700;}
    .status.ok{color:var(--success);}
    .status.bad{color:var(--danger);}
    .actions{margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;}
    .btn{
      border:none;cursor:pointer;border-radius:999px;padding:10px 16px;
      font-weight:700;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;
    }
    .btn.primary{background:linear-gradient(135deg,#7d2cff,#ff4b8f);color:white;box-shadow:0 10px 22px rgba(125,44,255,.25);}
    .btn.secondary{background:var(--purple-soft);color:var(--purple);}
    .alert{margin:12px 0;padding:10px 12px;border-radius:12px;font-size:13px;}
    .alert.error{background:#ffecec;color:#b30000;border:1px solid #ffd0d0;}
  </style>
</head>
<body>
  <div class="shell">
    <div class="top">
      <h1>Confirm Class Registration</h1>
      <a class="btn-back" href="view_classes.php">← Back to Classes</a>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="title"><?php echo htmlspecialchars($class['class_name']); ?></div>

      <div class="meta">
        <span class="chip">📅 <?php echo htmlspecialchars($class['schedule_day']); ?></span>
        <span class="chip">⏰ <?php echo htmlspecialchars($class['schedule_time']); ?></span>
        <span class="chip">👥 Seats: <?php echo (int)$class['current_students']; ?> / <?php echo (int)$class['max_students']; ?></span>
      </div>

      <div class="desc">
        <?php
          $d = trim($class['class_description'] ?? "");
          echo $d !== "" ? htmlspecialchars($d) : "No description provided.";
        ?>
      </div>

      <div class="row">
        <div class="price">Fee: RM <?php echo number_format((float)$class['class_fee'], 2); ?></div>

        <?php if (empty($error)): ?>
          <div class="status ok">✅ Ready to register</div>
        <?php else: ?>
          <div class="status bad">❌ Cannot register</div>
        <?php endif; ?>
      </div>

      <div class="actions">
        <?php if (empty($error)): ?>
          <form method="post" onsubmit="return confirm('Confirm register for this class?');">
            <button type="submit" class="btn primary">✅ Confirm Register</button>
          </form>
        <?php endif; ?>

        <a class="btn secondary" href="view_classes.php">Back</a>
      </div>
    </div>
  </div>
</body>
</html>
