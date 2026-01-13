<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: devotee_login.php");
  exit;
}

$donation_id = (int)($_GET['donation_id'] ?? 0);

if (empty($donation_id)) {
  // Debug: show what we received
  echo "Error: No donation_id provided. Received: ";
  var_dump($_GET);
  exit;
  // Uncomment below and remove debug code above once fixed:
  // header("Location: donation.php");
  // exit;
}

// Get donation details
$stmt = $conn->prepare("SELECT * FROM donation WHERE donation_id = ? AND user_id = ?");
$stmt->bind_param("ii", $donation_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$donation = $result->fetch_assoc();
$stmt->close();

if (!$donation) {
  // Debug: show what we're searching for
  echo "Error: Donation not found. Looking for donation_id: $donation_id, user_id: " . $_SESSION['user_id'];
  echo "<br><br><a href='my_donations.php'>Back to My Donations</a>";
  exit;
  // Uncomment below and remove debug code above once fixed:
  // header("Location: donation.php");
  // exit;
}

// Generate receipt number if not exists
$receipt_no = "RCPT-" . str_pad($donation_id, 6, '0', STR_PAD_LEFT);

// Touch n Go account details
$account_number = '160170114355';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Instructions</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 20px;
    }
    
    .container {
      max-width: 700px;
      margin: 40px auto;
      background: white;
      border-radius: 16px;
      padding: 40px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    
    .success-icon {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .success-icon svg {
      width: 64px;
      height: 64px;
      fill: #10b981;
    }
    
    h1 {
      color: #1f2937;
      text-align: center;
      margin-bottom: 10px;
      font-size: 28px;
    }
    
    .subtitle {
      text-align: center;
      color: #6b7280;
      margin-bottom: 30px;
    }
    
    .donation-summary {
      background: #f9fafb;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 30px;
    }
    
    .donation-summary h3 {
      color: #374151;
      margin-bottom: 15px;
      font-size: 18px;
    }
    
    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid #e5e7eb;
    }
    
    .summary-row:last-child {
      border-bottom: none;
      padding-top: 15px;
      font-weight: 600;
      font-size: 18px;
      color: #7c3aed;
    }
    
    .summary-label {
      color: #6b7280;
    }
    
    .summary-value {
      color: #1f2937;
      font-weight: 500;
    }
    
    .bank-details {
      background: #eff6ff;
      border: 2px solid #3b82f6;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 25px;
    }
    
    .bank-details h3 {
      color: #1e40af;
      margin-bottom: 20px;
      font-size: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #bfdbfe;
    }
    
    .detail-row:last-child {
      border-bottom: none;
    }
    
    .detail-label {
      color: #1e40af;
      font-weight: 600;
    }
    
    .detail-value {
      color: #1f2937;
      font-family: 'Courier New', monospace;
      font-weight: 600;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .copy-btn {
      background: #3b82f6;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 12px;
      transition: background 0.3s;
    }
    
    .copy-btn:hover {
      background: #2563eb;
    }
    
    .instructions {
      background: #fef3c7;
      border-left: 4px solid #f59e0b;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 25px;
    }
    
    .instructions h4 {
      color: #92400e;
      margin-bottom: 15px;
      font-size: 16px;
    }
    
    .instructions ol {
      margin-left: 20px;
      color: #78350f;
    }
    
    .instructions li {
      margin-bottom: 10px;
      line-height: 1.6;
    }
    
    .important-note {
      background: #fee2e2;
      border-left: 4px solid #ef4444;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 25px;
    }
    
    .important-note p {
      color: #991b1b;
      font-weight: 500;
      margin: 0;
    }
    
    .button-group {
      display: flex;
      gap: 15px;
      margin-top: 30px;
    }
    
    .btn {
      flex: 1;
      padding: 14px 24px;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      text-align: center;
      text-decoration: none;
      display: inline-block;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
      background: white;
      color: #7c3aed;
      border: 2px solid #7c3aed;
    }
    
    .btn-secondary:hover {
      background: #7c3aed;
      color: white;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="success-icon">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
      </svg>
    </div>
    
    <h1>Payment Instructions</h1>
    <p class="subtitle">Please complete your payment to confirm your donation</p>
    
    <div class="donation-summary">
      <h3>📋 Donation Summary</h3>
      <div class="summary-row">
        <span class="summary-label">Receipt Number:</span>
        <span class="summary-value"><?php echo htmlspecialchars($receipt_no); ?></span>
      </div>
      <div class="summary-row">
        <span class="summary-label">Donation Type:</span>
        <span class="summary-value"><?php echo ucfirst(htmlspecialchars($donation['donation_type'])); ?></span>
      </div>
      <div class="summary-row">
        <span class="summary-label">Payment Method:</span>
        <span class="summary-value">Touch n Go</span>
      </div>
      <?php if (!empty($donation['donation_purpose'])): ?>
      <div class="summary-row">
        <span class="summary-label">Purpose:</span>
        <span class="summary-value"><?php echo htmlspecialchars($donation['donation_purpose']); ?></span>
      </div>
      <?php endif; ?>
      <div class="summary-row">
        <span class="summary-label">Amount to Pay:</span>
        <span class="summary-value">RM <?php echo number_format($donation['amount'], 2); ?></span>
      </div>
    </div>
    
    <div class="bank-details">
      <h3>💳 Touch n Go Payment Details</h3>
      <div class="detail-row">
        <span class="detail-label">Service:</span>
        <span class="detail-value">Touch n Go eWallet</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Account Name:</span>
        <span class="detail-value">Sri Balathandayuthapani Temple</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Account Number:</span>
        <span class="detail-value">
          <?php echo $account_number; ?>
          <button class="copy-btn" onclick="copyToClipboard('<?php echo $account_number; ?>')">Copy</button>
        </span>
      </div>
    </div>
    
    <div class="instructions">
      <h4>📝 Payment Instructions</h4>
      <ol>
        <li>Open your Touch n Go eWallet app</li>
        <li>Select "Transfer" or "Send Money"</li>
        <li>Enter the account number: <strong><?php echo $account_number; ?></strong></li>
        <li>Transfer the exact amount: <strong>RM <?php echo number_format($donation['amount'], 2); ?></strong></li>
        <li>Use your <strong>Receipt Number (<?php echo htmlspecialchars($receipt_no); ?>)</strong> in the notes/reference</li>
        <li>Take a screenshot of your payment confirmation</li>
        <li>Your donation status will be updated to "Completed" once verified by our admin team</li>
      </ol>
    </div>
    
    <div class="important-note">
      <p>⚠️ <strong>Important:</strong> Please include the receipt number "<?php echo htmlspecialchars($receipt_no); ?>" in your payment reference to ensure quick verification of your donation.</p>
    </div>
    
    <div class="button-group">
      <a href="my_donations.php" class="btn btn-primary">View My Donations</a>
      <a href="devotee_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
  </div>
  
  <script>
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard: ' + text);
      }, function(err) {
        alert('Could not copy text');
      });
    }
  </script>
</body>
</html>