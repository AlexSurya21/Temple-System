<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? "Devotee";

// --------- detect columns safely ----------
function getTableColumns(mysqli $conn, string $table): array {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($res) {
        while ($r = $res->fetch_assoc()) $cols[] = $r['Field'];
    }
    return $cols;
}

$cols = getTableColumns($conn, "donation");

// ID column (needed for remove)
$idCol = in_array("donation_id", $cols) ? "donation_id" : (in_array("id", $cols) ? "id" : null);

// required
$userCol   = in_array("user_id", $cols) ? "user_id" : null;
$amountCol = in_array("amount", $cols) ? "amount" : null;
$typeCol   = in_array("donation_type", $cols) ? "donation_type" : (in_array("type", $cols) ? "type" : null);

// optional
$dateCol = null;
foreach (["created_at","donated_at","donation_date","date"] as $d) {
    if (in_array($d, $cols)) { $dateCol = $d; break; }
}

$noteCol = null;
foreach (["note","message","remarks","description"] as $n) {
    if (in_array($n, $cols)) { $noteCol = $n; break; }
}

$error = "";
$flash = "";

// --------- REMOVE donation ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {

    if (!$idCol || !$userCol) {
        $error = "❌ Cannot remove. Your donation table must have an ID column (donation_id or id) + user_id.";
    } else {
        $deleteId = (int)$_POST['delete_id'];

        $stmt = $conn->prepare("DELETE FROM donation WHERE $idCol = ? AND $userCol = ?");
        $stmt->bind_param("ii", $deleteId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) $flash = "✅ Donation record removed.";
        else $flash = "⚠️ Nothing removed (maybe already deleted / not yours).";

        $stmt->close();
    }
}

// --------- FETCH donations ----------
$rows = [];

if (!$userCol || !$amountCol || !$typeCol) {
    $error = "❌ Your donation table missing columns. Need at least: user_id, amount, donation_type/type.";
} else {

    $select = [];
    $select[] = $idCol ? "$idCol AS donation_id" : "0 AS donation_id";
    $select[] = "$typeCol AS donation_type";
    $select[] = "$amountCol AS amount";
    if ($noteCol) $select[] = "$noteCol AS note";
    if ($dateCol) $select[] = "$dateCol AS donated_at";

    $orderBy = "";
    if ($dateCol) $orderBy = "ORDER BY $dateCol DESC";
    else if ($idCol) $orderBy = "ORDER BY $idCol DESC";

    $sql = "SELECT " . implode(", ", $select) . " FROM donation WHERE $userCol = ? $orderBy";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;

    $stmt->close();
}

$total = 0;
foreach ($rows as $r) $total += (float)$r['amount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Donations</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root{
        --bg: linear-gradient(135deg,#ff8a4a,#ff5f7c);
        --card:#fff;
        --soft:#faf7ff;
        --border:#eee6ff;
        --purple:#7d2cff;
        --muted:#7c7a90;
        --danger:#ff5c5c;
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:Poppins,sans-serif;}
    body{min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;padding:30px 16px;}
    .shell{max-width:1100px;width:100%;background:var(--card);border-radius:28px;box-shadow:0 24px 60px rgba(0,0,0,.18);padding:28px 32px;}
    .top{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px;flex-wrap:wrap;}
    h1{font-size:34px;color:#2a2344;}
    .sub{margin-top:6px;color:var(--muted);font-size:13px;}
    .btnrow{display:flex;gap:10px;flex-wrap:wrap;}
    .pill{background:#f3ecff;border:1px solid var(--border);color:var(--purple);text-decoration:none;border-radius:999px;padding:10px 16px;font-size:13px;height:40px;display:inline-flex;align-items:center;gap:8px;}
    .summary{margin:14px 0 18px;background:var(--soft);border:1px solid var(--border);border-radius:18px;padding:14px 16px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;}
    .tablewrap{background:var(--soft);border:1px solid var(--border);border-radius:20px;overflow:hidden;}
    table{width:100%;border-collapse:collapse;}
    th,td{padding:12px 14px;text-align:left;font-size:13px;}
    th{background:#f3ecff;font-weight:600;}
    tr{border-bottom:1px solid #eadfff;}
    tr:last-child{border-bottom:none;}
    .muted{color:var(--muted);font-size:12px;}
    .empty{text-align:center;padding:22px;color:var(--muted);}
    .alert{padding:12px 14px;border-radius:14px;margin-bottom:12px;font-size:13px;}
    .ok{background:#e8fff3;border:1px solid #c9f5dc;color:#167a45;}
    .err{background:#ffecec;border:1px solid #ffd0d0;color:#b12b2b;}
    .btn-del{
        background:#ffecec;border:1px solid #ffd0d0;color:#b12b2b;
        padding:8px 12px;border-radius:999px;cursor:pointer;font-size:12px;font-weight:600;
    }
    .btn-del:hover{transform:translateY(-1px);}
</style>
</head>
<body>
<div class="shell">

    <div class="top">
        <div>
            <h1>My Donations</h1>
            <div class="sub">Hello <?php echo htmlspecialchars($userName); ?> 👋 Here is your donation history.</div>
        </div>
        <div class="btnrow">
            <a class="pill" href="donation.php">💗 Make Donation</a>
            <a class="pill" href="devotee_dashboard.php">← Dashboard</a>
        </div>
    </div>

    <?php if($error): ?><div class="alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if($flash): ?><div class="alert ok"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>

    <div class="summary">
        <div><strong>Total Records:</strong> <?php echo count($rows); ?></div>
        <div><strong>Total Donated:</strong> RM <?php echo number_format($total,2); ?></div>
    </div>

    <div class="tablewrap">
        <?php if(empty($rows)): ?>
            <div class="empty">No donations yet. Click “Make Donation” 💗</div>
        <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>Type</th>
                <th>Amount (RM)</th>
                <th>Note</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($rows as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['donation_type'] ?? "-"); ?></td>
                    <td><?php echo number_format((float)$r['amount'],2); ?></td>
                    <td class="muted"><?php echo htmlspecialchars($r['note'] ?? "-"); ?></td>
                    <td class="muted"><?php echo htmlspecialchars($r['donated_at'] ?? "-"); ?></td>
                    <td>
                        <?php if($idCol && !empty($r['donation_id'])): ?>
                        <form method="POST" style="margin:0" onsubmit="return confirm('Remove this donation record?');">
                            <input type="hidden" name="delete_id" value="<?php echo (int)$r['donation_id']; ?>">
                            <button class="btn-del" type="submit">✖ Remove</button>
                        </form>
                        <?php else: ?>
                            <span class="muted">No ID</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
