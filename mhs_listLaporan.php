<?php
session_start();

// Include database configuration
$config = parse_ini_file('db_config.ini');
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Mahasiswa") {
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

    // Get the current user's key from the session
    $userKey = $_SESSION['user_key'];

    // Query to fetch data where reported_by_id matches the current user's key
    $query = "
    SELECT 
        id_pelanggaran, 
        nim_pelanggar, 
        reported_by_id, 
        bukti,
        jenis_pelanggaran,
        tingkat_pelanggaran, 
        tanggal_pelanggaran, 
        status 
    FROM 
        dbo.Pelanggaran 
    WHERE 
        reported_by_id = ?
    ORDER BY id_pelanggaran DESC
";
    $params = [$userKey];
    $stmt = sqlsrv_query($conn, $query, $params);

    if (!$stmt) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }
    // Check if a delete request is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
        $idPelanggaran = $_POST['id_pelanggaran'];

        // Prepare the query to execute HapusLaporan stored procedure
        $query = "EXEC HapusLaporan @IdPelanggaran = ?";
        $params = [$idPelanggaran];
        $deleteStmt = sqlsrv_query($conn, $query, $params);

        if ($deleteStmt === false) {
            die("Failed to delete report: " . print_r(sqlsrv_errors(), true));
        } else {
            echo "<script>alert('Laporan berhasil dihapus.'); window.location.href = 'mhs_listLaporan.php';</script>";
        }
    }

?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>List Laporan</title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/MDataLprnMain.css">
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
                <h2>Data Laporan</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
            <div class="table-container">
                <table id="Tabel">
                    <thead>
                        <tr>
                            <th>ID Pelanggaran</th>
                            <th>NIM Pelanggar</th>
                            <th>Bukti</th>
                            <th>Jenis Pelanggaran</th>
                            <th>Tingkat Pelanggaran</th>
                            <th>Tanggal Pelanggaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id_pelanggaran']) ?></td>
                                <td><?= htmlspecialchars($row['nim_pelanggar']) ?></td>
                                <td>
                                    <?php if (!empty($row['bukti'])): ?>
                                        <a href="uploads/<?= htmlspecialchars($row['bukti']) ?>" target="_blank"><?= htmlspecialchars($row['bukti']) ?></a>
                                    <?php else: ?>
                                        <span>Tidak ada</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['jenis_pelanggaran']) ?></td>
                                <td><?= htmlspecialchars($row['tingkat_pelanggaran']) ?></td>
                                <td><?= htmlspecialchars($row['tanggal_pelanggaran']->format('d-m-Y')) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td>
    <div class="action-buttons">
        <?php
        $url = "mhs_editLaporan.php?id_pelanggaran=" . urlencode($row['id_pelanggaran']);
        echo "<a href='{$url}' class='view-btn'>Edit</a>";
        ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="id_pelanggaran" value="<?= htmlspecialchars($row['id_pelanggaran']) ?>">
            <button type="submit" name="delete" class="delete-btn">Hapus</button>
        </form>
    </div>
</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
    sqlsrv_close($conn);
} else {
    header("location: logout.php");
}
?>