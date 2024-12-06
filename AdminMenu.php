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
    <link rel="stylesheet" href="style/AdminStyles.css">
    <link rel="stylesheet" href="style/ADasboardMain.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="img/LogoPLTK.png" alt="Logo">
    </div>
    <div class="menu">
        <a href="AdminMenu.php" class="menu-item">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <a href="admin_kelolaMhs.php" class="menu-item">
            <i class="fas fa-user"></i><span>Data Mahasiswa</span>
        </a>
        <a href="admin_kelolaDsn.php" class="menu-item">
            <i class="fas fa-book"></i><span>Data Dosen</span>
        </a>
        <a href="admin_laporanMasuk.php" class="menu-item">
            <i class="fas fa-warning"></i><span>Laporan Masuk</span>
        </a>
        <a href="admin_editPlg.php" class="menu-item">
            <i class="fas fa-exclamation-circle"></i><span>Edit Pelanggaran</span>
        </a>
        <a href="admin_editSanksi.php" class="menu-item">
            <i class="fas fa-gavel"></i><span>Edit Sanksi</span>
        </a>
    </div>
    <div class="profile">
    <img src="img/profile.png" alt="Profile">
    <span class="username"><h3 id="profile-name"><?php echo $_SESSION['profile_name']; ?></h3></span>
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
            <h2>Dashboard Admin</h2>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main" id="main">
        <div class="announcement">
            <h2>Pengumuman Penting</h2>
            <p>Terdapat pelanggaran baru yang memerlukan perhatian Anda.</p>
            <button>Detail</button>
        </div>
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
    }else{
    header("location: logout.php");
}
?>