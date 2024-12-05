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
        <title>Data Laporan</title>
        <link rel="stylesheet" href="style/MenuStyles.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            .content-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            .content-table,
            th,
            td {
                border: 1px solid #ccc;
            }

            th,
            td {
                padding: 12px;
                text-align: left;
                background-color: #254989;
            }

            th {
                background-color: #007bff;
                color: white;
            }

            tr:nth-child(even) {
                background-color: #f2f2f2;
            }

            .dashboard-content {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            #selectedMenu {
                background-color: #353f4f;
            }

            .view-btn {
                background-color: #4CAF50;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 5px;
                cursor: pointer;
            }

            .view-btn:hover {
                background-color: #45a049;
            }
        </style>
    </head>

    <body>
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="img/logoPoltek.png" alt="Logo">
            </div>
            <div class="menu">
                <a href="dosenMenu.php" class="<?= ($current_page == 'dosenMenu.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </a>
                <a href="dsn_buatLaporan.php" class="<?= ($current_page == 'dsn_buatLaporan.php') ? 'active' : '' ?>">
                    <i class="fas fa-user"></i><span>Buat Laporan</span>
                </a>
                <a href="dsn_listLaporan.php" class="<?= ($current_page == 'dsn_listLaporan.php') ? 'active' : '' ?>">
                    <i class="fas fa-book"></i><span>List Laporan</span>
                </a>
                <a href="dsn_laporanBanding.php" class="<?= ($current_page == 'mhs_laporanBanding.php') ? 'active' : '' ?>">
                    <i class="fas fa-balance-scale"></i><span>Laporan Banding</span>
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
                    <h3 id="profile-name"><?php echo $_SESSION['profile_name']; ?></h3>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main" id="main">
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
                                <td><?= htmlspecialchars($row['tanggal_pelanggaran']->format('d-m-Y')) ?></td>
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

        <!-- Toggle Button -->
        <button class="toggle-btn" id="toggle-btn">&lt;</button>

        <script>
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggle-btn');

            // Sidebar toggle functionality
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '>' : '<';
            });
        </script>
    </body>

    </html>
<?php
} else {
    header("location: logout.php");
}
?>