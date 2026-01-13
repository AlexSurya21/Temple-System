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

// 1) Check current status of this registration
$stmt = $conn->prepare("
    SELECT registration_status
    FROM class_registration
    WHERE user_id = ? AND class_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $user_id, $class_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: my_classes.php?info=not_found");
    exit;
}

$status = strtolower($row['registration_status'] ?? '');

// ✅ If already cancelled -> delete from history
if ($status === 'cancelled') {
    $stmt = $conn->prepare("
        DELETE FROM class_registration
        WHERE user_id = ? AND class_id = ? AND registration_status = 'cancelled'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $user_id, $class_id);
    $stmt->execute();
    $stmt->close();

    header("Location: my_classes.php?info=deleted_from_history");
    exit;
}

// ✅ If active -> cancel and reduce students
if ($status === 'active') {
    $conn->begin_transaction();

    try {
        // cancel
        $stmt = $conn->prepare("
            UPDATE class_registration
            SET registration_status = 'cancelled'
            WHERE user_id = ? AND class_id = ? AND registration_status = 'active'
        ");
        $stmt->bind_param("ii", $user_id, $class_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            throw new Exception("Already cancelled.");
        }
        $stmt->close();

        // decrease count
        $stmt = $conn->prepare("
            UPDATE cultural_class
            SET current_students = IF(current_students > 0, current_students - 1, 0)
            WHERE class_id = ?
        ");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        header("Location: my_classes.php?info=cancelled");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: my_classes.php?info=error");
        exit;
    }
}

// fallback
header("Location: my_classes.php?info=unknown_status");
exit;

