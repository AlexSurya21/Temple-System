<?php
// devotee/donation.php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? "Devotee";

$errors = [];
$success = "";

// ----------------------------------------------------
// Helper: get donation table columns (to avoid crashes)
// ----------------------------------------------------
function getTableColumns($conn, $tableName) {
    $cols = [];
    try {
        $res = $conn->query("SHOW COLUMNS FROM `$tableName`");
        while ($row = $res->fetch_assoc()) {
            $cols[] = $row['Field'];
        }
    } catch (Exception $e) {
        // table not found etc.
    }
    return $cols;
}

$donationCols = getTableColumns($conn, "donation");

// Detect common column names (change if yours different)
$idCol = in_array("donation_id", $donationCols) ? "donation_id" : (in_array("id", $donationCols) ? "id" : null);
$typeCol = in_array("donation_type", $donationCols) ? "donation_type" : (in_array("type", $donationCols) ? "type" : null);
$amountCol = in_array("amount", $donationCols) ? "amount" : null;
$userCol = in_array("user_id", $donationCols) ? "user_id" : null;

// Date column (we will use NOW() if exists & not auto)
$dateCol = null;
foreach (["created_at", "donated_at", "donation_date", "date"] as $dc) {
    if (in_array($dc, $donationCols)) { $dateCol = $dc; break; }
}

// Optional note/message column
$noteCol = null;
foreach (["note", "message", "remarks", "description"] as $nc) {
    if (in_array($nc, $donationCols)) { $noteCol = $nc; break; }
}

// Donation types (simple static list)
// If you have a donation_type table, tell me, I’ll make it dynamic.
$donationTypes = [
    "Temple Maintenance",
    "Special Pooja",
    "Annadhanam (Food Donation)",
    "Festival Fund",
    "Building Fund",
    "General Donation"
];

// ----------------------------------------------------
// Handle submit
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedType = trim($_POST['donation_type'] ?? "");
    $amount = trim($_POST['amount'] ?? "");
    $note = trim($_POST['note'] ?? "");

    if ($selectedType === "") $errors[] = "Please select donation type.";
    if ($amount === "" || !is_numeric($amount) || (float)$amount <= 0) $errors[] = "Please enter a valid amount (more than 0).";

    // Check must-have columns
    if (!$userCol || !$amountCol || !$typeCol) {
        $errors[] = "Your donation table columns are not matching. Need: user_id, amount, donation_type.";
    }

    if (empty($errors)) {
        try {
            // Build INSERT based on existing columns
            $fields = [];
            $placeholders = [];
            $types = "";
            $values = [];

            // user_id
            $fields[] = $userCol;
            $placeholders[] = "?";
            $types .= "i";
            $values[] = $userId;

            // donation_type
            $fields[] = $typeCol;
            $placeholders[] = "?";
            $types .= "s";
            $values[] = $selectedType;

            // amount
            $fields[] = $amountCol;
            $placeholders[] = "?";
            $types .= "d";
            $values[] = (float)$amount;

            // optional note
            if ($noteCol) {
                $fields[] = $noteCol;
                $placeholders[] = "?";
                $types .= "s";
                $values[] = $note;
            }

            // date column (if exists, we insert NOW() without placeholder)
            $dateSql = "";
            if ($dateCol) {
                $fields[] = $dateCol;
                $placeholders[] = "NOW()";
                // no bind for NOW()
            }

            $sql = "INSERT INTO donation (" . implode(",", $fields) . ") VALUES (" . implode(",", $placeholders) . ")";

            $stmt = $conn->prepare($sql);

            // Bind only real placeholders (not NOW())
            // Count placeholders that are "?"
            $qCount = 0;
            foreach ($placeholders as $ph) if ($ph === "?") $qCount++;

            if ($qCount > 0) {
                $stmt->bind_param($types, ...$values);
            }

            $stmt->execute();
            $stmt->close();

            $success = "Donation recorded successfully ✅";
        } catch (Exception $e) {
            $errors[] = "DB Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Make a Donation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg-gradient: linear-gradient(135deg, #ff8a4a, #ff5f7c);
      --card-bg:#ffffff;
      --purple:#7d2cff;
      --purple-soft:#f3ecff;
      --text-main:#2a2344;
      --muted:#7c7a90;
      --border-soft:#eee6ff;
      --danger:#ff5c5c;
      --success:#37c978;
    }

    *{box-sizing:border-box;margin:0;padding:0;font-family:"Poppins",sans-serif;}
    body{
      min-height:100vh;
      background:var(--bg-gradient);
      display:flex;
      justify-content:center;
      align-items:center;
      padding:32px 16px;
    }
    .shell{
      max-width:1000px;
      width:100%;
      background:var(--card-bg);
      border-radius:28px;
      box-shadow:0 24px 60px rgba(0,0,0,.18);
      padding:28px 32px;
    }
    .top{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:16px;
      margin-bottom:18px;
    }
    h1{
      font-size:34px;
      color:var(--text-main);
      line-height:1.1;
    }
    .sub{
      color:var(--muted);
      margin-top:8px;
      font-size:14px;
    }
    .back{
      text-decoration:none;
      background:var(--purple-soft);
      color:var(--purple);
      padding:10px 16px;
      border-radius:999px;
      font-size:13px;
      border:1px solid var(--border-soft);
      display:inline-flex;
      align-items:center;
      gap:8px;
      height:40px;
      white-space:nowrap;
    }
    .card{
      margin-top:18px;
      background:#faf7ff;
      border:1px solid var(--border-soft);
      border-radius:20px;
      padding:18px;
    }
    label{
      display:block;
      margin-bottom:8px;
      font-weight:600;
      color:var(--text-main);
      font-size:14px;
    }
    select,input,textarea{
      width:100%;
      padding:12px 14px;
      border-radius:14px;
      border:1px solid #e6dcff;
      outline:none;
      font-size:14px;
      background:#fff;
    }
    textarea{min-height:90px;resize:vertical;}
    .grid{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:14px;
      margin-top:12px;
    }
    .actions{
      margin-top:14px;
      display:flex;
      gap:10px;
      flex-wrap:wrap;
    }
    .btn{
      border:none;
      cursor:pointer;
      border-radius:999px;
      padding:12px 18px;
      font-weight:600;
      font-size:14px;
      transition:.15s;
    }
    .btn-primary{
      background:linear-gradient(135deg, #7d2cff, #ff4b8f);
      color:#fff;
      box-shadow:0 10px 22px rgba(125,44,255,.15);
    }
    .btn-primary:hover{transform:translateY(-1px);}
    .btn-ghost{
      background:#fff;
      color:var(--purple);
      border:1px solid var(--border-soft);
    }
    .msg{
      margin-top:12px;
      padding:12px 14px;
      border-radius:14px;
      font-size:13px;
    }
    .msg.err{background:#ffecec;color:#b12b2b;border:1px solid #ffd0d0;}
    .msg.ok{background:#e8fff3;color:#167a45;border:1px solid #c9f5dc;}
    @media(max-width:800px){ .grid{grid-template-columns:1fr;} h1{font-size:28px;} }
  </style>
</head>
<body>
  <div class="shell">
    <div class="top">
      <div>
        <h1>Make a Donation</h1>
        <div class="sub">Thank you for supporting the temple, <?php echo htmlspecialchars($userName); ?> 🙏</div>
      </div>
      <a class="back" href="devotee_dashboard.php">← Back to Dashboard</a>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="msg err">
        <strong>Please fix:</strong><br>
        <?php foreach ($errors as $e) echo "• " . htmlspecialchars($e) . "<br>"; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="msg ok">
        <?php echo htmlspecialchars($success); ?>
        &nbsp; <a href="my_donations.php" style="color:#167a45;font-weight:600;">View My Donations →</a>
      </div>
    <?php endif; ?>

    <div class="card">
      <form method="POST">
        <label>Donation Type *</label>
        <select name="donation_type" required>
          <option value="">-- Select donation type --</option>
          <?php foreach ($donationTypes as $t): ?>
            <option value="<?php echo htmlspecialchars($t); ?>"
              <?php echo (isset($_POST['donation_type']) && $_POST['donation_type'] === $t) ? "selected" : ""; ?>>
              <?php echo htmlspecialchars($t); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div class="grid">
          <div>
            <label>Amount (RM) *</label>
            <input type="number" step="0.01" min="1" name="amount" placeholder="e.g. 50" value="<?php echo htmlspecialchars($_POST['amount'] ?? ""); ?>" required>
          </div>

          <div>
            <label>Note (optional)</label>
            <input type="text" name="note" placeholder="e.g. For temple maintenance" value="<?php echo htmlspecialchars($_POST['note'] ?? ""); ?>">
          </div>
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-primary">Donate Now</button>
          <a class="btn btn-ghost" href="my_donations.php" style="text-decoration:none;display:inline-flex;align-items:center;">My Donations</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
