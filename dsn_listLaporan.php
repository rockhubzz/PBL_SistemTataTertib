<?php
session_start();
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Dosen") {
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
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Admin</title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/DListLprnMain.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>

    <body>
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="img/LogoPLTK.png" alt="Logo">
            </div>
            <div class="menu">
                <a href="dosenMenu.php" class="menu-item">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </a>
                <a href="dsn_buatLaporan.php" class="menu-item">
                    <i class="fas fa-user"></i><span>Buat Laporan</span>
                </a>
                <a href="dsn_listLaporan.php" class="menu-item">
                    <i class="fas fa-book"></i><span>List Laporan</span>
                </a>
                <a href="dsn_laporanBanding.php" class="menu-item">
                    <i class="fas fa-balance-scale"></i><span>Laporan Banding</span>
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
                <h2>List Laporan</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
            <div class="table-container">
                <div class="report-section">
                </div>
                <h2>Data Laporan</h2>
                <div class="dashboard-content">
                    <table class="content-table">
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
                                    </td>
                                    <td><?= htmlspecialchars($row['tingkat_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($row['tanggal_pelanggaran']->format('Y-m-d')) ?></td>
                                    <td><?= htmlspecialchars($row['status']) ?></td>
                                    <td><?php
                                        $url = "dsn_editLaporan.php?id_pelanggaran=" . urlencode($row['id_pelanggaran']);
                                        echo "<a href='{$url}' class='view-btn'>Edit</a>";
                                        ?>
                                    </td>

                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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