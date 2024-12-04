<?php
// Start the session
session_start();
if(!empty($_SESSION['user_key']) && $_SESSION['role'] == "Admin"){
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
    p.status 
FROM dbo.Pelanggaran p
JOIN dbo.Mahasiswa m ON m.nim = p.nim_pelanggar
JOIN dbo.Users u ON m.user_id = u.user_id
JOIN dbo.Users u2 ON p.reported_by_id = u2.user_id
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
    <title>Data Pelanggaran</title>
    <link rel="stylesheet" href="style/MenuStyles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            overflow: scroll;
        }

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

        th, td {
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

        .sidebar a.active {
            background-color: #007bff;
            color: white;
        }

        .save-btn {
            margin: 20px 0;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .save-btn:hover {
            background-color: #0056b3;
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
    <div class="main">
        <h2>Data Pelanggaran</h2>
        <div class="table-container">
            <form method="POST" action="">
                <table>
                    <thead>
                        <tr>
                            <th>ID Pelanggaran</th>
                            <th>NIM Pelanggar</th>
                            <th>Nama Pelanggar</th>
                            <th>Pelapor</th>
                            <th>Bukti</th>
                            <th>Tingkat Pelanggaran</th>
                            <th>Jenis Pelanggaran</th>
                            <th>Tanggal Pelanggaran</th>
                            <th>Status</th>
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
                                        No File
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['tingkat_pelanggaran']) ?></td>
                                <td><?= htmlspecialchars($row['jenis_pelanggaran']) ?></td>
                                <td><?= htmlspecialchars($row['tanggal_pelanggaran']->format('Y-m-d')) ?></td>
                                <td>
                                    <select name="status[<?= $row['id_pelanggaran'] ?>]">
                                        <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Reviewed" <?= $row['status'] == 'Reviewed' ? 'selected' : '' ?>>Reviewed</option>
                                        <option value="Rejected" <?= $row['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                    </select>
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
</body>
</html>
<?php
    }else{
    header("location: logout.php");
}
?>