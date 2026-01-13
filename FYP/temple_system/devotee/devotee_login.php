<?php
// Sri Balathandayuthapani Temple System - Devotee Login

// Prevent caching (optional)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

session_start();

require_once("../includes/db_connect.php");   // uses temple_system_db

// If already logged in, go straight to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: devotee_dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter both email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        if (!$conn) {
            $error = "Database connection failed.";
        } else {
            // Look for active user with that email
            $sql  = "SELECT * FROM user WHERE email = ? LIMIT 1";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();

                    // password column is BCRYPT hash
                    if (password_verify($password, $row["password"])) {
                        // success – create session
                        $_SESSION["user_id"]        = $row["user_id"];
                        $_SESSION["user_name"]      = $row["full_name"];
                        $_SESSION["last_activity"]  = time();

                        header("Location: devotee_dashboard.php");
                        exit();
                    } else {
                        $error = "Invalid email or password.";
                    }
                } else {
                    $error = "Invalid email or password.";
                }

                $stmt->close();
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Devotee Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff7a18, #ffb347);
        }

        .card {
            background: #fff;
            width: 420px;
            max-width: 90vw;
            border-radius: 24px;
            padding: 40px 40px 30px;
            box-shadow: 0 18px 45px rgba(0,0,0,0.18);
            text-align: center;
        }

        .logo-circle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #a24ad8;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 44px;
            color: #fff;
        }

        h1 {
            font-size: 26px;
            color: #5a189a;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 14px;
            color: #636363;
            margin-bottom: 18px;
        }

        .error-msg {
            background: #fdecea;
            color: #c0392b;
            border: 1px solid #f5c6cb;
            padding: 8px 10px;
            font-size: 13px;
            border-radius: 8px;
            margin-bottom: 14px;
            text-align: left;
        }

        form {
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 4px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #a24ad8;
            box-shadow: 0 0 0 2px rgba(162, 74, 216, 0.15);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 40px; /* space for eye icon */
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 16px;
            color: #555;
            user-select: none;
        }

        .btn-primary {
            width: 100%;
            border: none;
            outline: none;
            border-radius: 999px;
            padding: 12px 0;
            margin-top: 4px;
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            background-image: linear-gradient(90deg, #a24ad8, #ff4b8b);
            box-shadow: 0 12px 25px rgba(162, 74, 216, 0.4);
            transition: transform 0.15s, box-shadow 0.15s, filter 0.15s;
        }

        .btn-primary:hover {
            filter: brightness(1.05);
            box-shadow: 0 16px 30px rgba(162, 74, 216, 0.55);
            transform: translateY(-1px);
        }

        .btn-primary:active {
            transform: translateY(1px);
            box-shadow: 0 8px 18px rgba(162, 74, 216, 0.3);
        }

        .back-link {
            display: inline-block;
            margin-top: 14px;
            font-size: 13px;
            color: #7b2cbf;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo-circle">ॐ</div>
    <h1>Devotee Login</h1>
    <div class="subtitle">Sign in to manage your bookings and class registrations.</div>

    <?php if (!empty($error)): ?>
        <div class="error-msg">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" required>

        <label for="password">Password</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="password" placeholder="Enter your password" required>
            <span class="toggle-password" onclick="togglePassword()">👁</span>
        </div>

     <button type="submit" class="btn-primary">Login to Devotee Portal</button>
     </form>

    <a href="devotee_register.php" class="back-link">Create new account</a>
    <br>
    <a href="../index.php" class="back-link">← Back to Home</a>
</div>

<script>
function togglePassword() {
    const pass = document.getElementById("password");
    pass.type = (pass.type === "password") ? "text" : "password";
}
</script>
</body>
</html>
