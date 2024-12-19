<?php
// Start the session
session_start();

// Redirect if the user is not an admin
if (empty($_SESSION['user_key']) || $_SESSION['role'] !== "Admin") {
    header("Location: logout.php");
    exit;
}

// Include database configuration
$config = parse_ini_file('db_config.ini');
$serverName = $config['serverName'];
$connectionInfo = [
    "Database" => $config['database'],
    "UID" => $config['username'],
    "PWD" => $config['password']
];

// Establish a database connection
$conn = sqlsrv_connect($serverName, $connectionInfo);
if (!$conn) {
    die("Database connection failed: " . print_r(sqlsrv_errors(), true));
}

// Initialize variables
$editMode = false;
$editUser = ['user_id' => '', 'nama' => '', 'role' => '', 'nimp' => ''];

// Handle form submission for adding or editing a user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_user'])) {
    $role = htmlspecialchars(trim($_POST['role']));
    $nim_nip = htmlspecialchars(trim($_POST['nim_nip']));
    $nama = htmlspecialchars(trim($_POST['nama']));
    $userID = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;

    if (!empty($userID)) {
        // Edit user mode
        $queryEdit = "EXEC UpdateUser @UserID = ?, @NimpUser = ?, @Nama = ?, @Role = ?";
        $paramsEdit = [$userID, $nim_nip, $nama, $role];
        $stmtEdit = sqlsrv_query($conn, $queryEdit, $paramsEdit);

        if ($stmtEdit === false) {
            die("Edit failed: " . print_r(sqlsrv_errors(), true));
        }
    } else {
        // Add new user
        $queryInsert = "EXEC AddUser @NimpUser = ?, @Nama = ?, @Role = ?";
        $paramsInsert = [$nim_nip, $nama, $role];
        $stmtInsert = sqlsrv_query($conn, $queryInsert, $paramsInsert);

        if ($stmtInsert === false) {
            die("Insert failed: " . print_r(sqlsrv_errors(), true));
        }
    }

    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit;
}

// Handle delete user action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $userID = intval($_POST['user_id']);
    $queryDelete = "EXEC HapusAkun @UserID = ?";
    $paramsDelete = [$userID];
    $stmtDelete = sqlsrv_query($conn, $queryDelete, $paramsDelete);

    if ($stmtDelete === false) {
        die("Delete failed: " . print_r(sqlsrv_errors(), true));
    }

    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
    exit;
}

// Handle edit user action
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['edit_user'])) {
    $userID = intval($_GET['edit_user']);
    $queryGetUser = "EXEC GetEditUser @UserID = ?";
    $paramsGetUser = [$userID];
    $stmtGetUser = sqlsrv_query($conn, $queryGetUser, $paramsGetUser);

    if ($stmtGetUser === false) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }

    $editUser = sqlsrv_fetch_array($stmtGetUser, SQLSRV_FETCH_ASSOC);
    $editMode = true;
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
    <title>Manage User</title>
    <link rel="stylesheet" href="style/AdminStyles.css">
    <link rel="stylesheet" href="style/AbuatAkunMain.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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
            <h2>Edit Laporan</h2>
        </div>
    </div>
    <div class="main">
        <!-- Main content -->
        <div class="content-container">
            <div class="user-list-container">
                <h2>Daftar User</h2>

                <!-- Filter Dropdown -->
                <div class="filter-container">
                    <label for="filterRole">Filter Role:</label>
                    <select id="filterRole">
                        <option value="">Semua</option>
                        <option value="Admin">Admin</option>
                        <option value="Dosen">Dosen</option>
                        <option value="Mahasiswa">Mahasiswa</option>
                    </select>
                </div>


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
                                    <!-- Delete User Form -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['user_id']); ?>">
                                        <button type="submit" name="delete_user" class="delete-btn">Delete</button>
                                    </form>

                                    <!-- Edit User Form -->
                                    <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="display: inline;">
                                        <input type="hidden" name="edit_user" value="<?php echo htmlspecialchars($row['user_id']); ?>">
                                        <button type="submit" class="edit-btn">Edit</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-container">
                <h2><?php echo $editMode ? "Edit User" : "Tambah User"; ?></h2>
                <?php if (isset($_GET['success'])): ?>
                    <p class="success-message">User berhasil ditambahkan!</p>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <p class="delete-message">User berhasil dihapus!</p>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($editUser['user_id'] ?? ''); ?>">
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required <?php if ($editMode): echo 'disabled';
                                                                endif; ?>>
                            <option value="Admin" <?php echo $editUser['role'] == "Admin" ? "selected" : ""; ?>>Admin</option>
                            <option value="Dosen" <?php echo $editUser['role'] == "Dosen" ? "selected" : ""; ?>>Dosen</option>
                            <option value="Mahasiswa" <?php echo $editUser['role'] == "Mahasiswa" ? "selected" : ""; ?>>Mahasiswa</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nim_nip">NIM/NIP</label>
                        <input type="text" id="nim_nip" name="nim_nip" value="<?php echo htmlspecialchars($editUser['nimp'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($editUser['nama'] ?? ''); ?>" required>
                    </div>
                    <!-- Submit and Cancel -->
                    <div class="button-container">
    <button type="submit" name="submit_user" class="submit-btn">
        <?php echo $editMode ? "Update User" : "Tambah User"; ?>
    </button>
    <?php if ($editMode): ?>
        <a href="admin_buatAkun.php" class="cancel-btn">Batal</a>
    <?php endif; ?>
</div>

                </form>
            </div>
        </div>
    </div>
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

    $(document).ready(function () {
        // Inisialisasi DataTable
        const table = $('#Tabel').DataTable({
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
            }
        });

        // Filter Role
        $('#filterRole').on('change', function () {
            const role = $(this).val(); // Ambil nilai dari dropdown filter
            table.column(2).search(role).draw(); // Kolom index 2 adalah kolom "Role"
        });
    });
        
    </script>
    
</body>

</html>