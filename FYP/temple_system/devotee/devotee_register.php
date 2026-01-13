<?php
session_start();
require_once "../includes/db_connect.php";
$error = "";
$success = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST["full_name"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $phone    = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    if ($name === "" || $email === "" || $phone === "" || $password === "") {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } elseif (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $error = "Please enter a valid phone number (10-15 digits).";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // check email already exists
        $stmt = $conn->prepare("SELECT devotee_id FROM devotee WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = $res->num_rows > 0;
        $stmt->close();
        if ($exists) {
            $error = "This email is already registered. Please login.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO devotee (full_name, email, phone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $phone);
            if ($stmt->execute()) {
                $success = "Registration successful! You can login now.";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Devotee Register</title>
  <style>
    body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#ff7a18,#ffb347);font-family:system-ui}
    .card{background:#fff;width:420px;max-width:90vw;border-radius:24px;padding:40px;box-shadow:0 18px 45px rgba(0,0,0,.18)}
    h1{color:#5a189a;margin:0 0 8px}
    p{color:#666;margin:0 0 18px}
    label{display:block;margin:10px 0 5px;font-weight:600;color:#555;font-size:13px}
    input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;font-size:14px}
    .btn{margin-top:16px;width:100%;border:none;border-radius:999px;padding:12px;font-weight:700;color:#fff;cursor:pointer;background:linear-gradient(90deg,#a24ad8,#ff4b8b);box-shadow:0 12px 25px rgba(162,74,216,.4)}
    .msg{margin:10px 0;padding:10px;border-radius:10px;font-size:13px}
    .err{background:#fdecea;color:#c0392b;border:1px solid #f5c6cb}
    .ok{background:#e9fff2;color:#1e7e34;border:1px solid #b7ebc6}
    a{display:block;margin-top:14px;color:#7b2cbf;text-decoration:none;font-size:13px;text-align:center}
    a:hover{text-decoration:underline}
  </style>
</head>
<body>
  <div class="card">
    <h1>Create Account</h1>
    <p>Register first, then login using your Gmail.</p>
    <?php if ($error): ?><div class="msg err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg ok"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <form method="post">
      <label>Full Name</label>
      <input type="text" name="full_name" required>
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Phone Number</label>
      <input type="tel" name="phone" pattern="[0-9]{10,15}" placeholder="e.g., 0123456789" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <button class="btn" type="submit">Register</button>
    </form>
    <a href="devotee_login.php">Already have account? Login</a>
  </div>
</body>
</html>