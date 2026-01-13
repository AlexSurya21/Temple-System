<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: devotee_login.php");
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

if ($class_id <= 0) {
    die("Invalid class id");
}

$conn->begin_transaction();

try {
    // 1) Check registration exists and still active
    $stmt = $conn->prepare("
        SELECT registration_status
        FROM class_registration
        WHERE user_id = ? AND class_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $user_id, $class_id);
    $stmt->execute();
    $reg = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reg) {
        throw new Exception("You are not registered for this class.");
    }

    if (strtolower($reg['registration_status']) !== 'active') {
        throw new Exception("This registration is already cancelled.");
    }

    // 2) Update registration status -> cancelled
    $stmt = $conn->prepare("
        UPDATE class_registration
        SET registration_status = 'cancelled'
        WHERE user_id = ? AND class_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $class_id);
    $stmt->execute();
    $stmt->close();

    // 3) Decrease current_students but never below 0
    $stmt = $conn->prepare("
        UPDATE cultural_class
        SET current_students = GREATEST(current_students - 1, 0)
        WHERE class_id = ?
    ");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    header("Location: my_classes.php?msg=cancelled");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}
