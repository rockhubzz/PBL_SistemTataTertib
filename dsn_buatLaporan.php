<?php
session_start();
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Dosen") {
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

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_report'])) {
        $nim_pelanggar = $_POST['nim_pelanggar'];
        $reported_by_id = $_SESSION['user_key'];
        $tanggal_pelanggaran = $_POST['tanggal_pelanggaran'];
        $status = 'Pending'; // Default status for new reports
        $jenis_pelanggaran = $_POST['jenis_pelanggaran'];

        // Handle file upload
        $bukti = null;
        if (!empty($_FILES['bukti']['name'])) {
            $uploadDir = 'uploads/';
            $bukti = basename($_FILES['bukti']['name']);
            $uploadFile = $uploadDir . $bukti;

            // Validate file type (optional)
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (in_array($_FILES['bukti']['type'], $allowedTypes)) {
                if (!move_uploaded_file($_FILES['bukti']['tmp_name'], $uploadFile)) {
                    die("File upload failed.");
                }
            } else {
                die("Invalid file type. Only JPG, PNG, and PDF files are allowed.");
            }
        }

        $queryCheck = "SELECT tingkat_pelanggaran FROM dbo.Opsi_Pelanggaran WHERE deskripsi = ?";
        $paramsCheck = [$jenis_pelanggaran];

        $stmtCheck = sqlsrv_query($conn, $queryCheck, $paramsCheck);

        if ($stmtCheck === false) {
            die("Verification query failed: " . print_r(sqlsrv_errors(), true));
        }

        // Fetch student data
        $result = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        $tingkat_pelanggaran = $result['tingkat_pelanggaran'];


        // Insert into database
        $queryInsert = "INSERT INTO dbo.Pelanggaran (nim_pelanggar, reported_by_id, tingkat_pelanggaran, tanggal_pelanggaran, bukti, status, jenis_pelanggaran) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$nim_pelanggar, $reported_by_id, $tingkat_pelanggaran, $tanggal_pelanggaran, $bukti, $status, $jenis_pelanggaran];

        $stmtInsert = sqlsrv_query($conn, $queryInsert, $params);
        if ($stmtInsert === false) {
            die("Insert failed: " . print_r(sqlsrv_errors(), true));
        }

        // Redirect to the same page with a success message
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    }

    // Fetch violation options
    $query = "SELECT tingkat_pelanggaran, deskripsi FROM dbo.Opsi_Pelanggaran";
    $stmt = sqlsrv_query($conn, $query);
    $options = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $tingkat = $row['tingkat_pelanggaran'];
            $deskripsi = $row['deskripsi'];
            $options[$deskripsi] = $tingkat; // Reverse mapping: "Jenis Pelanggaran" -> "Tingkat Pelanggaran"
        }
    } else {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }

    $student = null;
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nim_pelanggar'])) {
        $nimOrNama = $_POST['nim_pelanggar'];
        // Check for student existence
        $queryCheck = "SELECT m.nim, u.nama FROM dbo.Mahasiswa m JOIN dbo.Users u ON m.user_id = u.user_id WHERE m.nim = ? OR u.nama LIKE ?";
        $paramsCheck = [$nimOrNama, "%$nimOrNama%"];

        $stmtCheck = sqlsrv_query($conn, $queryCheck, $paramsCheck);

        if ($stmtCheck === false) {
            die("Verification query failed: " . print_r(sqlsrv_errors(), true));
        }

        // Fetch student data
        $student = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
    }

    // Handle missing student or options JSON
    $studentJson = json_encode($student ?? []);
    $optionsJson = json_encode($options ?? []);
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Admin</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style/DBuatLprnMain.css">
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
                <h2>Buat Laporan</h2>
            </div>
        </div>
                <!-- Main Content -->
                <div class="main" id="main">
                    <div class="form-container">
                        <?php if (isset($_GET['success'])): ?>
                            <p class="success-message">Laporan berhasil dibuat!</p>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="nim_pelanggar">NIM / Nama Pelanggar</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" id="nim_pelanggar" name="nim_pelanggar" placeholder="Masukkan NIM/nama" value="<?= htmlspecialchars($_POST['nim_pelanggar'] ?? '') ?>" required>
                                    <button type="button" class="submit-btn" onclick="checkNim()">Check</button>
                                </div>
                            </div>

                            <?php if ($student): ?>
                                <p style="color: green;">Mahasiswa found: <?= htmlspecialchars($student['nama']) ?> (NIM: <?= htmlspecialchars($student['nim']) ?>)</p>
                            <?php elseif (!$student && isset($_POST['nim_pelanggar'])): ?>
                                <p style="color: red;">Mahasiswa not found.</p>
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="jenis_pelanggaran">Jenis Pelanggaran</label>
                                <select id="jenis_pelanggaran" name="jenis_pelanggaran" class="select2" required onchange="updateTingkatPelanggaran()">
                                    <option value="" disabled selected>Pilih Pelanggaran</option>
                                    <?php foreach (array_keys($options) as $jenis): ?>
                                        <option value="<?= htmlspecialchars($jenis) ?>"><?= htmlspecialchars($jenis) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <p id="tingkat_pelanggaran" style="font-weight: bold; color: #333;"></p><br> <!-- Display as plain text -->
                            </div>

                            <div class="form-group">
                                <label for="tanggal_pelanggaran">Tanggal Pelanggaran</label>
                                <input type="date" id="tanggal_pelanggaran" name="tanggal_pelanggaran" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="bukti">Upload Bukti</label>
                                <input type="file" id="bukti" name="bukti" accept=".jpg,.png,.pdf">
                            </div>

                            <button type="submit" name="submit_report" class="submit-btn">Submit Report</button>
                        </form>
                    </div>
                </div>
            </div>
        <!-- Close database connection -->
        <?php sqlsrv_close($conn); ?>

        <script>
                document.addEventListener('DOMContentLoaded', function() {
                    $('#jenis_pelanggaran').select2({
                        placeholder: 'Select',
                        allowClear: true,
                        dropdownAutoWidth: true,
                        width: '100%'
                    });

                    const studentData = <?= $studentJson ?>;
                    if (studentData && studentData.nim) {
                        document.getElementById('nim_pelanggar').value = studentData.nim;
                    }
                });

                const options = <?= $optionsJson ?>;

                function updateTingkatPelanggaran() {
                    const jenisPelanggaran = document.getElementById('jenis_pelanggaran').value;
                    const tingkatPelanggaranText = document.getElementById('tingkat_pelanggaran');

                    const tingkatValue = options[jenisPelanggaran];

                    tingkatPelanggaranText.textContent = "Tingkat Pelanggaran: " + tingkatValue;
                }
                async function checkNim() {
                    const nim = document.getElementById('nim_pelanggar').value.trim();

                    if (!nim) {
                        alert('Please enter a NIM or name to check.');
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('nim_pelanggar', nim);

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);

                        const html = await response.text();

                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        const foundMessage = doc.querySelector('p[style="color: green;"]');
                        const notFoundMessage = doc.querySelector('p[style="color: red;"]');

                        if (foundMessage) {
                            alert(foundMessage.textContent);
                            const nimValueMatch = foundMessage.textContent.match(/NIM:\s*(\S+?)(?:\s*\))/);
                            if (nimValueMatch) {
                                document.getElementById('nim_pelanggar').value = nimValueMatch[1]; // Extract NIM without parentheses
                            }
                        } else if (notFoundMessage) {
                            alert(notFoundMessage.textContent);
                        } else {
                            alert('Unexpected response from the server.');
                        }

                    } catch (error) {
                        alert('Error checking NIM: ' + error.message);
                    }
                }
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