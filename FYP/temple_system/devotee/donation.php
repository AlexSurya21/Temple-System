<?php
session_start();
require_once "../includes/db_connect.php"; 

if (!isset($_SESSION['user_id'])) {
  header("Location: devotee_login.php");
  exit;
}

// Get user info - change 'devotees' and 'name' to match your actual table and column names
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Devotee'; // Fallback if name not in session

// Uncomment and adjust this if you need to fetch from database:
/*
$stmt = $conn->prepare("SELECT name FROM devotees WHERE devotee_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$user_name = $user['name'] ?? 'Devotee';
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Make a Donation</title>
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
    
    h1 {
      color: #1f2937;
      margin-bottom: 10px;
      font-size: 32px;
    }
    
    .subtitle {
      color: #6b7280;
      margin-bottom: 30px;
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
    
    .form-group {
      margin-bottom: 25px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #374151;
      font-weight: 600;
      font-size: 14px;
    }
    
    .form-group select,
    .form-group input[type="number"],
    .form-group input[type="text"] {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      font-size: 16px;
      transition: border-color 0.3s;
      font-family: inherit;
    }
    
    .form-group select:focus,
    .form-group input:focus {
      outline: none;
      border-color: #7c3aed;
    }
    
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    
    .info-box {
      background: #eff6ff;
      border-left: 4px solid #3b82f6;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 25px;
    }
    
    .info-box p {
      color: #1e40af;
      font-size: 14px;
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
    
    .error-message {
      background: #fee2e2;
      border-left: 4px solid #ef4444;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    
    .error-message p {
      color: #991b1b;
      margin: 0;
    }
    
    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }
      
      .button-group {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="devotee_dashboard.php" class="back-link">← Back to Dashboard</a>
    
    <h1>Make a Donation</h1>
    <p class="subtitle">Thank you for supporting the temple, <?php echo htmlspecialchars($user_name); ?> 🙏</p>
    
    <?php if (isset($_GET['error'])): ?>
      <div class="error-message">
        <p>
          <?php 
            if ($_GET['error'] === 'invalid') {
              echo '⚠️ Please fill in all required fields correctly.';
            } elseif ($_GET['error'] === 'db') {
              echo '⚠️ An error occurred. Please try again.';
            }
          ?>
        </p>
      </div>
    <?php endif; ?>
    
    <div class="info-box">
      <p>💡 After submitting this form, you will be redirected to payment instructions with Touch n Go account details to complete your donation.</p>
    </div>
    
    <form method="POST" action="donation_process.php">
      <div class="form-group">
        <label for="donation_type">Donation Type *</label>
        <select id="donation_type" name="donation_type" required>
          <option value="">Select Donation Type</option>
          <option value="general">General Donation</option>
          <option value="festival">Festival Celebration</option>
          <option value="maintenance">Temple Maintenance</option>
          <option value="annadana">Annadhanam (Food Service)</option>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="amount">Amount (RM) *</label>
          <input type="number" id="amount" name="amount" min="1" step="0.01" required placeholder="50">
        </div>

        <div class="form-group">
          <label for="donation_purpose">Purpose (optional)</label>
          <input type="text" id="donation_purpose" name="donation_purpose" maxlength="500" placeholder="e.g. For temple maintenance">
        </div>
      </div>

      <!-- Hidden field for payment method - always Touch n Go -->
      <input type="hidden" name="payment_method" value="touch_n_go">

      <div class="button-group">
        <button type="submit" name="donate" class="btn btn-primary">Proceed to Payment</button>
        <a href="my_donations.php" class="btn btn-secondary">My Donations</a>
      </div>
    </form>
  </div>
</body>

</html>
