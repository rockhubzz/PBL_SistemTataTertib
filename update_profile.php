<?php
// Start session
session_start();
// Fetch current username and role

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
$success_flag = false; // Flag to indicate success

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
            $success_flag = true; // Set success flag
            $message = "Password changed successfully!";
        } else {
            $message = "Failed to update password: " . print_r(sqlsrv_errors(), true);
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
    <link rel="stylesheet" href="style/Password.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Update Profile</title>
    <script>
        // Function to show success alert and redirect to logout
        function showSuccessAndLogout() {
            alert("Password changed successfully!");
            window.location.href = "logout.php";
        }
    </script>
</head>
<body>
<div class="update-container">
        <!-- Back Button -->
        <button class="back-button" onclick="history.back()">
            <i class="fas fa-times"></i>
        </button>
        <!-- Profile Update Form -->
        <h2>Change Password</h2>
        <?php if ($message): ?>
            <div class="<?= strpos($message, 'successfully') !== false ? 'message' : 'error-message' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($success_flag): ?>
            <script>
                // Trigger the alert and redirect if password was successfully updated
                showSuccessAndLogout();
            </script>
        <?php endif; ?>
        <form action="update_profile.php" method="post">
            <div class="form-group">
                <label for="new_password"><i class="fas fa-key"></i> New Password:</label>
                <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
            </div>
            <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Update</button>
        </form>
    </div>
</body>
</html>
