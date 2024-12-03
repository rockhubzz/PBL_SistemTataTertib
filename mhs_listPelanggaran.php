<?php
    session_start();
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

    // Get tingkat_pelanggaran from the dropdown or default to 1
    $selected_level = isset($_GET['tingkat_pelanggaran']) ? $_GET['tingkat_pelanggaran'] : 1;

    // Query to fetch violations filtered by tingkat_pelanggaran
    $query = "
        SELECT 
            p.id_pelanggaran, 
            p.jenis_pelanggaran, 
            p.tanggal_pelanggaran, 
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

    $params = [$selected_level, $_SESSION['user_key']];
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }

    // Fetch results
    $pelanggarans = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $pelanggarans[] = $row;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filtered Pelanggarans</title>
    <link rel="stylesheet" href="style/MenuStyles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .filter-section {
            margin: 20px auto;
            width: 90%;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 90px;
            margin-right: 70px;
        }

        .filter-section select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .report-table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            font-size: 16px;
            margin-left: 300px;
        }

        .report-table th, .report-table td {
            border: 1px solid #ddd;
            text-align: center;
            padding: 10px;
        }

        .report-table th {
            background-color: #27365a;
            color: white;
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
            <a href="Mahasiswa.php" class="<?= ($current_page == 'Mahasiswa.php') ? 'active' : '' ?>">
                <i class="fas fa-home"></i><span>Dashboard</span>
            </a>
            <a href="mhs_listPelanggaran.php" class="<?= ($current_page == 'mhs_listPelanggaran.php') ? 'active' : '' ?>">
                <i class="fas fa-exclamation-circle"></i><span>Lihat Pelanggaran</span>
            </a>
            <a href="buat_laporan.php" class="<?= ($current_page == 'buat_laporan.php') ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i><span>Buat Laporan</span>
            </a>
            <a href="mengajukan_sanksi.php" class="<?= ($current_page == 'mengajukan_sanksi.php') ? 'active' : '' ?>">
                <i class="fas fa-gavel"></i><span>Mengajukan Sanksi</span>
            </a>
            <a href="laporan_pernyataan.php" class="<?= ($current_page == 'laporan_pernyataan.php') ? 'active' : '' ?>">
                <i class="fas fa-clipboard"></i><span>Laporan Pernyataan</span>
            </a>
            <a href="laporan_banding.php" class="<?= ($current_page == 'laporan_banding.php') ? 'active' : '' ?>">
                <i class="fas fa-balance-scale"></i><span>Laporan Banding</span>
            </a>
        </div>
    </div>

    <!-- Topbar -->
    <div class="topbar" id="topbar">
        <div class="profile-notifications">
            <h2>List Pelanggaran Anda</h2>
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
    <div class="main-content">
        <!-- Filter Dropdown -->
        <div class="filter-section">
            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="get">
                <label for="tingkat_pelanggaran">Filter by Tingkat Pelanggaran:</label>
                <select name="tingkat_pelanggaran" id="tingkat_pelanggaran" onchange="this.form.submit()">
                    <option value="1" <?= $selected_level == 1 ? 'selected' : '' ?>>Tingkat 1</option>
                    <option value="2" <?= $selected_level == 2 ? 'selected' : '' ?>>Tingkat 2</option>
                    <option value="3" <?= $selected_level == 3 ? 'selected' : '' ?>>Tingkat 3</option>
                    <option value="4" <?= $selected_level == 4 ? 'selected' : '' ?>>Tingkat 4</option>
                    <option value="5" <?= $selected_level == 5 ? 'selected' : '' ?>>Tingkat 5</option>
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
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pelanggarans)): ?>
                    <?php foreach ($pelanggarans as $pelanggaran): ?>
                        <tr>
                            <td><?= htmlspecialchars($pelanggaran['id_pelanggaran']) ?></td>
                            <td><?= htmlspecialchars($pelanggaran['jenis_pelanggaran']) ?></td>
                            <td><?= htmlspecialchars($pelanggaran['tanggal_pelanggaran']->format('Y-m-d')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Tidak ada pelanggaran pada tingkat ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Add any necessary JavaScript here
    </script>
</body>
</html>
