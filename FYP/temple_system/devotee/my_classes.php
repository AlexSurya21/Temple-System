<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? "Devotee";

$rows = [];

if ($conn) {
    $sql = "
        SELECT 
            cr.class_id,
            cr.registration_date,
            cr.registration_status,
            cc.class_name,
            cc.schedule_day,
            cc.schedule_time
        FROM class_registration cr
        JOIN cultural_class cc ON cc.class_id = cr.class_id
        WHERE cr.user_id = ?
        ORDER BY 
            (cr.registration_status = 'active') DESC,
            cr.registration_date DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $stmt->close();
}

function niceDate($dateStr) {
    if (!$dateStr) return "";
    $ts = strtotime($dateStr);
    if (!$ts) return $dateStr;
    return date("d M Y, g:i A", $ts);
}

$active = [];
$history = [];

foreach ($rows as $r) {
    $st = strtolower(trim($r['registration_status'] ?? 'active'));
    if ($st === 'active') $active[] = $r;
    else $history[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Classes</title>

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
      --danger-soft:#ffecec;
      --shadow: 0 10px 24px rgba(0,0,0,.06);
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:Poppins,sans-serif;}
    body{min-height:100vh;background:var(--bg-gradient);display:flex;justify-content:center;padding:32px 16px;}
    .shell{max-width:1100px;width:100%;background:var(--card-bg);border-radius:26px;box-shadow:0 24px 60px rgba(0,0,0,.18);padding:24px 30px;}
    .top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:16px;}
    h1{font-size:28px;color:var(--text);font-weight:800;}
    .sub{color:var(--muted);font-size:13px;margin-top:6px;}
    .btn-back{font-size:13px;color:var(--purple);text-decoration:none;padding:10px 14px;border-radius:999px;background:var(--purple-soft);font-weight:700;}

    .section-title{
      margin-top:10px;
      font-size:15px;
      font-weight:800;
      color:var(--text);
    }

    .grid{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:14px;
      margin-top:12px;
    }

    .card{
      border:1px solid var(--border);
      background:#faf7ff;
      border-radius:18px;
      padding:14px 16px;
      box-shadow:var(--shadow);
    }

    .title{font-weight:800;color:var(--text);font-size:17px;margin-bottom:10px;}
    .meta{display:flex;flex-wrap:wrap;gap:8px;color:var(--muted);font-size:12px;}
    .chip{background:#fff;border:1px solid var(--border);border-radius:999px;padding:6px 10px;}

    .row{
      margin-top:12px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
    }

    .pill{
      border-radius:999px;
      padding:5px 10px;
      font-size:12px;
      font-weight:800;
      border:1px solid transparent;
    }

    .pill.active{background:#e8fff3;color:var(--success);border-color:#c8f0db;}
    .pill.other{background:#f2f3f7;color:#6c6f81;border-color:#e1e3ec;}

    .date{
      color:var(--muted);
      font-size:12px;
      white-space:nowrap;
      background:#fff;
      border:1px dashed #e6ddff;
      padding:6px 10px;
      border-radius:12px;
    }

    .btn-danger{
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:10px 12px;
      border-radius:999px;
      background:var(--danger-soft);
      border:1px solid #ffd0d0;
      color:#b30000;
      font-size:13px;
      font-weight:800;
      cursor:pointer;
    }
    .btn-danger:hover{filter:brightness(.98);}

    .empty{padding:24px;text-align:center;color:var(--muted);border:1px dashed #e6ddff;border-radius:16px;background:#fff;margin-top:12px;}

    details{
      margin-top:14px;
      border:1px solid var(--border);
      border-radius:16px;
      background:#fff;
      padding:10px 12px;
    }
    summary{
      cursor:pointer;
      font-weight:800;
      color:var(--text);
      list-style:none;
    }
    summary::-webkit-details-marker{display:none;}
    .hint{color:var(--muted);font-weight:500;font-size:12px;margin-top:4px;}

    @media(max-width:800px){
      .grid{grid-template-columns:1fr}
      .shell{padding:20px}
    }
  </style>
</head>

<body>
  <div class="shell">
    <div class="top">
      <div>
        <h1>My Classes</h1>
        <div class="sub">Classes you have registered for, <?php echo htmlspecialchars($user_name); ?>.</div>
      </div>
      <a class="btn-back" href="devotee_dashboard.php">← Back to Dashboard</a>
    </div>

    <?php if (empty($rows)): ?>
      <div class="empty">No registered classes yet. Go to “View Cultural Classes” and register.</div>

    <?php else: ?>

      <div class="section-title">Active Registrations</div>
      <?php if (count($active) === 0): ?>
        <div class="empty">No active classes right now.</div>
      <?php else: ?>
        <div class="grid">
          <?php foreach ($active as $r): ?>
            <div class="card">
              <div class="title"><?php echo htmlspecialchars($r['class_name'] ?? 'Class'); ?></div>

              <div class="meta">
                <span class="chip">📅 <?php echo htmlspecialchars($r['schedule_day'] ?? '-'); ?></span>
                <span class="chip">⏰ <?php echo htmlspecialchars($r['schedule_time'] ?? '-'); ?></span>
              </div>

              <div class="row">
                <span class="pill active">✅ active</span>
                <span class="date">Registered: <?php echo htmlspecialchars(niceDate($r['registration_date'] ?? '')); ?></span>

                <a href="remove_class.php?class_id=<?php echo (int)$r['class_id']; ?>"
                   onclick="return confirm('Are you sure you want to unregister from this class?');"
                   class="btn-danger">
                  ❌ Remove
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <details>
        <summary>
          History (Cancelled/Other) — <?php echo count($history); ?> item(s)
          <div class="hint">Click to expand/collapse</div>
        </summary>

        <?php if (count($history) === 0): ?>
          <div class="empty">No history records.</div>
        <?php else: ?>
          <div class="grid">
            <?php foreach ($history as $r): ?>
              <?php $st = strtolower(trim($r['registration_status'] ?? 'cancelled')); ?>
              <div class="card" style="background:#fbfbfe;">
                <div class="title"><?php echo htmlspecialchars($r['class_name'] ?? 'Class'); ?></div>

                <div class="meta">
                  <span class="chip">📅 <?php echo htmlspecialchars($r['schedule_day'] ?? '-'); ?></span>
                  <span class="chip">⏰ <?php echo htmlspecialchars($r['schedule_time'] ?? '-'); ?></span>
                </div>

                <div class="row">
                  <span class="pill other">⚪ <?php echo htmlspecialchars($st); ?></span>
                  <span class="date">Updated: <?php echo htmlspecialchars(niceDate($r['registration_date'] ?? '')); ?></span>

                  <a href="remove_class.php?class_id=<?php echo (int)$r['class_id']; ?>"
                     onclick="return confirm('Remove this record from your list?');"
                     class="btn-danger">
                    ❌ Remove
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </details>

    <?php endif; ?>
  </div>
</body>
</html>
