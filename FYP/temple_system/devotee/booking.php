<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");
    exit;
}

$userName = $_SESSION['user_name'] ?? "Devotee";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Make a Booking</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg: linear-gradient(135deg, #ff8a4a, #ff5f7c);
      --card:#fff;
      --soft:#faf7ff;
      --border:#eee6ff;
      --text:#2a2344;
      --muted:#7c7a90;
      --purple:#7d2cff;
      --pink:#ff4b8f;
    }
    *{box-sizing:border-box;font-family:Poppins,system-ui;margin:0;padding:0}
    body{min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;padding:28px 16px}
    .shell{max-width:980px;width:100%;background:var(--card);border-radius:28px;box-shadow:0 24px 60px rgba(0,0,0,.18);padding:26px}
    .top{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px}
    .title h1{color:var(--text);font-size:34px;line-height:1.1}
    .title p{color:var(--muted);font-size:13px;margin-top:6px}
    .btn{border:none;text-decoration:none;cursor:pointer;border-radius:999px;padding:10px 16px;font-size:13px;font-weight:600}
    .btn.back{background:#f3ecff;color:var(--purple)}
    .card{background:var(--soft);border:1px solid var(--border);border-radius:20px;padding:18px;margin-top:14px}
    label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px;font-weight:600}
    input,select,textarea{width:100%;padding:12px 12px;border-radius:14px;border:1px solid #eadfff;background:#fff;font-size:13px;outline:none}
    textarea{min-height:90px;resize:vertical}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:14px}
    .btn.primary{background:linear-gradient(135deg,var(--purple),var(--pink));color:#fff}
    .btn.ghost{background:#fff;border:1px solid #eadfff;color:var(--purple)}
    .hint{font-size:12px;color:var(--muted);margin-top:8px}
    @media(max-width:720px){.grid{grid-template-columns:1fr}.top{flex-direction:column;align-items:flex-start}}
  </style>
</head>
<body>
  <div class="shell">
    <div class="top">
      <div class="title">
        <h1>Make a Booking</h1>
        <p>Hello <b><?php echo htmlspecialchars($userName); ?></b> 👋 Please fill in your booking details.</p>
      </div>
      <a class="btn back" href="devotee_dashboard.php">← Back to Dashboard</a>
    </div>

    <form class="card" method="POST" action="booking_process.php">
      <div class="grid">
        <div>
          <label>Booking Type *</label>
          <select name="booking_type" required>
            <option value="">-- Select --</option>
            <option value="Ganapathi Homam">Ganapathi Homam</option>
            <option value="Abishegam">Abishegam</option>
            <option value="Temple Hall Booking">Temple Hall Booking</option>
            <option value="Special Pooja">Special Pooja</option>
          </select>
        </div>

        <div>
          <label>Pax (People)</label>
          <input type="number" name="pax" min="1" placeholder="e.g. 10">
        </div>

        <div>
          <label>Booking Date *</label>
          <input type="date" name="booking_date" required>
        </div>

        <div>
          <label>Booking Time</label>
          <input type="time" name="booking_time">
        </div>
      </div>

      <div style="margin-top:12px">
        <label>Note (Optional)</label>
        <textarea name="note" placeholder="Any extra request..."></textarea>
        <div class="hint">Status will start as <b>pending</b>. Admin/priest can confirm later if you want.</div>
      </div>

      <div class="actions">
        <a class="btn ghost" href="my_bookings.php">View My Bookings</a>
        <button class="btn primary" type="submit">Submit Booking</button>
      </div>
    </form>
  </div>
</body>
</html>
