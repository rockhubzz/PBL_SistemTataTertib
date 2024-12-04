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

    // Handle Create
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
        $tingkat_pelanggaran = intval($_POST['tingkat_pelanggaran']);
        $sanksi = $_POST['sanksi'];

        $queryInsert = "INSERT INTO dbo.Sanksi (tingkat_pelanggaran, sanksi) VALUES (?, ?)";
        $params = [$tingkat_pelanggaran, $sanksi];
        $stmtInsert = sqlsrv_query($conn, $queryInsert, $params);

        if ($stmtInsert === false) {
            die("Insert failed: " . print_r(sqlsrv_errors(), true));
        }

        // Refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Handle Update
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
        $tingkat_pelanggaran = intval($_POST['tingkat_pelanggaran']);
        $sanksi = $_POST['sanksi'];

        $queryUpdate = "UPDATE dbo.Sanksi SET sanksi = ? WHERE tingkat_pelanggaran = ?";
        $params = [$sanksi, $tingkat_pelanggaran];
        $stmtUpdate = sqlsrv_query($conn, $queryUpdate, $params);

        if ($stmtUpdate === false) {
            die("Update failed: " . print_r(sqlsrv_errors(), true));
        }

        // Refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Handle Delete
    if (isset($_GET['delete'])) {
        $tingkat_pelanggaran = intval($_GET['delete']);

        $queryDelete = "DELETE FROM dbo.Sanksi WHERE tingkat_pelanggaran = ?";
        $params = [$tingkat_pelanggaran];
        $stmtDelete = sqlsrv_query($conn, $queryDelete, $params);

        if ($stmtDelete === false) {
            die("Delete failed: " . print_r(sqlsrv_errors(), true));
        }

        // Refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Fetch data for display
    $query = "SELECT tingkat_pelanggaran, sanksi FROM dbo.Sanksi ORDER BY tingkat_pelanggaran ASC";
    $stmt = sqlsrv_query($conn, $query);
    if ($stmt === false) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sanksi</title>
    <link rel="stylesheet" href="style/MenuStyles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            overflow: auto;
        }
        .main h2, h3, label {
            color: black;
        }
        .table-container {
            padding: 50px;
            margin: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-container {
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .tingkat {
            width: 60px;
            text-align: center;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        .btn {
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        textarea {
            width: 500px;
            height: 100px;
            resize: none;
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

    </div>
</div>

    <!-- Topbar -->
    <div class="topbar" id="topbar">
        <div class="profile-notifications">
            <div class="notifications" id="notification-icon">
                <i class="fas fa-bell"></i>
                <div class="notification-dropdown" id="notification-dropdown">
                    <h4>Notifikasi</h4>
                    <ul>
                        <li>Pelanggaran baru oleh mahasiswa A.</li>
                        <li>Dosen B mengajukan revisi data.</li>
                        <li>Pengingat rapat pukul 10.00.</li>
                    </ul>
                </div>
            </div>
            <div class="profile dropdown">
                <img src="img/profile.png" alt="Profile Picture">
                <div class="dropdown-menu">
                    <a href="update_profile.php">Change Password</a>
                    <a href="logout.php">Log Out</a>
                </div>
                <h3 id="profile-name" style="color: white"><?php echo $_SESSION['profile_name']; ?></h3>
            </div>
        </div>
    </div>
<!-- #region -->
    <div class="main">
        <h2 style="color: white">Manage Sanksi</h2>

        <!-- Display Data -->
        <div class="table-container">
            <h3>Daftar Sanksi</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tingkat Pelanggaran</th>
                        <th>Sanksi</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <form method="POST" action="">
                                <td>
                                    <input type="hidden" name="tingkat_pelanggaran" value="<?= $row['tingkat_pelanggaran'] ?>">
                                    <p style="color: black"><?= $row['tingkat_pelanggaran'] ?></p>
                                </td>
                                <td>
                                    <textarea name="sanksi" class="sanksi" required><?= htmlspecialchars($row['sanksi']) ?></textarea>
                                </td>
                                <td>
                                    <button type="submit" name="update" class="btn">Update</button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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
