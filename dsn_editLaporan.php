<?php
// Start the session
session_start();
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Dosen") {
    $idEdit = isset($_GET['id_pelanggaran']) ? intval($_GET['id_pelanggaran']) : null;


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

    $query = "
    SELECT * FROM dbo.Pelanggaran WHERE id_pelanggaran = ?
";
    $params = [$idEdit];
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);


    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_report'])) {
        $nim_pelanggar = $_POST['nim_pelanggar'];
        $reported_by_id = $_SESSION['user_key']; // Use the session profile ID
        $tingkat_pelanggaran = $_POST['tingkat_pelanggaran'];
        $tanggal_pelanggaran = $_POST['tanggal_pelanggaran'];
        $status = 'Pending'; // Default status for new reports
        $jenis_pelanggaran = $_POST['jenis_pelanggaran'];

        // Handle file upload
        $bukti = null;
        if (!empty($_FILES['bukti']['name'])) {
            $uploadDir = 'uploads/';
            $bukti = basename($_FILES['bukti']['name']);
            $uploadFile = $uploadDir . $bukti;

            if (!move_uploaded_file($_FILES['bukti']['tmp_name'], $uploadFile)) {
                die("File upload failed.");
            }
        }

        // Insert into database
        $queryInsert = "UPDATE dbo.Pelanggaran
                        SET nim_pelanggar = ?,
                        reported_by_id = ?, tingkat_pelanggaran = ?,
                        tanggal_pelanggaran = ?,
                        bukti = ?, status = ?,
                        jenis_pelanggaran = ?
                    WHERE id_pelanggaran = ?";
        $params = [$nim_pelanggar, $reported_by_id, $tingkat_pelanggaran, $tanggal_pelanggaran, $bukti, $status, $jenis_pelanggaran, $idEdit];

        $stmtInsert = sqlsrv_query($conn, $queryInsert, $params);
        if ($stmtInsert === false) {
            die("Insert failed: " . print_r(sqlsrv_errors(), true));
        }

        // Redirect to the same page with a success message
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    }

    // Handle student verification
    $student = null;
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nim_pelanggar'])) {
        $nimOrNama = $_POST['nim_pelanggar'];
        $queryCheck = "SELECT nim, u.nama FROM dbo.Mahasiswa m
                    JOIN dbo.Users u ON m.user_id = u.user_id
                    WHERE m.nim = ? OR u.nama LIKE ?";
        $paramsCheck = [$nimOrNama, "%$nimOrNama%"];

        $stmtCheck = sqlsrv_query($conn, $queryCheck, $paramsCheck);

        if ($stmtCheck === false) {
            die("Verification query failed: " . print_r(sqlsrv_errors(), true));
        }

        $student = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Laporan</title>
        <link rel="stylesheet" href="style/DEditMain.css">
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>

    <body>
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="img/LogoPLTK.png" alt="Logo">
            </div>
            <div class="menu">
                <a href="dosenMenu.php" class="menu-item">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </a>
                <a href="dsn_buatLaporan.php" class="menu-item">
                    <i class="fas fa-user"></i><span>Buat Laporan</span>
                </a>
                <a href="dsn_listLaporan.php" class="menu-item">
                    <i class="fas fa-book"></i><span>List Laporan</span>
                </a>
                <a href="dsn_laporanBanding.php" class="menu-item">
                    <i class="fas fa-balance-scale"></i><span>Laporan Banding</span>
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
                <h2>Edit Laporan</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main">
            <div class="form-container">
            <div class="back-button">
        <button class="btn-back" onclick="history.back()">
            ← Kembali
        </button>
        </div>
                <?php if (isset($_GET['success'])): ?>
                    <p class="success-message">Laporan berhasil dibuat!</p>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nim_pelanggar">NIM / Nama Pelanggar</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="nim_pelanggar" name="nim_pelanggar" value="<?= htmlspecialchars($row['nim_pelanggar'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Other form fields remain unchanged -->
                    <div class="form-group">
                        <label>Reported By</label>
                        <input type="text" value="<?= htmlspecialchars($_SESSION['profile_name']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <div class="form-group">
                            <label for="tanggal_pelanggaran">Tanggal Pelanggaran</label>
                            <input
                                type="date"
                                id="tanggal_pelanggaran"
                                name="tanggal_pelanggaran"
                                value="<?= isset($row['tanggal_pelanggaran']) ? htmlspecialchars($row['tanggal_pelanggaran']->format('Y-m-d')) : '' ?>"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="tingkat_pelanggaran">Tingkat Pelanggaran</label>
                            <select id="tingkat_pelanggaran" name="tingkat_pelanggaran" required onchange="updateJenisPelanggaran()">
                                <option value="" disabled <?= !isset($row['tingkat_pelanggaran']) ? 'selected' : '' ?>>Select Level</option>
                                <option value="1" <?= isset($row['tingkat_pelanggaran']) && $row['tingkat_pelanggaran'] == 1 ? 'selected' : '' ?>>1</option>
                                <option value="2" <?= isset($row['tingkat_pelanggaran']) && $row['tingkat_pelanggaran'] == 2 ? 'selected' : '' ?>>2</option>
                                <option value="3" <?= isset($row['tingkat_pelanggaran']) && $row['tingkat_pelanggaran'] == 3 ? 'selected' : '' ?>>3</option>
                                <option value="4" <?= isset($row['tingkat_pelanggaran']) && $row['tingkat_pelanggaran'] == 4 ? 'selected' : '' ?>>4</option>
                                <option value="5" <?= isset($row['tingkat_pelanggaran']) && $row['tingkat_pelanggaran'] == 5 ? 'selected' : '' ?>>5</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="jenis_pelanggaran">Jenis Pelanggaran</label>
                            <select id="jenis_pelanggaran" name="jenis_pelanggaran" required>
                                <option value="" disabled>Select Type</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bukti">Upload Bukti</label>
                            <input type="file" id="bukti" name="bukti" accept=".jpg,.png,.pdf">
                        </div>
                        <button type="submit" name="submit_report" class="submit-btn">Submit Report</button>
                </form>
            </div>
        </div>
        <!-- Close database connection -->
        <?php sqlsrv_close($conn); ?>
        <script>
            function checkNim() {
                var nim = document.getElementById('nim_pelanggar').value;
                if (nim.trim() === '') {
                    alert('Please enter a NIM or name to check.');
                    return;
                }
                // Create a form to submit via AJAX or redirect with GET parameters
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href; // Submit to the same page

                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'nim_pelanggar';
                input.value = nim;
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit(); // Submit the form
            }
            document.addEventListener("DOMContentLoaded", function() {
                const tingkatPelanggaran = document.getElementById('tingkat_pelanggaran').value;
                const jenisPelanggaran = document.getElementById('jenis_pelanggaran');
                const currentJenisPelanggaran = <?= json_encode($row['jenis_pelanggaran'] ?? '') ?>;

                // Define options based on tingkat_pelanggaran
                const options = {
                    1: ['Merusak nama baik Polinema', 'Menggunakan obat-obatan terlarang', 'Mengedarkan serta menjual obat-obatan terlarang', 'Melakukan tindak kriminal dan terbukti bersalah'],
                    2: ['Merusak fasilitas Polinema', 'Mengakses materi pornografi di area kampus', 'Membawa dan/atau menggunakan senjata tajam'],
                    3: ['Melanggar peraturan Polinema/jurusan/prodi', 'Tidak menjaga kebersihan', 'Membuat kegaduhan', 'Merokok di luar area merokok', 'Bermain kartu, game online di area kampus'],
                    4: ['Berpakaian tidak pantas', 'Mahasiswa laki-laki berambut tidak rapi', 'Mahasiswa berambut berwarna', 'Makan atau minum di lab'],
                    5: ['Berbicara tidak sopan'],
                };

                function populateJenisPelanggaran() {
                    const tingkatPelanggaranValue = document.getElementById('tingkat_pelanggaran').value;

                    // Clear existing options
                    jenisPelanggaran.innerHTML = '<option value="" disabled>Select Type</option>';

                    // Populate options based on tingkat_pelanggaran
                    if (options[tingkatPelanggaranValue]) {
                        options[tingkatPelanggaranValue].forEach(option => {
                            const opt = document.createElement('option');
                            opt.value = option;
                            opt.textContent = option;
                            if (option === currentJenisPelanggaran) {
                                opt.selected = true;
                            }
                            jenisPelanggaran.appendChild(opt);
                        });
                    }
                }
                // Populate jenis_pelanggaran on load and when tingkat_pelanggaran changes
                populateJenisPelanggaran();
                document.getElementById('tingkat_pelanggaran').addEventListener('change', populateJenisPelanggaran);
            });
        </script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const toggleBtn = document.querySelector(".toggle-btn");
                const sidebar = document.querySelector(".sidebar");
                const main = document.querySelector(".main");
                const header = document.querySelector(".header");

                toggleBtn.addEventListener("click", () => {
                    sidebar.classList.toggle("collapsed");
                    main.classList.toggle("collapsed");
                    header.classList.toggle("collapsed");
                });
            });
        </script>
    </body>
    </html>
<?php
} else {
    header("location: logout.php");
}
?>