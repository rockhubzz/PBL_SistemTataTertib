<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_key'])) {
    header("Location: login.php"); // Redirect to login if not authenticated
    exit();
}

// Include database configuration
$config = parse_ini_file('db_config.ini');

// Extract connection details
$serverName = $config['serverName'];
$connectionInfo = array(
    "Database" => $config['database'],
    "UID" => $config['username'],
    "PWD" => $config['password']
);
$conn = sqlsrv_connect($serverName, $connectionInfo);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

// Initialize variables
$user_key = $_SESSION['user_key'];
$message = "";

// Fetch current username
$current_username = "";
$query = "SELECT username FROM dbo.Users WHERE user_id = ?";
$stmt = sqlsrv_query($conn, $query, array($user_key));
if ($stmt && sqlsrv_has_rows($stmt)) {
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $current_username = $row['username'];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($new_password === $confirm_password) {
        // Update password
        $update_query = "UPDATE dbo.Users SET password = ? WHERE user_id = ?";
        $params = array($new_password, $user_key); // Use hashed passwords in production
        $update_stmt = sqlsrv_query($conn, $update_query, $params);

        if ($update_stmt) {
            $message = "Profile updated successfully!";
        } else {
            $message = "Failed to update profile: " . print_r(sqlsrv_errors(), true);
        }
    } else {
        $message = "Passwords do not match. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .update-container {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
        }
        .update-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .update-container input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .update-container button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        .update-container button:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
            color: green;
        }
        .error-message {
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
            color: red;
        }
        .login-link {
            margin-top: 10px;
            text-align: center;
        }
        .login-link a {
            text-decoration: none;
            color: #007bff;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="update-container">
        <h2>Update Password</h2>
        <?php if ($message): ?>
            <div class="<?= strpos($message, 'successfully') !== false ? 'message' : 'error-message' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form action="update_profile.php" method="post">
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
            <button type="submit">Update</button>
        </form>
    </div>
    <div class="login-link">
        <a href="loginPage.php">Kembali ke login</a>
    </div>
</body>
</html>
