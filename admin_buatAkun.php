<?php
// Start the session
session_start();

if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Admin") {
    // Include database configuration
    $config = parse_ini_file('db_config.ini');

    // Extract connection details
    $serverName = $config['serverName'];
    $connectionInfo = [
        "Database" => $config['database'],
        "UID" => $config['username'],
        "PWD" => $config['password']
    ];
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if (!$conn) {
        die("Connection failed: " . print_r(sqlsrv_errors(), true));
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_user'])) {
        $role = $_POST['role'];
        $nim_nip = $_POST['nim_nip'];
        $nama = $_POST['nama'];
        
        // Username and password are the same as the NIM/NIP field
        $username = $nim_nip;
        $password = $nim_nip; // Consider hashing this in production (e.g., password_hash)

        // Insert into database
        $queryInsert = "INSERT INTO Users(username, password, role, nama) VALUES(?, ?, ?, ?)";
        $params = [$username, $password, $role, $nama];

        $stmtInsert = sqlsrv_query($conn, $queryInsert, $params);
        if ($stmtInsert === false) {
            die("Insert failed: " . print_r(sqlsrv_errors(), true));
        }

        // Redirect to the same page with a success message
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User</title>
    <link rel="stylesheet" href="style/MenuStyles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        .form-container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        input[type="text"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .submit-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .submit-btn:hover {
            background-color: #0056b3;
        }

        .success-message {
            color: green;
            margin-bottom: 15px;
        }

        label {
            color: black;
        }
    </style>
</head>
<body>
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="img/LogoPLTK.png" alt="Logo">
    </div>
    <div class="menu">
        <a href="AdminMenu.php" class="<?= ($current_page == 'AdminMenu.php') ? 'active' : '' ?>">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <a href="admin_kelolaMhs.php" class="<?= ($current_page == 'admin_kelolaMhs.php') ? 'active' : '' ?>">
            <i class="fas fa-user"></i><span>Data Mahasiswa</span>
        </a>
        <a href="admin_kelolaDsn.php" class="<?= ($current_page == 'admin_kelolaDsn.php') ? 'active' : '' ?>">
            <i class="fas fa-book"></i><span>Data Dosen</span>
        </a>
        <a href="admin_laporanMasuk.php" class="<?= ($current_page == 'admin_laporanMasuk.php') ? 'active' : '' ?>">
            <i class="fas fa-warning"></i><span>Laporan Masuk</span>
        </a>
        <a href="admin_editPlg.php" class="<?= ($current_page == 'admin_laporanMasuk.php') ? 'active' : '' ?>">
            <i class="fas fa-edit"></i><span>Edit Pelanggaran</span>
        </a>
        <a href="admin_editSanksi.php" class="<?= ($current_page == 'admin_laporanMasuk.php') ? 'active' : '' ?>">
            <i class="fas fa-gavel"></i><span>Edit Sanksi</span>
        </a>
        <a href="admin_SPMasuk.php" class="<?= ($current_page == 'admin_SPMasuk.php') ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i><span>SP Masuk</span>
        </a>
        <a href="admin_buatAkun.php" class="<?= ($current_page == 'admin_SPMasuk.php') ? 'active' : '' ?>">
            <i class="fas fa-plus"></i><span>Buat Akun</span>
        </a>


    </div>
</div>

    <!-- Topbar -->
    <div class="topbar" id="topbar">
            <div class="profile dropdown">
                <img src="img/profile.png" alt="Profile Picture">
                <div class="dropdown-menu">
                    <a href="update_profile.php">Change Password</a>
                    <a href="logout.php">Log Out</a>
                </div>
                <h3 id="profile-name" style="color: white"><?php echo $_SESSION['profile_name']; ?></h3>
            </div>
    </div>

    <div class="main">
        <h2>Tambah User</h2>
        <div class="form-container">
            <?php if (isset($_GET['success'])): ?>
                <p class="success-message">User berhasil ditambahkan!</p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="Admin">Admin</option>
                        <option value="Dosen">Dosen</option>
                        <option value="Mahasiswa">Mahasiswa</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="nim_nip">NIM/NIP</label>
                    <input type="text" id="nim_nip" name="nim_nip" required>
                </div>

                <div class="form-group">
                    <label for="nama">Nama</label>
                    <input type="text" id="nama" name="nama" required>
                </div>

                <button type="submit" name="submit_user" class="submit-btn">Tambah User</button>
            </form>
        </div>
    </div>

    <?php sqlsrv_close($conn); ?>
</body>
</html>

<?php
} else {
    header("location: logout.php");
}
?>
