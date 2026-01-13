<?php
session_start();
require_once "../includes/db_connect.php";

// 🔒 optional: force login (uncomment if you want)
// if (!isset($_SESSION['user_id'])) {
//     header("Location: devotee_login.php");
//     exit;
// }

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if ($event_id <= 0) {
    die("Invalid event_id");
}

// helper: safely pick a value from possible column names
function pick($row, $keys, $default = "") {
    foreach ($keys as $k) {
        if (isset($row[$k]) && $row[$k] !== null && trim((string)$row[$k]) !== "") {
            return $row[$k];
        }
    }
    return $default;
}

$event = null;

if ($conn) {
    // ✅ Try to select common columns (if some don't exist, it's okay as long as your table has these actual names)
    // If your column names differ, change them here (see comment below).
    $sql = "SELECT * FROM event WHERE event_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$event) {
    die("Event not found.");
}

// ✅ read fields (supports different column names)
$title = pick($event, ['event_name', 'title', 'event_title', 'name'], 'Event');
$date  = pick($event, ['event_date', 'date'], '');
$time  = pick($event, ['event_time', 'time'], '');
$loc   = pick($event, ['location', 'venue', 'place'], '');

// ✅ THIS is your description (the important part)
$description = pick($event, [
    'description',
    'event_description',
    'event_desc',
    'details'
], 'No description provided.');

// Optional poster column
$poster = pick($event, ['poster', 'poster_image', 'image', 'event_image'], '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($title); ?> - Event Details</title>

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
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:Poppins,sans-serif;}
    body{min-height:100vh;background:var(--bg-gradient);display:flex;justify-content:center;padding:32px 16px;}
    .shell{max-width:1100px;width:100%;background:var(--card-bg);border-radius:26px;box-shadow:0 24px 60px rgba(0,0,0,.18);padding:26px 30px;}
    .top{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:18px;}
    h1{font-size:28px;color:var(--text);font-weight:800;line-height:1.1;}
    .sub{color:var(--muted);font-size:13px;margin-top:6px;}
    .btn-back{font-size:13px;color:var(--purple);text-decoration:none;padding:8px 12px;border-radius:999px;background:var(--purple-soft);white-space:nowrap;}
    .grid{display:grid;grid-template-columns: 1.3fr 1fr;gap:16px;margin-top:14px;}
    .panel{border:1px solid var(--border);background:#faf7ff;border-radius:18px;padding:16px 18px;min-height:320px;}
    .meta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;}
    .chip{background:#fff;border:1px solid var(--border);border-radius:999px;padding:6px 10px;color:var(--muted);font-size:12px;display:inline-flex;gap:8px;align-items:center;}
    .desc{margin-top:10px;color:#5f5c72;font-size:14px;line-height:1.7;white-space:normal;}
    .poster-box{border:1px dashed #e3d9ff;border-radius:16px;height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);background:#fbf9ff;overflow:hidden;}
    .poster-box img{width:100%;height:100%;object-fit:cover;display:block;}
    @media(max-width:900px){ .grid{grid-template-columns:1fr;} }
  </style>
</head>
<body>
  <div class="shell">
    <div class="top">
      <div>
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <div class="sub">Event details</div>
      </div>
      <a class="btn-back" href="event_list.php">← Back to Events</a>
    </div>

    <div class="grid">
      <!-- LEFT -->
      <div class="panel">
        <div class="meta">
          <?php if ($date): ?><span class="chip">📅 <?php echo htmlspecialchars($date); ?></span><?php endif; ?>
          <?php if ($time): ?><span class="chip">⏰ <?php echo htmlspecialchars($time); ?></span><?php endif; ?>
          <?php if ($loc):  ?><span class="chip">📍 <?php echo htmlspecialchars($loc); ?></span><?php endif; ?>
        </div>

        <!-- ✅ DESCRIPTION SHOWS HERE -->
        <div class="desc">
          <?php echo nl2br(htmlspecialchars($description)); ?>
        </div>
      </div>

      <!-- RIGHT (poster) -->
      <div class="panel">
        <div class="poster-box">
          <?php if ($poster): ?>
             <img src="../uploads/<?php echo htmlspecialchars($poster); ?>" alt="Event poster">
          <?php else: ?>
             <div>No poster image</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</body>
</html>
