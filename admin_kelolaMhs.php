<?php
session_start();
if(!empty($_SESSION['user_key']) && $_SESSION['role'] == "Admin"){
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
    SELECT 
        m.nim, 
        u.nama AS nama, 
        (SELECT COUNT(id_pelanggaran) FROM Pelanggaran WHERE nim_pelanggar = m.nim) AS jumlah_pelanggaran,
        (SELECT MIN(tingkat_pelanggaran) FROM Pelanggaran WHERE nim_pelanggar = m.nim) AS tingkat_pelanggaran
    FROM 
        dbo.Mahasiswa m
    INNER JOIN 
        dbo.Users u 
    ON 
        m.user_id = u.user_id
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
    <title>Kelola Mahasiswa</title>
    <link rel="stylesheet" href="style/MenuStyles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table-container {
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
            color: black;
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
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="img/LogoPLTK.png" alt="Logo">
        </div>
        <div class="menu">
            <a href="AdminMenu.php" class="<?= ($current_page == 'AdminMenu.php') ? 'active' : '' ?>">
                <i class="fas fa-home"></i><span>Dashboard</span>
            </a>
            <a href="admin_kelolaMhs.php" class="<?= ($current_page == 'admin_kelolaMhs.php') ? 'active' : '' ?>">
                <i class="fas fa-user"></i><span>Data Mahasiswa</span>
            </a>
            <a href="admin_kelolaDsn.php" class="<?= ($current_page == 'admin_kelolaDsn.php') ? 'active' : '' ?>">
                <i class="fas fa-book"></i><span>Data Dosen</span>
            </a>
            <a href="admin_laporanMasuk.php" class="<?= ($current_page == 'admin_laporanMasuk.php') ? 'active' : '' ?>">
                <i class="fas fa-warning"></i><span>Laporan Masuk</span>
            </a>
            <a href="admin_editPlg.php" class="<?= ($current_page == 'admin_laporanMasuk.php') ? 'active' : '' ?>">
            <i class="fas fa-edit"></i><span>Edit Pelanggaran</span>
        </a>
        <a href="admin_editSanksi.php" class="<?= ($current_page == 'admin_laporanMasuk.php') ? 'active' : '' ?>">
            <i class="fas fa-gavel"></i><span>Edit Sanksi</span>
        </a>
        <a href="admin_SPMasuk.php" class="<?= ($current_page == 'admin_SPMasuk.php') ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i><span>SP Masuk</span>
        </a>
        <a href="admin_buatAkun.php" class="<?= ($current_page == 'admin_SPMasuk.php') ? 'active' : '' ?>">
            <i class="fas fa-plus"></i><span>Buat Akun</span>
        </a>



        </div>
    </div>
    <!-- Topbar -->
    <div class="topbar" id="topbar">
            <div class="profile dropdown">
                <img src="img/profile.png" alt="Profile Picture">
                <div class="dropdown-menu">
                    <a href="update_profile.php">Change Password</a>
                    <a href="logout.php">Log Out</a>
                </div>
                <h3 id="profile-name"><?php echo $_SESSION['profile_name']; ?></h3>
            </div>
    </div>

    <!-- Main Content -->
    <div class="main" id="main">
        <h2>Data Mahasiswa</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>NIM</th>
                        <th>Nama</th>
                        <th>Jumlah Pelanggaran</th>
                        <th>Tingkat Pelanggaran Max</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nim']) ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['jumlah_pelanggaran']) ?></td>
                            <td><?= htmlspecialchars($row['tingkat_pelanggaran']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
<?php
    }else{
    header("location: logout.php");
}
?>