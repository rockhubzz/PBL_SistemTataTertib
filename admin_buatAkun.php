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

    // Handle form submission for adding a user
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_user'])) {
        $role = $_POST['role'];
        $nim_nip = $_POST['nim_nip'];
        $nama = $_POST['nama'];

        // Insert into database
        $queryInsert = "EXEC AddUser @NimpUser = ?, @nama = ?, @Role = ?";
        $params = [$nim_nip, $nama, $role];

        $stmtInsert = sqlsrv_query($conn, $queryInsert, $params);
        if ($stmtInsert === false) {
            die("Insert failed: " . print_r(sqlsrv_errors(), true));
        }

        // Redirect to the same page with a success message
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    }

    // Handle delete user action
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
        $userID = $_POST['user_id'];

        // Execute delete query
        $queryDelete = "EXEC DelUser @UserID = ?";
        $paramsDelete = [$userID];

        $stmtDelete = sqlsrv_query($conn, $queryDelete, $paramsDelete);
        if ($stmtDelete === false) {
            die("Delete failed: " . print_r(sqlsrv_errors(), true));
        }

        // Redirect to the same page to refresh the table
        header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
        exit;
    }

    // Fetch existing users
    $querySelect = "SELECT user_id, nama, role FROM Users WHERE user_id <> ? ORDER BY role";
    $params = [$_SESSION['user_key']];
    $stmtSelect = sqlsrv_query($conn, $querySelect, $params);
    if ($stmtSelect === false) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Admin</title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/AbuatAkunMain.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="//cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
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
                <a href="admin_SPMasuk.php" class="menu-item">
                    <i class="fas fa-envelope"></i><span>SP masuk</span>
                </a>
                <a href="admin_buatAkun.php" class="menu-item">
                    <i class="fas fa-user-cog"></i><span>Manage Akun</span>
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
                <h2>Tambah User</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
            <div class="content-container">
                <div class="user-list-container">
                    <h2>Daftar User</h2>
                    <table id="Tabel">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Nama</th>
                                <th>Role</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = sqlsrv_fetch_array($stmtSelect, SQLSRV_FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['user_id']); ?>">
                                            <button type="submit" name="delete_user" class="delete-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-container">
                    <h2>Tambah User</h2>
                    <?php if (isset($_GET['success'])): ?>
                        <p class="success-message">User berhasil ditambahkan!</p>
                    <?php endif; ?>
                    <?php if (isset($_GET['deleted'])): ?>
                        <p class="delete-message">User berhasil dihapus!</p>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <option value="" disabled selected>Select Role</option>
                                <option value="Admin">Admin</option>
                                <option value="Dosen">Dosen</option>
                                <option value="Mahasiswa">Mahasiswa</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="nim_nip">NIM/NIP</label>
                            <input type="text" id="nim_nip" name="nim_nip" required>
                        </div>

                        <div class="form-group">
                            <label for="nama">Nama</label>
                            <input type="text" id="nama" name="nama" required>
                        </div>

                        <button type="submit" name="submit_user" class="submit-btn">Tambah User</button>
                    </form>
                </div>
            </div>
        </div>

        <?php sqlsrv_close($conn); ?>

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
            $(document).ready(function() {
                $('#Tabel').DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
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