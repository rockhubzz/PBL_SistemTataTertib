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
        $sanksi = $_POST['sanksi'];

        $queryInsert = "INSERT INTO dbo.Sanksi (tingkat_pelanggaran, sanksi) VALUES (?, ?)";
        $params = [$tingkat_pelanggaran, $sanksi];
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
        $tingkat_pelanggaran = intval($_POST['tingkat_pelanggaran']);
        $sanksi = $_POST['sanksi'];

        $queryUpdate = "UPDATE dbo.Sanksi SET sanksi = ? WHERE tingkat_pelanggaran = ?";
        $params = [$sanksi, $tingkat_pelanggaran];
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
        $tingkat_pelanggaran = intval($_GET['delete']);

        $queryDelete = "DELETE FROM dbo.Sanksi WHERE tingkat_pelanggaran = ?";
        $params = [$tingkat_pelanggaran];
        $stmtDelete = sqlsrv_query($conn, $queryDelete, $params);

        if ($stmtDelete === false) {
            die("Delete failed: " . print_r(sqlsrv_errors(), true));
        }

        // Refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Fetch data for display
    $query = "SELECT tingkat_pelanggaran, sanksi FROM dbo.Sanksi ORDER BY tingkat_pelanggaran ASC";
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
        <link rel="stylesheet" href="style/ASanksiMain.css">
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="//cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <style>
            .main h3{
                color: black;
            }
        </style>
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
                <h2>Manage Sanksi</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main">
            <div class="table-container">
                <h3>Daftar Sanksi</h3>
                <table id="Tabel">
                    <thead>
                        <tr>
                            <th>Tingkat Pelanggaran</th>
                            <th>Sanksi</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                            <tr>
                                <form method="POST" action="">
                                    <td>
                                        <input type="hidden" name="tingkat_pelanggaran" value="<?= $row['tingkat_pelanggaran'] ?>">
                                        <p style="color: black"><?= $row['tingkat_pelanggaran'] ?></p>
                                    </td>
                                    <td>
                                        <textarea name="sanksi" class="sanksi" required><?= htmlspecialchars($row['sanksi']) ?></textarea>
                                    </td>
                                    <td>
                                        <button type="submit" name="update" class="btn">Update</button>
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
            $(document).ready(function() {
                $('#Tabel').DataTable({
                    paging: true,
                    searching: false,
                    ordering: false,
                    info: true,
                    language: {
                        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                    }
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