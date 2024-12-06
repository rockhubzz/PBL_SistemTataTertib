<?php
session_start();
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Mahasiswa") {

    $current_page = basename($_SERVER['PHP_SELF']);
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

    // Retrieve the tingkat_pelanggaran filter from the query string
    $filterLevel = isset($_GET['tingkat_pelanggaran']) ? intval($_GET['tingkat_pelanggaran']) : null;

    // Validate tingkat_pelanggaran
    if ($filterLevel === null) {
        $filterLevel = 1;
    }

    // SQL query to fetch filtered pelanggarans
    $query = "
SELECT 
p.id_pelanggaran, 
p.jenis_pelanggaran, 
            p.tingkat_pelanggaran,
            p.tanggal_pelanggaran, 
            p.status,
            m.nim, 
            u.nama AS nama_mahasiswa 
        FROM 
        dbo.Pelanggaran p
        JOIN 
        dbo.Mahasiswa m ON p.nim_pelanggar = m.nim
        JOIN 
        dbo.Users u ON m.user_id = u.user_id
        WHERE 
        p.tingkat_pelanggaran = ? AND m.nim = (SELECT nim FROM dbo.Mahasiswa WHERE user_id = ?)
        ORDER BY p.id_pelanggaran
        ";
    $params = [$filterLevel, $_SESSION['user_key']];
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }

    // Fetch results
    $filteredViolations = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $filteredViolations[] = $row;
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Admin</title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/MListPelanggaranMain.css">
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
                <h2>Lihat Pelanggaran</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
            <div class="table-container">
                <div class="filter-section">
                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="get">
                        <label for="tingkat_pelanggaran">Filter by Tingkat Pelanggaran:</label>
                        <select name="tingkat_pelanggaran" id="tingkat_pelanggaran" onchange="this.form.submit()">
                            <option value="1" <?= $filterLevel == 1 ? 'selected' : '' ?>>Tingkat 1</option>
                            <option value="2" <?= $filterLevel == 2 ? 'selected' : '' ?>>Tingkat 2</option>
                            <option value="3" <?= $filterLevel == 3 ? 'selected' : '' ?>>Tingkat 3</option>
                            <option value="4" <?= $filterLevel == 4 ? 'selected' : '' ?>>Tingkat 4</option>
                            <option value="5" <?= $filterLevel == 5 ? 'selected' : '' ?>>Tingkat 5</option>
                        </select>
                    </form>
                </div>

                <!-- Tabel Laporan -->
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>ID Pelanggaran</th>
                            <th>Jenis Pelanggaran</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Status Banding</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($filteredViolations)): ?>
                            <?php foreach ($filteredViolations as $pelanggaran): ?>
                                <?php
                                // Query to get the banding status for the current id_pelanggaran
                                $bandingQuery = "
                SELECT kesepakatan 
                FROM dbo.Banding 
                    WHERE id_pelanggaran = ?
                    ";
                                $params = [$pelanggaran['id_pelanggaran']];
                                $bandingStmt = sqlsrv_query($conn, $bandingQuery, $params);

                                // Default status
                                $bandingStatus = "Belum Ada Banding";

                                if ($bandingStmt && $row = sqlsrv_fetch_array($bandingStmt, SQLSRV_FETCH_ASSOC)) {
                                    if ($row['kesepakatan'] === null) {
                                        $bandingStatus = "Pending";
                                    } elseif ($row['kesepakatan'] == 0) {
                                        $bandingStatus = "Ditolak";
                                    } elseif ($row['kesepakatan'] == 1) {
                                        $bandingStatus = "Diterima";
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($pelanggaran['id_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($pelanggaran['jenis_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($pelanggaran['tanggal_pelanggaran']->format('Y-m-d')) ?></td>
                                    <td><?= htmlspecialchars($pelanggaran['status']) ?></td>
                                    <td><?= htmlspecialchars($bandingStatus) ?></td>
                                    <td>
                                        <?php
                                        $url = "mhs_ajukanBanding.php?id_pelanggaran=" . urlencode($pelanggaran['id_pelanggaran']);
                                        echo "<a href='{$url}' class='view-btn'>Ajukan Banding</a>";
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">Tidak ada pelanggaran pada tingkat ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
} else {
    header("location: logout.php");
}
?>