<?php
// Start the session
session_start();

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

// Ensure the user is logged in and has a profile name
if (!isset($_SESSION['profile_name']) || !isset($_SESSION['user_key'])) {
    die("Unauthorized access. Please log in.");
}

// Ensure id_pelanggaran is provided via GET
if (!isset($_GET['id_pelanggaran'])) {
    die("Invalid request. Missing id_pelanggaran.");
}

$query = "SELECT nim_pelanggar, jenis_pelanggaran, bukti FROM dbo.Pelanggaran WHERE id_pelanggaran = ?";
$params = [$_GET['id_pelanggaran']];

$stmt = sqlsrv_query($conn, $query, $params);
if ($stmt === false) {
die("Insert failed: " . print_r(sqlsrv_errors(), true));
}
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

$id_pelanggaran = $_GET['id_pelanggaran'];
$nim_pengaju = $_SESSION['user_key']; // Retrieve the user's NIM from session

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_banding'])) {
    $alasan = $_POST['alasan'];

    // Insert into dbo.Banding
    $queryInsert = "INSERT INTO dbo.Banding (nim_pengaju, id_pelanggaran, alasan, kesepakatan)
                    VALUES (?, ?, ?, 0)";
    $params = [$row['nim_pelanggar'], $id_pelanggaran, $alasan];

    $stmtInsert = sqlsrv_query($conn, $queryInsert, $params);
    if ($stmtInsert === false) {
        die("Insert failed: " . print_r(sqlsrv_errors(), true));
    }

    // Redirect to the same page with a success message
    header("Location: " . $_SERVER['PHP_SELF'] . "?id_pelanggaran=" . urlencode($id_pelanggaran) . "&success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Banding</title>
    <link rel="stylesheet" href="style/MenuStyles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Similar styles to the previous page */
        .form-container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            color: black;
        }
        .form-group p{
            color: black;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            resize: none;
            height: 150px;
        }
        .submit-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-btn:hover {
            background-color: #0056b3;
        }
        .success-message {
            color: green;
            margin-bottom: 15px;
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
            <a href="mengajukan_sanksi.php"><i class="fas fa-gavel"></i><span>Mengajukan Sanksi</span></a>
            <a href="laporan_pernyataan.php"><i class="fas fa-clipboard"></i><span>Laporan Pernyataan</span></a>
            <a href="laporan_banding.php" class="active"><i class="fas fa-balance-scale"></i><span>Laporan Banding</span></a>
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
        <h2>Ajukan Banding</h2>
        <div class="form-container">
            <?php if (isset($_GET['success'])): ?>
                <p class="success-message">Banding berhasil diajukan!</p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="id_pelanggaran">ID Pelanggaran</label>
                    <input type="text" id="id_pelanggaran" name="id_pelanggaran" value="<?= htmlspecialchars($id_pelanggaran) ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="jenis_pelanggaran">Jenis Pelanggaran:</label>
                    <p><?php echo $row['jenis_pelanggaran']?></p>
                </div>
                <div class="form-group">
                    <label for="alasan">Alasan Banding</label>
                    <textarea id="alasan" name="alasan" required></textarea>
                </div>
                <button type="submit" name="submit_banding" class="submit-btn">Submit Banding</button>
            </form>
        </div>
    </div>

    <!-- Close database connection -->
    <?php sqlsrv_close($conn); ?>
</body>
</html>
