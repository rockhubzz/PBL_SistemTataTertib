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
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Upload Surat pernyataan </title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/MUploadPernyataanMain.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>

    <body>
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="img/LogoPLTK.png" alt="Logo">
            </div>
            <div class="menu">
                <a href="Mahasiswa.php" class="menu-item">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </a>
                <a href="mhs_listPelanggaran.php" class="menu-item">
                    <i class="fas fa-exclamation-circle"></i><span>Lihat Pelanggaran</span>
                </a>
                <a href="mhs_buatLaporan.php" class="menu-item">
                    <i class="fas fa-file-alt"></i><span>Buat Laporan</span>
                </a>
                <a href="mhs_listLaporan.php" class="menu-item">
                    <i class="fas fa-book"></i><span>Lihat Laporan</span>
                </a>
                <a href="mhs_laporanBanding.php" class="menu-item">
                    <i class="fas fa-balance-scale"></i><span>Laporan Banding</span>
                </a>
                <a href="mhs_lihatSanksi.php" class="menu-item">
                    <i class="fas fa-exclamation-triangle"></i><span>Lihat Sanksi</span>
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
                <h2>Dashboard Mahasiswa</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
        <div class="table-container">
        <div class="report-section">
        <div class="form-container">
        <div class="back-button">
        <button class="btn-back" onclick="history.back()">
            ← Kembali
        </button>
        </div>
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
        </div>

        <script>
            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');
            const header = document.getElementById('header');
            const main = document.getElementById('main');

            toggleSidebar.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                main.classList.toggle('collapsed');
                header.classList.toggle('collapsed');
            });
        </script>
    </body>

    </html>
<?php
    sqlsrv_close($conn);
} else {
    header("location: logout.php");
}
?>