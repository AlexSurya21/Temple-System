<?php
// devotee/my_bookings.php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? "Devotee";

// ✅ fetch bookings (latest first)
$sql = "SELECT booking_id, booking_type, booking_date, booking_time, booking_status, special_requirements
        FROM booking
        WHERE user_id = ?
        ORDER BY booking_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$bookings = [];
$pendingCount = 0;
$approvedCount = 0;   

while ($row = $res->fetch_assoc()) {
    $bookings[] = $row;

    $st = strtolower(trim($row['booking_status'] ?? 'pending'));
    if ($st === 'pending') $pendingCount++;
    if ($st === 'approved' || $st === 'confirmed') $approvedCount++;
}

$totalRecords = count($bookings);

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function badgeClass($status) {
    $status = strtolower(trim($status ?? 'pending'));
    return match ($status) {
        'approved', 'confirmed' => 'badge green',
        'rejected', 'cancelled' => 'badge red',
        default => 'badge purple'
    };
}

function prettyStatus($status) {
    $status = strtolower(trim($status ?? 'pending'));
    return ucfirst($status);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings</title>
<style>
    :root{
        --bg1:#ff7a59; --bg2:#ff5f8f;
        --card:#ffffff;
        --soft:#f6f1ff;
        --text:#1f1b2e;
        --muted:#6b647a;
        --stroke:rgba(93,63,211,.18);
        --purple:#7c3aed;
        --pink:#ec4899;
        --green:#16a34a;
        --red:#ef4444;
    }
    *{box-sizing:border-box; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;}
    body{
        margin:0;
        min-height:100vh;
        background:linear-gradient(120deg,var(--bg1),var(--bg2));
        padding:40px 14px;
        color:var(--text);
    }
    .shell{
        max-width:1180px;
        margin:0 auto;
        background:rgba(255,255,255,.22);
        border:1px solid rgba(255,255,255,.35);
        border-radius:34px;
        padding:26px;
        backdrop-filter: blur(10px);
    }
    .panel{
        background:var(--card);
        border-radius:28px;
        padding:28px;
        box-shadow:0 18px 50px rgba(0,0,0,.10);
    }

    .topbar{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:18px;
        flex-wrap:wrap;
    }
    .title{
        font-size:64px;
        line-height:1;
        margin:0;
        letter-spacing:-1px;
    }
    .subtitle{
        margin:10px 0 0 0;
        color:var(--muted);
        font-size:18px;
    }
    .actions{
        display:flex;
        gap:12px;
        align-items:center;
        margin-top:10px;
        flex-wrap:wrap;
    }
    .btn{
        display:inline-flex;
        align-items:center;
        gap:10px;
        padding:14px 20px;
        border-radius:999px;
        font-weight:700;
        text-decoration:none;
        border:1px solid rgba(124,58,237,.25);
        background:#fff;
        color:var(--purple);
        transition:.15s;
    }
    .btn:hover{ transform: translateY(-1px); }
    .btn.primary{
        background:linear-gradient(90deg,var(--purple),var(--pink));
        color:#fff;
        border:none;
        box-shadow:0 12px 30px rgba(124,58,237,.25);
    }

    .stats{
        display:grid;
        grid-template-columns: repeat(3, minmax(0,1fr));
        gap:14px;
        margin-top:18px;
    }
    .stat{
        border:1px solid var(--stroke);
        background:linear-gradient(180deg, #faf7ff, #ffffff);
        border-radius:18px;
        padding:16px 18px;
    }
    .stat .label{ color:var(--muted); font-weight:700; font-size:13px; }
    .stat .value{ font-size:26px; font-weight:900; margin-top:6px; letter-spacing:-.5px; }
    .stat .value.purple{ color:var(--purple); }
    .stat .value.green{ color:var(--green); }
    .stat .value.orange{ color:#f97316; }

    .card{
        margin-top:18px;
        border:1px solid var(--stroke);
        background:var(--soft);
        border-radius:20px;
        overflow:hidden;
    }
    .cardHead{
        display:flex;
        justify-content:space-between;
        align-items:center;
        padding:16px 18px;
        border-bottom:1px solid var(--stroke);
        background:rgba(255,255,255,.45);
    }
    .cardHead strong{ font-size:16px; }
    .cardBody{ padding:16px 18px; }

    table{
        width:100%;
        border-collapse:separate;
        border-spacing:0;
        overflow:hidden;
        border-radius:18px;
        background:#fff;
        border:1px solid var(--stroke);
    }
    thead th{
        text-align:left;
        padding:16px 18px;
        font-size:14px;
        color:#2a2440;
        background:rgba(124,58,237,.08);
        border-bottom:1px solid var(--stroke);
    }
    tbody td{
        padding:16px 18px;
        border-bottom:1px solid rgba(93,63,211,.10);
        color:#2a2440;
        vertical-align:middle;
    }
    tbody tr:last-child td{ border-bottom:none; }

    .badge{
        display:inline-flex;
        align-items:center;
        padding:8px 14px;
        border-radius:999px;
        font-weight:800;
        font-size:14px;
        border:1px solid rgba(124,58,237,.25);
        background:rgba(124,58,237,.08);
        color:var(--purple);
    }
    .badge.green{
        border-color:rgba(22,163,74,.25);
        background:rgba(22,163,74,.10);
        color:var(--green);
    }
    .badge.red{
        border-color:rgba(239,68,68,.25);
        background:rgba(239,68,68,.10);
        color:var(--red);
    }

    .muted{ color:var(--muted); }

    .emptyWrap{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:18px;
        flex-wrap:wrap;
        border:1px dashed rgba(124,58,237,.35);
        background:linear-gradient(180deg, #fbf7ff, #ffffff);
        border-radius:18px;
        padding:18px;
    }
    .emptyTitle{
        font-size:18px;
        font-weight:900;
        margin:0 0 6px 0;
        letter-spacing:-.2px;
    }
    .emptyText{
        margin:0;
        color:var(--muted);
        line-height:1.4;
    }

    .removeBtn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        padding:10px 14px;
        border-radius:14px;
        font-weight:900;
        text-decoration:none;
        color:#b91c1c;
        background:rgba(239,68,68,.10);
        border:1px solid rgba(239,68,68,.25);
    }

    @media (max-width:820px){
        .title{ font-size:44px; }
        .stats{ grid-template-columns: 1fr; }
        thead{ display:none; }
        table, tbody, tr, td{ display:block; width:100%; }
        tbody td{ border-bottom:none; padding:10px 12px; }
        tbody tr{ border-bottom:1px solid rgba(93,63,211,.10); padding:10px 0; }
        tbody tr:last-child{ border-bottom:none; }
        tbody td::before{
            content: attr(data-label);
            display:block;
            font-size:12px;
            color:var(--muted);
            font-weight:800;
            margin-bottom:4px;
        }
    }
</style>
</head>
<body>

<div class="shell">
  <div class="panel">

    <div class="topbar">
      <div>
        <h1 class="title">My Bookings</h1>
        <p class="subtitle">Hello <b><?= h($user_name) ?></b> 👋 Here is your booking history.</p>
      </div>

      <div class="actions">
        <a class="btn primary" href="booking.php">📝 Make Booking</a>
        <a class="btn" href="devotee_dashboard.php">← Dashboard</a>
      </div>
    </div>

    <div class="stats">
      <div class="stat">
        <div class="label">TOTAL RECORDS</div>
        <div class="value purple"><?= $totalRecords ?></div>
      </div>
      <div class="stat">
        <div class="label">PENDING</div>
        <div class="value orange"><?= $pendingCount ?></div>
      </div>
      <div class="stat">
        <div class="label">APPROVED</div>
        <div class="value green"><?= $approvedCount ?></div>
      </div>
    </div>

    <div class="card">
      <div class="cardHead">
        <strong>Booking List</strong>
        <span class="muted">Sorted by newest first</span>
      </div>
      <div class="cardBody">

        <?php if ($totalRecords === 0): ?>
          <div class="emptyWrap">
            <div>
              <p class="emptyTitle">No bookings yet ✨</p>
              <p class="emptyText">When you create a booking, it will appear here with status and your note.</p>
            </div>
            <a class="btn primary" href="booking.php">➕ Make your first booking</a>
          </div>

        <?php else: ?>

          <table>
            <thead>
              <tr>
                <th>Type</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Note</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $b): ?>
              <?php
                $type = trim($b['booking_type'] ?? '');
                $note = trim($b['special_requirements'] ?? '');
                $typeDisplay = ($type !== '') ? $type : "—";
                $noteDisplay = ($note !== '') ? $note : "—";
                $status = $b['booking_status'] ?? 'pending';
              ?>
              <tr>
                <td data-label="Type"><?= h($typeDisplay) ?></td>
                <td data-label="Date"><?= h($b['booking_date'] ?? '—') ?></td>
                <td data-label="Time"><?= h($b['booking_time'] ?? '—') ?></td>
                <td data-label="Status">
                  <span class="<?= badgeClass($status) ?>"><?= h(prettyStatus($status)) ?></span>
                </td>
                <td data-label="Note"><?= h($noteDisplay) ?></td>
                <td data-label="Action">
                  <a class="removeBtn" href="remove_booking.php?id=<?= (int)$b['booking_id'] ?>"
                     onclick="return confirm('Remove this booking?');">✖ Remove</a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>

        <?php endif; ?>

      </div>
    </div>

  </div>
</div>

</body>
</html>

