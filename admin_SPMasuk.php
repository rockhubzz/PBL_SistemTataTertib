<?php
session_start();
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Admin") {
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

    // Fetch mahasiswa data
    $query = "
SELECT s.id_sp, s.id_pelanggaran, s.nim_pembuat,
	(SELECT nama FROM Users WHERE user_id = m.user_id) AS nama,
	p.tingkat_pelanggaran, p.jenis_pelanggaran, s.tanggal_dibuat, s.path_file
FROM SP s
JOIN Pelanggaran p ON p.id_pelanggaran = s.id_pelanggaran
JOIN Mahasiswa m ON p.nim_pelanggar = m.nim

";
    $stmt = sqlsrv_query($conn, $query);

    if (!$stmt) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SP Masuk</title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/ASPMasukMain.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="//cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
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
                <a href="admin_SPMasuk.php" class="menu-item">
                    <i class="fas fa-envelope"></i><span>SP masuk</span>
                </a>
                <a href="admin_buatAkun.php" class="menu-item">
                    <i class="fas fa-user-cog"></i><span>Manage Akun</span>
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
                <h2>SP Masuk</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
            <div class="content-container">
                <h2>Surat Pernyataan Masuk</h2>
                <div class="table-container">
                    <table id="Tabel">
                        <thead>
                            <tr>
                                <th>NIM</th>
                                <th>Nama</th>
                                <th>Tingkat Pelanggaran</th>
                                <th>Jenis Pelanggaran</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nim_pembuat']) ?></td>
                                    <td><?= htmlspecialchars($row['nama']) ?></td>
                                    <td><?= htmlspecialchars($row['tingkat_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($row['jenis_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($row['tanggal_dibuat']->format('d-m-Y')) ?></td>
                                    <td><a href="<?= htmlspecialchars($row['path_file']) ?>" class="view-btn">Lihat SP</a> </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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
                $(document).ready(function() {
                    $('#Tabel').DataTable({
                        paging: true,
                        searching: true,
                        ordering: true,
                        info: true,
                        language: {
                            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                        }
                    });
                });
            </script>
    </body>

    </html>
<?php
} else {
    header("location: logout.php");
}
?>