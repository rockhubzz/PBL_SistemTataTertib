<?php
session_start();

if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Mahasiswa") {
    $config = parse_ini_file('db_config.ini');
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

    if (isset($_GET['id_pelanggaran'])) {
        $id_pelanggaran = $_GET['id_pelanggaran'];

        $queryFetch = "SELECT nim FROM Mahasiswa m WHERE m.user_id = ?";
        $params = [$_SESSION['user_key']];
        $stmt = sqlsrv_query($conn, $queryFetch, $params);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (!$row) {
            die("Error fetching user information.");
        }

        $uploadedFileName = null;

        // Handle file upload
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_sp'])) {
            $nim_pembuat = $row['nim'];
            $tanggal_dibuat = date('Y-m-d');
            $file_path = null;

            // Process file upload
            if (!empty($_FILES['sp_file']['name'])) {
                $uploadDir = 'uploads/';
                $file_path = basename($_FILES['sp_file']['name']);
                $uploadFile = $uploadDir . $file_path;

                // Validate file type
                $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                if (in_array($_FILES['sp_file']['type'], $allowedTypes)) {
                    if (!move_uploaded_file($_FILES['sp_file']['tmp_name'], $uploadFile)) {
                        die("File upload failed.");
                    }
                    $uploadedFileName = $file_path; // Save the file name
                } else {
                    die("Invalid file type. Only PDF, JPG, and PNG files are allowed.");
                }
            }

            $queryInsert = "INSERT INTO SP (id_pelanggaran, path_file, nim_pembuat, tanggal_dibuat) VALUES (?, ?, ?, ?)";
            $paramsInsert = [$id_pelanggaran, $file_path, $nim_pembuat, $tanggal_dibuat];

            $stmtInsert = sqlsrv_query($conn, $queryInsert, $paramsInsert);
            if ($stmtInsert === false) {
                die("Insert failed: " . print_r(sqlsrv_errors(), true));
            }

            header("Location: " . $_SERVER['PHP_SELF'] . "?id_pelanggaran=" . urlencode($id_pelanggaran) . "&success=1&uploaded=" . urlencode($uploadedFileName));
            exit;
        }

        if (isset($_GET['uploaded'])) {
            $uploadedFileName = htmlspecialchars($_GET['uploaded']);
        }
    } else {
        die("No id_pelanggaran provided in the URL.");
    }
?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Surat Pernyataan</title>
    <link rel="stylesheet" href="style/MenuStyles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .form-container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 20px auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        input[type="text"],
        input[type="file"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
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

        .file-info {
            margin-top: 10px;
            color: green;
            font-size: 14px;
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
            <a href="mhs_laporanBanding.php" class="<?= ($current_page == 'mhs_laporanBanding.php') ? 'active' : '' ?>">
                <i class="fas fa-balance-scale"></i><span>Laporan Banding</span>
            </a>
            <a href="mhs_lihatSanksi.php" class="<?= ($current_page == 'mhs_laporanBanding.php') ? 'active' : '' ?>">
                <i class="fas fa-exclamation-triangle"></i><span>Lihat Sanksi</span>
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
    </div>

    <div class="main">
        <h2>Upload Surat Pernyataan</h2>
        <div class="form-container">
            <?php if (isset($_GET['success'])): ?>
                <p class="success-message">Surat Pernyataan berhasil diunggah!</p>
                <p><?php echo $id_pelanggaran?></p>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="sp_file" style="color: black">Upload File</label>
                    <input type="file" id="sp_file" name="sp_file" accept=".pdf,.jpg,.png" style="color: black" required>
                    <?php if ($uploadedFileName): ?>
                        <p class="file-info" style="color: black">File uploaded: <?= htmlspecialchars($uploadedFileName) ?></p>
                    <?php endif; ?>
                </div>
                <button type="submit" name="upload_sp" class="submit-btn">Upload</button>
            </form>
        </div>
    </div>
</body>

</html>

<?php
    sqlsrv_close($conn);
} else {
    header("location: logout.php");
}
?>
