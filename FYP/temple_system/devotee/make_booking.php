<?php
// devotee/make_booking.php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");  
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? "Devotee";

// booking types (you can change)
$bookingTypes = [
    "Abishegam",
    "Archana",
    "Homam",
    "Special Pooja",
    "Blessing Ceremony"
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Make a Booking</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg: linear-gradient(135deg,#ff8a4a,#ff5f7c);
      --card:#fff;
      --soft:#faf7ff;
      --border:#eee6ff;
      --text:#2a2344;
      --muted:#7c7a90;
      --purple:#7d2cff;
      --pink:#ff4b8f;
    }
    *{box-sizing:border-box;font-family:Poppins,sans-serif}
    body{margin:0;min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;padding:28px 16px}
    .shell{width:100%;max-width:1050px;background:var(--card);border-radius:28px;box-shadow:0 24px 60px rgba(0,0,0,.18);padding:28px 32px}
    .top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}
    h1{margin:0;color:var(--text);font-size:42px;letter-spacing:-.5px}
    .sub{margin:6px 0 0;color:var(--muted);font-size:14px}
    .pill{background:var(--soft);border:1px solid var(--border);padding:10px 14px;border-radius:999px;color:var(--purple);text-decoration:none;font-weight:600}
    .card{margin-top:18px;background:var(--soft);border:1px solid var(--border);border-radius:20px;padding:18px}
    .grid{display:grid;grid-template-columns:1.4fr .9fr;gap:14px}
    label{display:block;font-weight:600;color:var(--text);font-size:14px;margin:10px 0 6px}
    input,select,textarea{
      width:100%;padding:12px 14px;border-radius:14px;border:1px solid #e6dcff;background:#fff;
      outline:none;font-size:14px
    }
    textarea{min-height:110px;resize:vertical}
    .hint{color:var(--muted);font-size:12px;margin-top:10px}
    .actions{display:flex;justify-content:flex-end;gap:10px;margin-top:14px;flex-wrap:wrap}
    .btn{
      border:none;cursor:pointer;padding:12px 18px;border-radius:999px;font-weight:700;font-size:14px;text-decoration:none;
      display:inline-flex;align-items:center;gap:8px
    }
    .btn.primary{color:#fff;background:linear-gradient(135deg,var(--purple),var(--pink))}
    .btn.ghost{color:var(--purple);background:#fff;border:1px solid #e6dcff}
    .msg{margin-top:10px;padding:10px 12px;border-radius:14px;background:#fff;border:1px solid #e6dcff;color:var(--text);font-size:13px}
    @media(max-width:800px){.grid{grid-template-columns:1fr} h1{font-size:34px}}
  </style>
</head>
<body>
  <div class="shell">
    <div class="top">
      <div>
        <h1>Make a Booking</h1>
        <p class="sub">Hello <b><?= htmlspecialchars($userName) ?></b> 👋 Please fill in your booking details.</p>
      </div>
      <a class="pill" href="devotee_dashboard.php">← Back to Dashboard</a>
    </div>

    <div class="card">
      <form action="booking_process.php" method="POST">
        <div class="grid">
          <div>
            <label>Booking Type *</label>
            <select name="booking_type" required>
              <option value="" disabled selected>Choose booking type</option>
              <?php foreach($bookingTypes as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>Number of Guests *</label>
            <input type="number" name="number_of_guests" min="1" value="1" required>
          </div>

          <div>
            <label>Booking Date *</label>
            <input type="date" name="booking_date" required>
          </div>

          <div>
            <label>Booking Time *</label>
            <input type="time" name="booking_time" required>
          </div>

          <div style="grid-column:1/-1">
            <label>Special Requirement / Note (Optional)</label>
            <textarea name="special_requirement" placeholder="Any extra request..."></textarea>
            <div class="hint">Status will start as <b>pending</b>. Admin/priest can confirm later.</div>
          </div>
        </div>

        <div class="actions">
          <a class="btn ghost" href="my_bookings.php">📄 View My Bookings</a>
          <button class="btn primary" type="submit">📝 Submit Booking</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

