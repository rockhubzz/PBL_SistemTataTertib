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
        <title>Buat Laporan</title>
        <link rel="stylesheet" href="style/MenuStyles.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
        <style>
            /* Basic fixes for dropdown and layout */
            .dropdown {
                position: relative;
                display: inline-block;
            }

            /* General form styling */
            /* General form styling */
            .form-container {
                background-color: #f9f9f9;
                /* Light background for contrast */
                border-radius: 8px;
                /* Rounded corners */
                padding: 20px;
                /* Spacing inside the container */
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                /* Subtle shadow for depth */
                max-width: 800px;
                /* Increased width of the form container */
                margin: auto;
                /* Center the form on the page */
            }

            /* Form group styling */
            .form-group {
                margin-bottom: 15px;
                /* Space between form groups */
            }

            .select2-container .select2-results__options {
                max-height: 200px;
                /* Adjust based on your needs */
                overflow-y: auto;
            }

            /* Adjust input box to fit form design */
            .select2-container .select2-selection--single {
                height: 38px;
                /* Match your input field height */
                display: flex;
                align-items: center;
            }

            .select2-container--default .select2-selection--single {
                border: 1px solid #ccc;
                /* Match input field border */
                border-radius: 4px;
                /* Match input field border-radius */
            }

            /* Label styling */
            .form-group label {
                font-weight: bold;
                /* Bold labels for clarity */
                margin-bottom: 5px;
                /* Space between label and input */
                display: block;
                /* Make labels block elements */
            }

            /* Input and select styling */
            input[type="text"],
            input[type="date"],
            select {
                width: 100%;
                /* Full width inputs */
                padding: 10px;
                /* Padding inside inputs for better touch target */
                border: 1px solid #ccc;
                /* Light border color */
                border-radius: 4px;
                /* Rounded input corners */
                box-sizing: border-box;
                /* Include padding in width calculation */
            }

            /* Input focus styling */
            input[type="text"]:focus,
            input[type="date"]:focus,
            select:focus {
                border-color: #007bff;
                /* Change border color on focus */
                outline: none;
                /* Remove default outline */
            }

            /* Button styling */
            .submit-btn {
                background-color: #007bff;
                /* Primary button color */
                color: white;
                /* Text color for buttons */
                padding: 10px 15px;
                /* Padding inside buttons */
                border: none;
                /* Remove default button border */
                border-radius: 4px;
                /* Rounded button corners */
                cursor: pointer;
                /* Pointer cursor on hover */
                font-size: 16px;
                /* Increase font size for buttons */
            }

            .submit-btn:hover {
                background-color: #0056b3;
                /* Darken button on hover */
            }

            /* Success message styling */
            .success-message {
                color: green;
                /* Green text for success messages */
                margin-bottom: 15px;
                /* Space below success message */
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

            label {
                color: black;
            }

            select#jenis_pelanggaran {
                max-height: 150px;
                /* Set the maximum height of the dropdown */
                overflow-y: auto;
                /* Add vertical scroll when options exceed max height */
                display: block;
                /* Ensure the dropdown behaves as a block-level element */
            }
        </style>
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
        </script>
    </head>

    <body>
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="img/logoPoltek.png" alt="Logo">
            </div>
            <div class="menu">
                <a href="dosenMenu.php" class="<?= ($current_page == 'dosenMenu.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </a>
                <a href="dsn_buatLaporan.php" class="<?= ($current_page == 'dsn_buatLaporan.php') ? 'active' : '' ?>">
                    <i class="fas fa-user"></i><span>Buat Laporan</span>
                </a>
                <a href="dsn_listLaporan.php" class="<?= ($current_page == 'dsn_listLaporan.php') ? 'active' : '' ?>">
                    <i class="fas fa-book"></i><span>List Laporan</span>
                </a>
                <a href="dsn_laporanBanding.php" class="<?= ($current_page == 'mhs_laporanBanding.php') ? 'active' : '' ?>">
                    <i class="fas fa-balance-scale"></i><span>Laporan Banding</span>
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
        <div class="main">
            <h2>Buat Laporan</h2>
            <div class="form-container">
                <?php if (isset($_GET['success'])): ?>
                    <p class="success-message">Laporan berhasil dibuat!</p>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nim_pelanggar">NIM / Nama Pelanggar</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="nim_pelanggar" name="nim_pelanggar" value="<?= htmlspecialchars($_POST['nim_pelanggar'] ?? '') ?>" required>
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
                            <option value="" disabled selected>Select Type</option>
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
    </body>

    </html>

<?php
    sqlsrv_close($conn);
} else {
    header("location: logout.php");
}
?>