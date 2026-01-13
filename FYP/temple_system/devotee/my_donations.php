<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: devotee_login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

// Get all donations for this user - sorted by date descending
$stmt = $conn->prepare("
  SELECT * FROM donation 
  WHERE user_id = ?
  ORDER BY donation_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$donations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate total completed donations
$total_paid = 0;
foreach ($donations as $donation) {
  if ($donation['payment_status'] === 'completed') {
    $total_paid += $donation['amount'];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Donations</title>
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
      max-width: 1000px;
      margin: 40px auto;
      background: white;
      border-radius: 16px;
      padding: 40px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    
    .back-link {
      display: inline-block;
      color: #7c3aed;
      text-decoration: none;
      margin-bottom: 20px;
      font-weight: 500;
      transition: color 0.3s;
    }
    
    .back-link:hover {
      color: #6d28d9;
    }
    
    h1 {
      color: #1f2937;
      margin-bottom: 10px;
      font-size: 32px;
    }
    
    .subtitle {
      color: #6b7280;
      margin-bottom: 30px;
    }
    
    .summary-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 25px;
      border-radius: 12px;
      margin-bottom: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .summary-card h3 {
      font-size: 18px;
      margin-bottom: 10px;
      opacity: 0.9;
    }
    
    .summary-card .amount {
      font-size: 36px;
      font-weight: bold;
    }
    
    .success-message {
      background: #d1fae5;
      border-left: 4px solid #10b981;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    
    .success-message p {
      color: #065f46;
      margin: 0;
    }
    
    .donations-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    
    .donations-table thead {
      background: #f9fafb;
    }
    
    .donations-table th {
      padding: 15px;
      text-align: left;
      font-weight: 600;
      color: #374151;
      border-bottom: 2px solid #e5e7eb;
    }
    
    .donations-table td {
      padding: 15px;
      border-bottom: 1px solid #e5e7eb;
      color: #1f2937;
    }
    
    .donations-table tr:hover {
      background: #f9fafb;
    }
    
    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .status-pending {
      background: #fef3c7;
      color: #92400e;
    }
    
    .status-completed {
      background: #d1fae5;
      color: #065f46;
    }
    
    .view-payment-btn {
      background: #3b82f6;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      text-decoration: none;
      display: inline-block;
      transition: background 0.3s;
    }
    
    .view-payment-btn:hover {
      background: #2563eb;
    }
    
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #6b7280;
    }
    
    .empty-state svg {
      width: 80px;
      height: 80px;
      margin-bottom: 20px;
      opacity: 0.5;
    }
    
    .empty-state h3 {
      color: #374151;
      margin-bottom: 10px;
    }
    
    .btn {
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
      margin-top: 20px;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    
    @media (max-width: 768px) {
      .donations-table {
        font-size: 14px;
      }
      
      .donations-table th,
      .donations-table td {
        padding: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="devotee_dashboard.php" class="back-link">← Back to Dashboard</a>
    
    <h1>My Donations</h1>
    <p class="subtitle">View and track all your donations to the temple</p>
    
    <?php if (isset($_GET['success'])): ?>
      <div class="success-message">
        <p>✅ Donation recorded successfully! Please complete the payment to confirm your donation.</p>
      </div>
    <?php endif; ?>
    
    <div class="summary-card">
      <div>
        <h3>Total Confirmed Donations</h3>
        <div class="amount">RM <?php echo number_format($total_paid, 2); ?></div>
      </div>
      <div style="text-align: right;">
        <p style="opacity: 0.9;">Thank you for your support 🙏</p>
      </div>
    </div>
    
    <?php if (empty($donations)): ?>
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3>No Donations Yet</h3>
        <p>You haven't made any donations yet. Start supporting the temple today!</p>
        <a href="donation.php" class="btn btn-primary">Make a Donation</a>
      </div>
    <?php else: ?>
      <table class="donations-table">
        <thead>
          <tr>
            <th>Receipt No.</th>
            <th>Donation Type</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($donations as $donation): 
            $receipt_no = "RCPT-" . str_pad($donation['donation_id'], 6, '0', STR_PAD_LEFT);
          ?>
            <tr>
              <td><?php echo htmlspecialchars($receipt_no); ?></td>
              <td><?php echo ucfirst(htmlspecialchars($donation['donation_type'])); ?></td>
              <td><strong>RM <?php echo number_format($donation['amount'], 2); ?></strong></td>
              <td>
                <span class="status-badge status-<?php echo $donation['payment_status']; ?>">
                  <?php echo $donation['payment_status']; ?>
                </span>
              </td>
              <td><?php echo date('d M Y', strtotime($donation['donation_date'])); ?></td>
              <td>
                <?php if ($donation['payment_status'] === 'pending'): ?>
                  <a href="payment_instructions.php?donation_id=<?php echo $donation['donation_id']; ?>" class="view-payment-btn">
                    Complete Payment
                  </a>
                <?php else: ?>
                  <span style="color: #10b981; font-weight: 600;">✓ Completed</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>