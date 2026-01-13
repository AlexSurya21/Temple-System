<?php
session_start();
require_once "../includes/db_connect.php";

// Initialize messages
$success = "";
$error = "";
 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Basic sanitization
    $name = trim($_POST['priest_name'] ?? "");
    $username = trim($_POST['username'] ?? "");
    $passwordRaw = $_POST['password'] ?? "";

    // Validate inputs
    if ($name === "" || $username === "" || $passwordRaw === "") {
        $error = "Please fill in all fields.";
    } else {
        // Check if username already exists
        $checkSql = "SELECT priest_id FROM priest WHERE username = ?";
        if ($checkStmt = $conn->prepare($checkSql)) {
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $error = "Username is already taken. Please choose another.";
            } else {
                // Create hashed password
                $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

                // Insert priest
                $sql = "INSERT INTO priest (priest_name, username, password) VALUES (?, ?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("sss", $name, $username, $password);
                    if ($stmt->execute()) {
                        $success = "Registration successful! You can now log in.";
                    } else {
                        $error = "Error registering priest. Please try again.";
                    }
                    $stmt->close();
                } else {
                    $error = "Failed to prepare registration query.";
                }
            }
            $checkStmt->close();
        } else {
            $error = "Failed to prepare username check.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Priest Register</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #ff4d4d 0%, #cc0000 50%, #990000 100%);
            min-height: 100vh;
            display: flex; justify-content: center; align-items: center;
            padding: 20px;
        }
        .register-container {
            background: white; border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px; width: 100%; overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #ff4d4d, #cc0000);
            color: white; text-align: center; padding: 30px;
        }
        .register-header h1 { margin: 0; font-size: 28px; }
        .register-form { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input {
            width: 100%; padding: 12px; border: 2px solid #e0e0e0;
            border-radius: 8px; font-size: 15px; transition: 0.3s;
        }
        .form-group input:focus {
            border-color: #cc0000; box-shadow: 0 0 0 3px rgba(204,0,0,0.1);
            outline: none;
        }
        .btn-register {
            width: 100%; padding: 15px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, #ff4d4d, #cc0000);
            color: white; font-weight: bold; cursor: pointer;
            transition: 0.3s; box-shadow: 0 4px 15px rgba(204,0,0,0.3);
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #cc0000, #990000);
            transform: translateY(-2px);
        }
        .msg { margin-top: 15px; text-align: center; font-weight: 600; }
        .msg.success { color: #27ae60; }
        .msg.error { color: #c33; }
        .footer-links { margin-top: 20px; text-align: center; }
        .btn-home {
            display: inline-block; padding: 10px 15px;
            background: #fff3f3; color: #b71c1c;
            border-radius: 8px; text-decoration: none; font-weight: 600;
            transition: 0.3s;
        }
        .btn-home:hover { background: #ffe5e5; }
    </style>
</head>
<body>
<div class="register-container">
    <div class="register-header">
        <h1>Priest Registration</h1>
    </div>
    <div class="register-form">
        <form method="post" action="">
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="priest_name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-register">Register</button>
        </form>

        <?php if (!empty($success)): ?>
            <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="footer-links">
            <a href="../index.php" class="btn-home">← Back to Home</a>
        </div>
    </div>
</div>
</body>

</html>
