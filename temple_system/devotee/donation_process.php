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
$payment_method = trim($_POST['payment_method'] ?? '');
$amount         = (float)($_POST['amount'] ?? 0);
$message        = trim($_POST['message'] ?? '');

if ($donation_type === '' || $payment_method === '' || $amount <= 0) {
  header("Location: donation.php?error=invalid");
  exit;
}

if (strlen($message) > 255) $message = substr($message, 0, 255);

// Receipt number
$receipt_no = "RCPT-" . date("YmdHis") . "-" . $user_id;

$stmt = $conn->prepare("
  INSERT INTO donations (user_id, donation_type, amount, payment_method, message, status, receipt_no)
  VALUES (?, ?, ?, ?, ?, 'paid', ?)
");
$stmt->bind_param("isdsss", $user_id, $donation_type, $amount, $payment_method, $message, $receipt_no);

if ($stmt->execute()) {
  $stmt->close();
  header("Location: my_donations.php?success=1");
  exit;
}

$stmt->close();
header("Location: donation.php?error=db");
exit;
