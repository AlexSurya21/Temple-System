<?php
session_start();
require_once "../includes/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT priest_id, priest_name, password FROM priest WHERE username = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['priest_id'] = $row['priest_id'];
                $_SESSION['priest_name'] = $row['priest_name'];
                header("Location: priest_dashboard.php");
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No priest found with that username.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Priest Login</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #ff4d4d 0%, #cc0000 50%, #990000 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #ff4d4d, #cc0000);
            color: white;
            text-align: center;
            padding: 40px 20px;
        }
        .login-header h1 { font-size: 32px; margin-bottom: 10px; }
        .login-header p { font-size: 16px; opacity: 0.9; }
        .login-form { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input {
            width: 100%; padding: 12px; border: 2px solid #e0e0e0;
            border-radius: 8px; font-size: 15px; transition: 0.3s;
        }
        .form-group input:focus {
            border-color: #cc0000; box-shadow: 0 0 0 3px rgba(204,0,0,0.1);
        }
        .btn-login {
            width: 100%; padding: 15px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, #ff4d4d, #cc0000);
            color: white; font-weight: bold; cursor: pointer;
            transition: 0.3s; box-shadow: 0 4px 15px rgba(204,0,0,0.3);
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #cc0000, #990000);
            transform: translateY(-2px);
        }
        .error-msg {
            margin-top: 15px; padding: 10px; border-radius: 8px;
            background: #fee; border: 1px solid #fcc; color: #c33;
            text-align: center;
        }
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
<div class="login-container">
    <div class="login-header">
        <h1>Priest Login</h1>
        <p>Access your dashboard</p>
    </div>
    <div class="login-form">
        <form method="post">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="footer-links">
            <a href="../index.php" class="btn-home">← Back to Home</a>
        </div>
    </div>
</div>
</body>


</html>
