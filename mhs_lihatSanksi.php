<?php
// Start the session
session_start();

if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Mahasiswa") {
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

    // Fetch data for the table
    $query = "
SELECT 
    p.id_pelanggaran AS id_pelanggaran,
    p.tingkat_pelanggaran, 
    p.jenis_pelanggaran, 
    s.sanksi,
    (SELECT COUNT(*) 
     FROM dbo.SP sp 
     WHERE sp.id_pelanggaran = p.id_pelanggaran) AS sp_count,
    (SELECT sp.path_file
     FROM dbo.SP sp
     WHERE sp.id_pelanggaran = p.id_pelanggaran) AS sp_path_file
FROM dbo.Pelanggaran p
JOIN dbo.Sanksi s 
    ON p.tingkat_pelanggaran = s.tingkat_pelanggaran
WHERE p.nim_pelanggar = (
    SELECT nim 
    FROM dbo.Mahasiswa 
    WHERE user_id = ?)
    ";
    $params = [$_SESSION['user_key']];
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pelanggaran dan Sanksi</title>
    <link rel="stylesheet" href="style/MenuStyles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            overflow: auto;
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

        .view-btn, .disabled-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .disabled-btn {
            background-color: #ccc;
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
                <a href="Mahasiswa.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
                <a href="mhs_listPelanggaran.php"><i class="fas fa-exclamation-circle"></i><span>Lihat Pelanggaran</span></a>
                <a href="mhs_buatLaporan.php"><i class="fas fa-file-alt"></i><span>Buat Laporan</span></a>
                <a href="mhs_listLaporan.php"><i class="fas fa-book"></i><span>Lihat Laporan</span></a>
                <a href="mhs_laporanBanding.php"><i class="fas fa-balance-scale"></i><span>Laporan Banding</span></a>
                <a href="mhs_lihatSanksi.php" class="active"><i class="fas fa-exclamation-triangle"></i><span>Lihat Sanksi</span></a>
            </div>
        </div>

        <!-- Topbar -->
        <div class="topbar" id="topbar">
            <div class="profile-notifications">
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

    <div class="main">
        <h2>Data Pelanggaran dan Sanksi</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tingkat Pelanggaran</th>
                        <th>Jenis Pelanggaran</th>
                        <th>Sanksi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tingkat_pelanggaran']) ?></td>
                            <td><?= htmlspecialchars($row['jenis_pelanggaran']) ?></td>
                            <td><?= htmlspecialchars($row['sanksi']) ?></td>
                            <td>
                                <?php if ($row['sp_count'] > 0): ?>
                                    <a href="uploads/<?php echo $row['sp_path_file']; ?>" class="view-btn">Lihat SP</a>
                                <?php else: ?>
                                    <a href="mhs_uploadPernyataan.php?id_pelanggaran=<?php echo $row['id_pelanggaran'] ?>" class="view-btn">Upload Surat Pernyataan</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Close database connection -->
    <?php sqlsrv_close($conn); ?>
</body>
</html>
<?php
} else {
    header("location: logout.php");
}
?>
