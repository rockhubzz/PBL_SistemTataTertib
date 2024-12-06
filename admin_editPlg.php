<?php
// Start the session
session_start();
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Admin") {
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

    // Handle Create
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
        $tingkat_pelanggaran = intval($_POST['tingkat_pelanggaran']);
        $deskripsi = $_POST['deskripsi'];

        $queryInsert = "INSERT INTO dbo.Opsi_Pelanggaran (tingkat_pelanggaran, deskripsi) VALUES (?, ?)";
        $params = [$tingkat_pelanggaran, $deskripsi];
        $stmtInsert = sqlsrv_query($conn, $queryInsert, $params);

        if ($stmtInsert === false) {
            die("Insert failed: " . print_r(sqlsrv_errors(), true));
        }

        // Refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Handle Update
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
        $deskripsi_original = $_POST['deskripsi_original'];
        $tingkat_pelanggaran = intval($_POST['tingkat_pelanggaran']);
        $deskripsi = $_POST['deskripsi'];

        $queryUpdate = "UPDATE dbo.Opsi_Pelanggaran SET tingkat_pelanggaran = ?, deskripsi = ? WHERE deskripsi = ?";
        $params = [$tingkat_pelanggaran, $deskripsi, $deskripsi_original];
        $stmtUpdate = sqlsrv_query($conn, $queryUpdate, $params);

        if ($stmtUpdate === false) {
            die("Update failed: " . print_r(sqlsrv_errors(), true));
        }

        // Refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Handle Delete
    if (isset($_GET['delete'])) {
        $deskripsi = $_GET['delete'];

        $queryDelete = "DELETE FROM dbo.Opsi_Pelanggaran WHERE deskripsi = ?";
        $params = [$deskripsi];
        $stmtDelete = sqlsrv_query($conn, $queryDelete, $params);

        if ($stmtDelete === false) {
            die("Delete failed: " . print_r(sqlsrv_errors(), true));
        }

        // Refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Fetch data for display
    $query = "SELECT tingkat_pelanggaran, deskripsi FROM dbo.Opsi_Pelanggaran ORDER BY tingkat_pelanggaran ASC";
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
        <title>Dashboard Admin</title>
        <link rel="stylesheet" href="style/AEditPlgMain.css">
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>

    <body>
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="img/LogoPLTK.png" alt="Logo">
            </div>
            <div class="menu">
                <a href="AdminMenu.php" class="menu-item">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </a>
                <a href="admin_kelolaMhs.php" class="menu-item">
                    <i class="fas fa-user"></i><span>Data Mahasiswa</span>
                </a>
                <a href="admin_kelolaDsn.php" class="menu-item">
                    <i class="fas fa-book"></i><span>Data Dosen</span>
                </a>
                <a href="admin_laporanMasuk.php" class="menu-item">
                    <i class="fas fa-warning"></i><span>Laporan Masuk</span>
                </a>
                <a href="admin_editPlg.php" class="menu-item">
                    <i class="fas fa-exclamation-circle"></i><span>Edit Pelanggaran</span>
                </a>
                <a href="admin_editSanksi.php" class="menu-item">
                    <i class="fas fa-gavel"></i><span>Edit Sanksi</span>
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
                <h2>Buat Opsi Pelanggaran</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main">
            <h2 style="color: black">Manage Opsi Pelanggaran</h2>
            <!-- Create Form -->
            <div class="form-container">
                <form method="POST" action="">
                    <label for="tingkat_pelanggaran">Tingkat Pelanggaran:</label>
                    <input type="number" id="tingkat_pelanggaran" name="tingkat_pelanggaran" required>
                    <br><br>
                    <label for="deskripsi">Deskripsi:</label>
                    <input type="text" id="deskripsi" name="deskripsi" required>
                    <br><br>
                    <button type="submit" name="create" class="btn">Create</button>
                </form>
            </div>

            <!-- Display Data -->
            <div class="table-container">
                <h3>Daftar Opsi Pelanggaran</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Tingkat Pelanggaran</th>
                            <th>Deskripsi</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                            <tr>
                                <form method="POST" action="">
                                    <td>
                                        <input type="number" name="tingkat_pelanggaran" class="tingkat" value="<?= $row['tingkat_pelanggaran'] ?>" required>
                                    </td>
                                    <td>
                                        <input type="hidden" name="deskripsi_original" value="<?= htmlspecialchars($row['deskripsi']) ?>">
                                        <input type="text" name="deskripsi" class="deskripsi" value="<?= htmlspecialchars($row['deskripsi']) ?>" required>
                                    </td>
                                    <td>
                                        <button type="submit" name="update"
                                         class="btn">Update</button>
                                        <a href="?delete=<?= urlencode($row['deskripsi']) ?>"
                                        class="btn delete-btn"
                                        onclick="return confirm('Are you sure?')">Delete</a>
                                    </td>
                                </form>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Close database connection -->
        <?php sqlsrv_close($conn); ?>

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