<?php
/**
 * ============================================================
 * Sri Balathandayuthapani Temple System
 * Database Connection Configuration File
 * ============================================================
 */

// ============================================================
// DATABASE CONFIGURATION
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'temple_system_db');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// ESTABLISH DATABASE CONNECTION
// ============================================================

$conn = null;

try 
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset(DB_CHARSET);
} 
catch (mysqli_sql_exception $e) 
{
    error_log("Database Connection Failed: " . $e->getMessage());
    
    die("
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Database Connection Error</title>
        <style>
            body 
            {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .error-container 
            {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 500px;
            }
            .error-icon 
            {
                font-size: 60px;
                color: #e74c3c;
                margin-bottom: 20px;
            }
            h2 
            {
                color: #2c3e50;
                margin-bottom: 15px;
            }
            p 
            {
                color: #7f8c8d;
                line-height: 1.6;
            }
            .error-code 
            {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
                font-family: 'Courier New', monospace;
                color: #e74c3c;
                font-size: 14px;
            }
            .help-text 
            {
                margin-top: 20px;
                font-size: 14px;
                color: #95a5a6;
            }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <div class='error-icon'>⚠️</div>
            <h2>Database Connection Error</h2>
            <p>Unable to connect to the temple management system database.</p>
            <div class='error-code'>
                Error: " . htmlspecialchars($e->getMessage()) . "
            </div>
            <div class='help-text'>
                <strong>For Developers:</strong><br>
                1. Make sure XAMPP is running<br>
                2. Check MySQL service is started<br>
                3. Verify database credentials in db_connect.php<br>
                4. Ensure 'temple_system_db' database exists
            </div>
        </div>
    </body>
    </html>
    ");
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Safely escape strings to prevent SQL injection
 */
function escape_string($data) 
{
    global $conn;
    return $conn->real_escape_string(trim($data));
}

/**
 * Sanitize user input to prevent XSS attacks
 */
function sanitize_input($data) 
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Execute prepared statement (secure method)
 */
function execute_prepared_statement($query, $types, $params) 
{
    global $conn;
    try 
    {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt;
    } 
    catch (mysqli_sql_exception $e) 
    {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in
 */
function is_logged_in($user_type = 'admin') 
{
    return isset($_SESSION[$user_type . '_id']) && !empty($_SESSION[$user_type . '_id']);
}

/**
 * Redirect to login if not authenticated
 */
function require_login($user_type = 'admin', $login_page = 'admin_login.php') 
{
    if (!is_logged_in($user_type)) 
    {
        header("Location: $login_page");
        exit();
    }
}

/**
 * Close database connection
 */
function close_connection() 
{
    global $conn;
    if ($conn) 
    {
        $conn->close();
    }
}

// ============================================================
// CONFIGURATION
// ============================================================

// Timezone setting (Malaysia)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Error reporting (TURN OFF in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================================
// END OF FILE
// ============================================================
?>