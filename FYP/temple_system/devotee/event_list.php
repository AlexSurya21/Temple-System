<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php"); 
    exit;
}

$events = [];

if ($conn) {
    $sql = "SELECT * FROM event ORDER BY event_date ASC";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $events[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Temple Events</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<style>
body{font-family:Poppins;background:linear-gradient(135deg,#ff8a4a,#ff5f7c);padding:30px}
.box{background:#fff;border-radius:20px;padding:24px;max-width:900px;margin:auto}
.card{border:1px solid #eee;border-radius:16px;padding:16px;margin-bottom:12px}
h1{margin-bottom:10px}
a{text-decoration:none;color:#7d2cff;font-weight:600}
.date{color:#777;font-size:13px}
</style>
</head>
<body>

<div class="box">
<h1>Temple Events</h1>

<?php if (empty($events)): ?>
<p>No events available.</p>
<?php else: ?>
<?php foreach ($events as $e): ?>
<div class="card">
    <h3><?php echo htmlspecialchars($e['event_name']); ?></h3>
    <div class="date">
        📅 <?php echo $e['event_date']; ?> | ⏰ <?php echo $e['event_time']; ?>
    </div>
    <a href="event_details.php?event_id=<?php echo $e['event_id']; ?>">
        View Details →
    </a>
</div>
<?php endforeach; ?>
<?php endif; ?>

<a href="devotee_dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>

