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

    // Update the status if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        foreach ($_POST['status'] as $id_pelanggaran => $new_status) {
            $queryUpdate = "UPDATE dbo.Pelanggaran SET status = ? WHERE id_pelanggaran = ?";
            $params = [$new_status, $id_pelanggaran];
            $stmtUpdate = sqlsrv_query($conn, $queryUpdate, $params);

            if ($stmtUpdate === false) {
                die("Update failed: " . print_r(sqlsrv_errors(), true));
            }
        }
        // Refresh the page to reflect updates
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Fetch Pelanggaran data
    $query = "SELECT 
    p.id_pelanggaran, 
    p.nim_pelanggar, 
    u.nama AS nama_pelanggar, 
    u2.nama AS nama_pelapor,
    p.bukti, 
    p.tingkat_pelanggaran,
    p.jenis_pelanggaran,
    p.tanggal_pelanggaran,
    b.alasan AS laporan_banding,
    b.kesepakatan AS status_banding,
    p.status 
FROM dbo.Pelanggaran p
JOIN dbo.Mahasiswa m ON m.nim = p.nim_pelanggar
JOIN dbo.Users u ON m.user_id = u.user_id
JOIN dbo.Users u2 ON p.reported_by_id = u2.user_id
LEFT JOIN dbo.Banding b ON b.id_pelanggaran = p.id_pelanggaran
ORDER BY p.id_pelanggaran DESC

";

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
        <title>Dashboard Admin</title>
        <link rel="stylesheet" href="style/ALaporanMskMain.css">
        <link rel="stylesheet" href="style/AdminStyles.css">
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
                <h2>Laporan Masuk</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main">
            <div class="table-container">
                <form method="POST" action="">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Pelanggaran</th>
                                <th>NIM Pelanggar</th>
                                <th>Nama Pelanggar</th>
                                <th>Nama Pelapor</th>
                                <th>Bukti</th>
                                <th>Tingkat Pelanggaran</th>
                                <th>Jenis Pelanggaran</th>
                                <th>Tanggal Pelanggaran</th>
                                <th>Laporan Banding</th>
                                <th>Status Banding</th>
                                <th>Status</th>
                                <th>Edit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($row['nim_pelanggar']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_pelanggar']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_pelapor']) ?></td>
                                    <td>
                                        <?php if ($row['bukti']): ?>
                                            <a href="uploads/<?= htmlspecialchars($row['bukti']) ?>" target="_blank"><?= htmlspecialchars($row['bukti']) ?></a>
                                        <?php else: ?>
                                            Tidak ada
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['tingkat_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($row['jenis_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($row['tanggal_pelanggaran']->format('Y-m-d')) ?></td>
                                    <td><?php
                                        if ($row['laporan_banding']): echo $row['laporan_banding'];
                                        else: echo 'Belum ada banding';
                                        endif; ?></td>
                                    <td><?php
                                        if ($row['status_banding'] = null) {
                                            echo 'Belum ada banding';
                                        } elseif ($row['status_banding'] == 0) {
                                            echo 'Ditolak pelapor';
                                        } elseif ($row['status_banding'] == 1) {
                                            echo 'Diterima pelapor';
                                        }
                                        ?></td>
                                    <td>
                                        <select name="status[<?= $row['id_pelanggaran'] ?>]">
                                            <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Reviewed" <?= $row['status'] == 'Reviewed' ? 'selected' : '' ?>>Reviewed</option>
                                            <option value="Rejected" <?= $row['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>
                                    </td>
                                    <td
                                        ><?php
                                        $url = "admin_editLaporan.php?id_pelanggaran=" . urlencode($row['id_pelanggaran']);
                                        echo "<a href='{$url}'class='save-btn'>Edit</a>";
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="save-btn">Save Changes</button>
                </form>
            </div>
        </div>
        <!-- Close database connection -->
        <?php sqlsrv_close($conn); ?>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const toggleBtn = document.querySelector(".toggle-btn");
                const sidebar = document.querySelector(".sidebar");
                const main = document.querySelector(".main");
                const header = document.querySelector(".header");

                toggleBtn.addEventListener("click", () => {
                    sidebar.classList.toggle("collapsed");
                    main.classList.toggle("collapsed");
                    header.classList.toggle("collapsed");
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