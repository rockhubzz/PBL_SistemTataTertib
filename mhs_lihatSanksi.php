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

    // Fetch data for the table
    $query = "
SELECT 
    p.id_pelanggaran AS id_pelanggaran,
    p.tingkat_pelanggaran, 
    p.jenis_pelanggaran, 
    s.sanksi,
    (SELECT COUNT(*) 
     FROM dbo.SP sp 
     WHERE sp.id_pelanggaran = p.id_pelanggaran) AS sp_count,
    (SELECT sp.path_file
     FROM dbo.SP sp
     WHERE sp.id_pelanggaran = p.id_pelanggaran) AS sp_path_file
FROM dbo.Pelanggaran p
JOIN dbo.Sanksi s 
    ON p.tingkat_pelanggaran = s.tingkat_pelanggaran
WHERE p.nim_pelanggar = (
    SELECT nim 
    FROM dbo.Mahasiswa 
    WHERE user_id = ?)
ORDER BY tingkat_pelanggaran
    ";
    $params = [$_SESSION['user_key']];
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Lihat Sanksi</title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/MSanksiMain.css">
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
                <h2>Data Pelanggaran dan Sanksi</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
            <h2>Data Pelanggaran dan Sanksi</h2>
            <div class="table-container">
                <table id="Tabel">
                    <thead>
                        <tr>
                            <th>Tingkat Pelanggaran</th>
                            <th>Jenis Pelanggaran</th>
                            <th>Sanksi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['tingkat_pelanggaran']) ?></td>
                                <td><?= htmlspecialchars($row['jenis_pelanggaran']) ?></td>
                                <td><?= htmlspecialchars($row['sanksi']) ?></td>
                                <td>
    <div class="action-buttons">
        <?php if ($row['sp_count'] > 0): ?>
            <a href="uploads/<?php echo $row['sp_path_file']; ?>" class="view-btn">Lihat SP</a>
        <?php else: ?>
            <a href="mhs_uploadPernyataan.php?id_pelanggaran=<?php echo $row['id_pelanggaran'] ?>" class="view-btn">Upload SuratPernyataan</a>
        <?php endif; ?>
    </div>
</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Close database connection -->
        <?php sqlsrv_close($conn); ?>
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