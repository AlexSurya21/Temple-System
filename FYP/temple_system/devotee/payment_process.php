<?php
session_start();
require_once '../includes/db_connect.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data
    $user_id = $_POST['user_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    $payment_type = $_POST['payment_type'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $booking_id = $_POST['booking_id'] ?? null;
    $donation_id = $_POST['donation_id'] ?? null;
    
    // Validate required fields
    if (empty($amount) || empty($payment_type) || empty($payment_method)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: payment_form.php");
        exit();
    }
    
    // Validate amount
    if ($amount <= 0) {
        $_SESSION['error'] = "Invalid payment amount.";
        header("Location: payment_form.php");
        exit();
    }
    
    // Generate unique transaction ID
    $transaction_id = 'TXN' . date('Ymd') . time() . rand(100, 999);
    
    // Generate unique receipt number
    $receipt_number = 'RCP' . date('Ymd') . rand(1000, 9999);
    
    // Set payment status as completed (simulated payment)
    $payment_status = 'completed';
    
    // Get current date and time
    $payment_date = date('Y-m-d H:i:s');
    
    // Prepare SQL statement
    $sql = "INSERT INTO payments (user_id, booking_id, donation_id, amount, payment_type, 
            payment_method, payment_status, transaction_id, payment_date, receipt_number) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiidssssss", 
        $user_id,
        $booking_id,
        $donation_id,
        $amount, 
        $payment_type, 
        $payment_method,
        $payment_status,
        $transaction_id,
        $payment_date,
        $receipt_number
    );
    
    // Execute the statement
    if ($stmt->execute()) {
        // Get the inserted payment_id
        $payment_id = $conn->insert_id;
        
        // Payment successful - store details in session
        $_SESSION['payment_success'] = true;
        $_SESSION['payment_id'] = $payment_id;
        $_SESSION['transaction_id'] = $transaction_id;
        $_SESSION['receipt_number'] = $receipt_number;
        $_SESSION['amount'] = $amount;
        $_SESSION['payment_type'] = $payment_type;
        $_SESSION['payment_method'] = $payment_method;
        $_SESSION['payment_date'] = $payment_date;
        
        // Update booking status if it's a booking payment
        if ($payment_type == 'booking' && $booking_id) {
            $update_sql = "UPDATE bookings SET payment_status = 'paid', status = 'confirmed' WHERE booking_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $booking_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        // Update donation status if it's a donation payment
        if ($payment_type == 'donation' && $donation_id) {
            $update_sql = "UPDATE donations SET payment_status = 'paid' WHERE donation_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $donation_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        // Redirect to success page
        header("Location: payment_success.php");
        exit();
        
    } else {
        // Payment failed
        $_SESSION['error'] = "Payment processing failed: " . $conn->error;
        header("Location: payment_form.php");
        exit();
    }
    
    $stmt->close();
    $conn->close();
    
} else {
    // If not POST request, redirect to payment form
    header("Location: payment_form.php");
    exit();
}
?>