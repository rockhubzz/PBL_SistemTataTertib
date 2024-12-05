<?php
    session_start();
    $config = parse_ini_file('db_config.ini');
    if(!empty($_SESSION['user_key']) && $_SESSION['role'] == "Admin"){
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

    $current_page = basename($_SERVER['PHP_SELF']);
    $query = "SELECT COUNT(nim) AS jml_mhs,
                (SELECT COUNT(id_pelanggaran) FROM dbo.Pelanggaran) AS jml_pelanggaran,
                (SELECT COUNT(nip) FROM dbo.Dosen) AS jml_dosen
                FROM dbo.Mahasiswa";
    $stmt = sqlsrv_query($conn, $query);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $jml_mhs = $row['jml_mhs'];
    $jml_plg = $row['jml_pelanggaran'];
    $jml_dsn = $row['jml_dosen'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="style/MenuStyles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="img/logoPoltek.png" alt="Logo">
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
                <h3 id="profile-name"><?php echo $_SESSION['profile_name']; ?></h3>
            </div>
    </div>
    <!-- Main Content -->
    <div class="main" id="main">
        <div class="announcement">
            <div>
                <h2>Pengumuman Penting</h2>
                <p>Terdapat pelanggaran baru yang memerlukan perhatian Anda.</p>
            </div>
            <button>Detail</button>
        </div>
        <div class="dashboard-content">
            <div class="card">
                <h3>Total Mahasiswa</h3>
                <p><?php echo $jml_mhs ?></p>
            </div>
            <div class="card">
                <h3>Total Pelanggaran</h3>
                <p><?php echo $jml_plg ?></p>
            </div>
            <div class="card">
                <h3>Total Dosen</h3>
                <p><?php echo $jml_dsn ?></p>
            </div>
        </div>
    </div>
</body>
</html>
<?php
    }else{
    header("location: logout.php");
}
?>