<?php
// Start the session
session_start();
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Mahasiswa") {
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

    // Ensure the user is logged in and has a profile name
    if (!isset($_SESSION['profile_name']) || !isset($_SESSION['user_key'])) {
        die("Unauthorized access. Please log in.");
    }

    // Ensure id_pelanggaran is provided via GET
    if (!isset($_GET['id_pelanggaran'])) {
        die("Invalid request. Missing id_pelanggaran.");
    }

    $query = "SELECT p.nim_pelanggar, p.jenis_pelanggaran, b.alasan AS alasan
                FROM dbo.Pelanggaran p
                JOIN dbo.Banding b ON p.id_pelanggaran = b.id_pelanggaran
                WHERE b.id_pelanggaran = ?";
    $params = [$_GET['id_pelanggaran']];

    $stmt = sqlsrv_query($conn, $query, $params);
    if ($stmt === false) {
        die("Insert failed: " . print_r(sqlsrv_errors(), true));
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    $id_pelanggaran = $_GET['id_pelanggaran'];
    $nim_pengaju = $_SESSION['user_key']; // Retrieve the user's NIM from session

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_banding'])) {
        $alasan = $_POST['alasan'];

        // Insert into dbo.Banding
        $queryInsert = "UPDATE dbo.Banding
                         SET alasan = ?
                         WHERE id_pelanggaran = ?";
        $params = [$alasan, $id_pelanggaran];

        $stmtInsert = sqlsrv_query($conn, $queryInsert, $params);
        if ($stmtInsert === false) {
            die("Insert failed: " . print_r(sqlsrv_errors(), true));
        }

        // Redirect to the same page with a success message
        header("Location: " . $_SERVER['PHP_SELF'] . "?id_pelanggaran=" . urlencode($id_pelanggaran) . "&success=1");
        exit;
    }
    $queryCheck = "SELECT COUNT(*) AS count FROM dbo.Banding WHERE id_pelanggaran = ?";
    $paramsCheck = [$id_pelanggaran];
    $stmtCheck = sqlsrv_query($conn, $queryCheck, $paramsCheck);

    if ($stmtCheck === false) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }

    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
    $bandingExists = $rowCheck['count'] > 0; // True if a banding exists
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ajukan Banding</title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/MAjukanBandingMain.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>

    <body>
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="img/LogoPLTK.png" alt="Logo">
            </div>
            <div class="menu">
                <a href="Mahasiswa.php" class="menu-item">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </a>
                <a href="mhs_listPelanggaran.php" class="menu-item">
                    <i class="fas fa-exclamation-circle"></i><span>Lihat Pelanggaran</span>
                </a>
                <a href="mhs_buatLaporan.php" class="menu-item">
                    <i class="fas fa-file-alt"></i><span>Buat Laporan</span>
                </a>
                <a href="mhs_listLaporan.php" class="menu-item">
                    <i class="fas fa-book"></i><span>Lihat Laporan</span>
                </a>
                <a href="mhs_laporanBanding.php" class="menu-item">
                    <i class="fas fa-balance-scale"></i><span>Laporan Banding</span>
                </a>
                <a href="mhs_lihatSanksi.php" class="menu-item">
                    <i class="fas fa-exclamation-triangle"></i><span>Lihat Sanksi</span>
                </a>
            </div>
            <div class="profile">
                <img src="img/profile.png" alt="Profile">
                <span class="username">
                    <h3 id="profile-name"><?php echo $_SESSION['profile_name']; ?></h3>
                </span>
                <div class="dropdown-content">
                    <a href="update_profile.php">Change Password</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <!-- Header -->
        <div class="header" id="header">
            <button class="toggle-btn" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div class="title">
                <h1>Sistem Tata Tertib</h1>
                <h2>Ajukan Banding</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
        <div class="table-container">
        <div class="form-container">
        <div class="back-button">
        <button class="btn-back" onclick="history.back()">
            ← Kembali
        </button>
        </div>
                <?php if (isset($_GET['success'])): ?>
                    <p class="success-message">Banding berhasil diajukan!</p>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="id_pelanggaran">ID Pelanggaran</label>
                        <input type="text" id="id_pelanggaran" name="id_pelanggaran" value="<?= htmlspecialchars($id_pelanggaran) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="jenis_pelanggaran">Jenis Pelanggaran:</label>
                        <p><?php echo $row['jenis_pelanggaran'] ?></p>
                    </div>
                    <div class="form-group">
                        <label for="alasan">Alasan Banding</label>
                        <textarea id="alasan" name="alasan" required><?php echo htmlspecialchars($row['alasan']); ?></textarea>
                    </div>
                    
                    <button type="submit" name="submit_banding" class="submit-btn">Submit Banding</button>
                </form>
            </div>
        </div>
        </div>
        </div>

        <script>
            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');
            const header = document.getElementById('header');
            const main = document.getElementById('main');

            toggleSidebar.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                main.classList.toggle('collapsed');
                header.classList.toggle('collapsed');
            });
        </script>
    </body>

    </html>
<?php
    sqlsrv_close($conn);
} else {
    header("location: logout.php");
}
?>