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
$query = "
    SELECT 
        tingkat_pelanggaran, 
        jumlah_pelanggaran
    FROM 
        dbo.RangkumanPelanggaran
    WHERE 
        nim = (SELECT nim FROM Mahasiswa WHERE user_id = ?)
    ORDER BY 
        tingkat_pelanggaran;
";
$params = [$_SESSION['user_key']];
$stmt = sqlsrv_query($conn, $query, $params);

if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

// Fetch results
$violations = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $violations[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen</title>
    <link rel="stylesheet" href="style/MenuStyles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Basic fixes for dropdown and layout */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border: 1px solid #ccc;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            z-index: 10;
        }

        .dropdown-menu a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: black;
        }

        .dropdown-menu a:hover {
            background-color: #f1f1f1;
        }

        .dropdown:hover .dropdown-menu {
            display: block;
        }

        .notification-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border: 1px solid #ccc;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            z-index: 10;
            width: 300px;
        }

        .notification-dropdown.visible {
            display: block;
        }

        .notification-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .notification-dropdown ul li {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .notification-dropdown ul li:last-child {
            border-bottom: none;
        }

        .collapsed {
            display: none;
        }

        /* Specific styling for the cards */
        .dashboard-content {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }

        .card {
            background-color: #3d4e85;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: white;
            width: 200px;
        }

        .card h3 {
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .card p {
            font-size: 2em;
            margin: 0;
        }

        /* Announcement section */
        .announcement {
            background-color: #3d4e85;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .announcement h2 {
            margin: 0;
        }

        .announcement button {
            background-color: #ffd700;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .announcement button:hover {
            background-color: #ffc107;
        }
        .report-section{
            margin-left: 120px;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .report-section h2 {
        margin-bottom: 20px;
    }

    .topbar h2{
        text-align: left;
    }

    .report-table {
    width: 100%; /* Membuat tabel merata memenuhi lebar */
    table-layout: fixed;/* Membuat kolom memiliki lebar tetap */
    padding-left: 140px; 
    margin-top: 60px;
}

    .report-table th,
    .report-table td {
        word-wrap: break-word; /* Agar konten dalam sel tidak meluap */
        padding: 10px; /* Memberikan ruang antara teks dengan tepi sel */
        text-align: center; /* Memastikan konten tetap rapi */
    }


    .report-table th {
        background-color: #27365a;
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
    
    .disabled-btn {
        background-color: #9p9p9p;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: not-allowed;
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
    
    <!-- Tabel Laporan -->
    <div class="report-section">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Tingkat Pelanggaran</th>
                    <th>Jumlah</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($violations as $violation): ?>
                    <tr>
                        <td>Tingkat <?= htmlspecialchars($violation['tingkat_pelanggaran']) ?></td>
                        <td><?= htmlspecialchars($violation['jumlah_pelanggaran']) ?></td>
                        <td>
    <?php
    if ($violation['jumlah_pelanggaran'] != 0) {
        $url = "mhs_listPelanggaran.php?tingkat_pelanggaran=" . urlencode($violation['tingkat_pelanggaran']);
        echo "<a href='{$url}' class='view-btn'>Lihat Laporan</a>";
    } else {
        echo "<button class='disabled-btn'>Lihat Laporan</button>";
    }
    ?>
</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
    <!-- Toggle Button -->
    <button class="toggle-btn" id="toggle-btn">&lt;</button>

    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggle-btn');
        const notificationIcon = document.getElementById('notification-icon');
        const notificationDropdown = document.getElementById('notification-dropdown');

        // Sidebar toggle functionality
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '>' : '<';
        });

        // Toggle notification dropdown visibility
        notificationIcon.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevent document click handler from firing
            notificationDropdown.classList.toggle('visible');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!notificationIcon.contains(event.target)) {
                notificationDropdown.classList.remove('visible');
            }
        });
    </script>
</body>
</html>