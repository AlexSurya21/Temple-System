<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Form - Sri Balathandayuthapani Temple</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .info-box {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #17a2b8;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>🕉️ Temple Payment</h1>
            <p>Sri Balathandayuthapani Temple</p>  
        </div>
        
        <?php
        session_start();
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        
        <div class="info-box">
            📝 <strong>Test Form:</strong> Fill in the details below to test the payment system
        </div>
        
        <form action="payment_process.php" method="POST">
            
            <div class="form-group">
                <label for="user_id">User ID (Optional)</label>
                <input type="number" id="user_id" name="user_id" placeholder="Enter user ID" value="1">
            </div>
            
            <div class="form-group">
                <label for="payment_type">Payment Type *</label>
                <select id="payment_type" name="payment_type" required>
                    <option value="">-- Select Payment Type --</option>
                    <option value="booking">Wedding/Priest Booking</option>
                    <option value="donation">Donation</option>
                    <option value="class_registration">Cultural Class Registration</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="amount">Amount (RM) *</label>
                <input type="number" id="amount" name="amount" step="0.01" min="1" placeholder="Enter amount" required value="100.00">
            </div>
            
            <div class="form-group">
                <label for="payment_method">Payment Method *</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="">-- Select Payment Method --</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="debit_card">Debit Card</option>
                    <option value="online_banking">Online Banking</option>
                    <option value="ewallet">E-Wallet</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="booking_id">Booking ID (Optional)</label>
                <input type="number" id="booking_id" name="booking_id" placeholder="Enter booking ID if applicable">
            </div>
            
            <div class="form-group">
                <label for="donation_id">Donation ID (Optional)</label>
                <input type="number" id="donation_id" name="donation_id" placeholder="Enter donation ID if applicable">
            </div>
            
            <button type="submit" class="btn-submit">💳 Process Payment</button>
            
        </form>
    </div>
</body>
</html>

