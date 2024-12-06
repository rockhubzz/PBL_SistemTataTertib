<?php
// Start session
session_start();

// Initialize variables
$errorMessage = "";

// Database connection
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

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';

    if (empty($role)) {
        $errorMessage = "Pilih Admin, Dosen, atau Mahasiswa.";
    } else {
        // Query to fetch user details
        $sql = "SELECT * FROM dbo.Users WHERE username = ? AND password = ? AND role = ?";
        $params = array($username, $password, $role);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Check if user exists
        if (sqlsrv_has_rows($stmt)) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $_SESSION['user_key'] = $row['user_id'];
            $_SESSION['profile_name'] = $row['nama'];
            $_SESSION['role'] = $row['role'];

            // Redirect based on role
            switch (strtolower($role)) {
                case 'admin':
                    header("Location: AdminMenu.php");
                    break;
                case 'dosen':
                    header("Location: dosenMenu.php");
                    break;
                case 'mahasiswa':
                    header("Location: mahasiswa.php");
                    break;
            }
            exit();
        } else {
            $errorMessage = "Invalid username, password, or role.";
            header("Location: loginPage.php?error=invalid_credentials");
        }
    }
}
?>