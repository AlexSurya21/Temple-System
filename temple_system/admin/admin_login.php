<?php
/**
 * ============================================================
 * Sri Balathandayuthapani Temple System
 * Admin Login Page
 * ============================================================
 */

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) 
{
    header("Location: admin_dashboard.php");
    exit();
}

// Initialize variables
$error = '';
$success = '';

// Check for messages
if (isset($_GET['timeout'])) 
{
    $error = 'Your session has expired. Please login again.';
}
if (isset($_GET['logout'])) 
{
    $success = 'You have been successfully logged out.';
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
    require_once('../includes/db_connect.php');
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) 
    {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
    {
        $error = 'Please enter a valid email address.';
    } 
    else 
    {
        $stmt = $conn->prepare("SELECT admin_id, admin_name, password, role FROM Admin WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) 
        {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password'])) 
            {
                // Login successful
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_name'] = $admin['admin_name'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['last_activity'] = time();
                
                header("Location: admin_dashboard.php");
                exit();
            } 
            else
            {
                $error = 'Invalid email or password.';
            }
        } 
        else 
        {
            $error = 'Invalid email or password.';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Sri Balathandayuthapani Temple</title>
    <style>
        * 
        {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body 
        {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container 
        {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }

        .login-header 
        {
            text-align: center;
            margin-bottom: 30px;
        }

        .temple-icon 
        {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .login-header h1 
        {
            color: #764ba2;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p 
        {
            color: #666;
            font-size: 14px;
        }

        .alert 
        {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error 
        {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success 
        {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group 
        {
            margin-bottom: 20px;
        }

        .form-group label 
        {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input 
        {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus 
        {
            outline: none;
            border-color: #764ba2;
            box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
        }

        .password-toggle
        {
            position: relative;
        }

        .password-toggle .toggle-btn 
        {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 14px;
            padding: 5px;
        }

        .password-toggle .toggle-btn:hover
        {
            color: #764ba2;
        }

        .login-btn 
        {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .login-btn:hover 
        {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(118, 75, 162, 0.4);
        }

        .form-footer 
        {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 13px;
        }

        @media (max-width: 480px) 
        {
            .login-container 
            {
                padding: 30px 20px;
            }

            .login-header h1 
            {
                font-size: 24px;
            }

            .temple-icon 
            {
                font-size: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="temple-icon">ॐ</div>
            <h1>Admin Login</h1>
            <p>Sri Balathandayuthapani Temple System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your email address" 
                    required
                    autocomplete="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-toggle">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="toggle-btn" onclick="togglePassword()">
                        <span id="toggleIcon">👁️</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn">Login to Dashboard</button>
        </form>

        <div class="form-footer">
            <p>© 2025 Sri Balathandayuthapani Temple</p>
        </div>
    </div>

    <script>
        function togglePassword() 
        {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') 
            {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else 
            {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }

        // Back button protection after logout
        if (window.location.search.includes('logout')) 
        {
            window.history.forward();
        }

        console.log('ॐ Admin Login System Loaded');
    </script>
</body>
</html>