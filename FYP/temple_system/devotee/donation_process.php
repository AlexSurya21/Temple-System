<?php
session_start();
require_once "../includes/db_connect.php"; 

if (!isset($_SESSION['user_id'])) {
  header("Location: devotee_login.php");
  exit;
}

if (!isset($_POST['donate'])) {
  header("Location: donation.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];

$donation_type  = trim($_POST['donation_type'] ?? '');
$amount         = (float)($_POST['amount'] ?? 0);
// Accept both 'donation_purpose' and 'message' for backwards compatibility
$donation_purpose = trim($_POST['donation_purpose'] ?? $_POST['message'] ?? '');

if ($donation_type === '' || $amount <= 0) {
  header("Location: donation.php?error=invalid");
  exit;
}

if (strlen($donation_purpose) > 500) $donation_purpose = substr($donation_purpose, 0, 500);

// Receipt number
$receipt_no = "RCPT-" . date("YmdHis") . "-" . $user_id;

// Insert donation with 'pending' status - will be updated to 'completed' after admin verification
$stmt = $conn->prepare("
  INSERT INTO donation (user_id, amount, donation_type, donation_purpose, payment_status, receipt_issued)
  VALUES (?, ?, ?, ?, 'pending', 0)
");
$stmt->bind_param("idss", $user_id, $amount, $donation_type, $donation_purpose);

if ($stmt->execute()) {
  $donation_id = $conn->insert_id;
  $stmt->close();
  
  // Redirect to payment instructions page
  header("Location: payment_instructions.php?donation_id=" . $donation_id);
  exit;
}

$stmt->close();
header("Location: donation.php?error=db");

exit;
