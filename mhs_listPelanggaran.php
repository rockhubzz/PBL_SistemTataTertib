<?php
session_start();
if($_SESSION['role'] == "Mahasiswa"){

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
            <a href="Mahasiswa.php" class="<?= ($current_page == 'Mahasiswa.php') ? 'active' : '' ?>">
                <i class="fas fa-home"></i><span>Dashboard</span>
            </a>
            <a href="mhs_listPelanggaran.php" class="<?= ($current_page == 'mhs_listPelanggaran.php') ? 'active' : '' ?>">
                <i class="fas fa-exclamation-circle"></i><span>Lihat Pelanggaran</span>
            </a>
            <a href="mhs_buatLaporan.php" class="<?= ($current_page == 'buat_laporan.php') ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i><span>Buat Laporan</span>
            </a>
            <a href="mhs_listLaporan.php" class="<?= ($current_page == 'buat_laporan.php') ? 'active' : '' ?>">
                <i class="fas fa-book"></i><span>Lihat Laporan</span>
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
            <h2>Laporan untuk Anda</h2>
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
            <th>Tingkat Pelanggaran</th>
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
                    <td><?= htmlspecialchars($pelanggaran['tingkat_pelanggaran']) ?></td>
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
<?php
}else{
    header("location: loginPage.php");
}
?>