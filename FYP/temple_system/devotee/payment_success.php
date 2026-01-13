<?php
session_start();

// Check if payment was successful
if (!isset($_SESSION['payment_success']) || $_SESSION['payment_success'] !== true) {
    header("Location: payment_form.php");
    exit();
}

// Get payment details from session
$payment_id = $_SESSION['payment_id'] ?? ''; 
$transaction_id = $_SESSION['transaction_id'] ?? '';
$receipt_number = $_SESSION['receipt_number'] ?? '';
$amount = $_SESSION['amount'] ?? 0;
$payment_type = $_SESSION['payment_type'] ?? '';
$payment_method = $_SESSION['payment_method'] ?? '';
$payment_date = $_SESSION['payment_date'] ?? date('Y-m-d H:i:s');

// Clear the session data after retrieving
unset($_SESSION['payment_success']);
unset($_SESSION['payment_id']);
unset($_SESSION['transaction_id']);
unset($_SESSION['receipt_number']);
unset($_SESSION['amount']);
unset($_SESSION['payment_type']);
unset($_SESSION['payment_method']);
unset($_SESSION['payment_date']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Sri Balathandayuthapani Temple</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #FF9933 0%, #FFFFFF 50%, #138808 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            animation: scaleIn 0.5s ease-in-out;
        }
        
        .success-icon svg {
            width: 50px;
            height: 50px;
            fill: #4CAF50;
        }
        
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .receipt-box {
            background: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FF6B35;
        }
        
        .receipt-header h2 {
            color: #FF6B35;
            font-size: 22px;
            margin-bottom: 5px;
        }
        
        .receipt-header p {
            color: #666;
            font-size: 14px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #FF6B35;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
            font-size: 15px;
        }
        
        .detail-value {
            color: #333;
            font-size: 15px;
            text-align: right;
        }
        
        .amount-paid {
            font-size: 26px;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .note-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .note-box p {
            color: #856404;
            font-size: 14px;
            margin: 0;
        }
        
        .buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a28;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-print {
            background: white;
            color: #FF6B35;
            border: 2px solid #FF6B35;
        }
        
        .btn-print:hover {
            background: #FF6B35;
            color: white;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 25px;
            color: #999;
            font-size: 13px;
        }
        
        @media print {
            body {
                background: white;
            }
            .buttons, .footer-text, .note-box {
                display: none;
            }
            .success-container {
                box-shadow: none;
            }
        }
        
        @media (max-width: 600px) {
            .buttons {
                flex-direction: column;
            }
            .content {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <!-- Header Section -->
        <div class="header">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                </svg>
            </div>
            <h1>Payment Successful!</h1>
            <p>Your transaction has been completed successfully</p>
        </div>
        
        <!-- Content Section -->
        <div class="content">
            <!-- Receipt Box -->
            <div class="receipt-box">
                <div class="receipt-header">
                    <h2>🕉️ Sri Balathandayuthapani Temple</h2>
                    <p>Payment Receipt</p>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Receipt Number:</span>
                    <span class="detail-value"><strong><?php echo htmlspecialchars($receipt_number); ?></strong></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($transaction_id); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Payment Type:</span>
                    <span class="detail-value"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $payment_type))); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $payment_method))); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Date & Time:</span>
                    <span class="detail-value"><?php echo date('d M Y, h:i A', strtotime($payment_date)); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Amount Paid:</span>
                    <span class="detail-value amount-paid">RM <?php echo number_format($amount, 2); ?></span>
                </div>
            </div>
            
            <!-- Note Box -->
            <div class="note-box">
                <p><strong>📧 Note:</strong> A confirmation email will be sent to your registered email address shortly. Please keep this receipt for your records.</p>
            </div>
            
            <!-- Action Buttons -->
            <div class="buttons">
                <button onclick="window.print()" class="btn btn-print">🖨️ Print Receipt</button>
                <a href="index.php" class="btn btn-secondary">🏠 Home</a>
                <a href="my_bookings.php" class="btn btn-primary">📋 My Bookings</a>
            </div>
            
            <p class="footer-text">
                Thank you for your contribution to Sri Balathandayuthapani Temple<br>
                May Lord Murugan bless you 🙏
            </p>
        </div>
    </div>
</body>

</html>
