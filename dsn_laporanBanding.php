<?php
session_start();
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Dosen") {
    // Include database configuration
    $config = parse_ini_file('db_config.ini');

    // Extract connection details
    $serverName = $config['serverName'];
    $connectionInfo = array(
        "Database" => $config['database'],
        "UID" => $config['username'],
        "PWD" => $config['password']
    );
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if (!$conn) {
        die("Connection failed: " . print_r(sqlsrv_errors(), true));
    }

    // Get the current user's key from the session
    $userKey = $_SESSION['user_key'];

    // Query to fetch data from dbo.Banding based on the relationship with dbo.Pelanggaran
    $query = "
SELECT
    b.id_banding,
    p.id_pelanggaran,
    p.jenis_pelanggaran,
    um.nama AS [Nama Pengaju],
    u.nama AS [Nama Pelapor],
    b.alasan AS [Alasan],
    b.kesepakatan AS [status]
FROM dbo.Banding b
JOIN dbo.Pelanggaran p ON p.id_pelanggaran = b.id_pelanggaran
JOIN dbo.Mahasiswa m ON b.nim_pengaju = m.nim
JOIN dbo.Users u ON u.user_id = p.reported_by_id
JOIN dbo.Users um ON um.user_id = m.user_id
WHERE u.user_id = ?
";
    $params = [$userKey];
    $stmt = sqlsrv_query($conn, $query, $params);

    if (!$stmt) {
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
        <link rel="stylesheet" href="style/DLaporanBandingMain.css">
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
                <h2>Data Banding</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
        <div class="table-container">
        <div class="report-section">
            <div class="dashboard-content">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>ID Banding</th>
                            <th>ID Pelanggaran</th>
                            <th>Pengaju</th>
                            <th>Jenis Pelanggaran</th>
                            <th>Alasan Banding</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (sqlsrv_has_rows($stmt)): ?>
                            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                                <tr data-id="<?= htmlspecialchars($row['id_banding']) ?>">
                                    <td><?= htmlspecialchars($row['id_banding']) ?></td>
                                    <td><?= htmlspecialchars($row['id_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($row['Nama Pengaju']) ?></td>
                                    <td><?= htmlspecialchars($row['jenis_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($row['Alasan']) ?></td>
                                    <?php if ($row['status'] === null): ?>
                                        <td>
                                            <button class="view-btn" onclick="handleAppealAction(<?= $row['id_banding'] ?>, 1)">Setuju</button>
                                            <button class="view-btn" style="background-color: red" onclick="handleAppealAction(<?= $row['id_banding'] ?>, 0)">Tolak</button>
                                        </td>
                                    <?php elseif ($row['status'] == 0): ?>
                                        <td>Anda menolak banding ini.</td>
                                    <?php elseif ($row['status'] == 1): ?>
                                        <td>Anda menyetujui banding ini</td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; font-weight: bold;">Tidak ada pengajuan banding</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
        </div>
        <script>
            const handleAppealAction = (idBanding, status) => {
                fetch('update_banding.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id_banding: idBanding,
                            status: status
                        }),
                    })
                    .then((response) => response.json())
                    .then((data) => {
                        const messageBox = document.getElementById('message');
                        if (data.success) {
                            messageBox.style.color = 'green';
                            messageBox.textContent = data.message;

                            // Find the corresponding table row and update it
                            const row = document.querySelector(`tr[data-id="${idBanding}"]`);
                            if (row) {
                                const actionCell = row.querySelector('td:last-child');
                                if (status === 0) {
                                    actionCell.textContent = "Anda menolak banding ini.";
                                } else if (status === 1) {
                                    actionCell.textContent = "Anda menyetujui banding ini.";
                                }
                            }
                        } else {
                            messageBox.style.color = 'red';
                            messageBox.textContent = data.message;
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                    });
            };
            const updateBanding = (id, status) => {
                fetch('update_banding.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id_banding: id,
                            status: status
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        const message = document.getElementById('message');
                        message.textContent = data.message;
                        if (data.success) {
                            message.style.color = 'green';
                        } else {
                            message.style.color = 'red';
                        }
                        setTimeout(() => {
                            location.reload();
                        }, 2000); // Reload the page after 2 seconds
                    })
                    .catch(error => console.error('Error:', error));
            };
        </script>
        <script>
            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');
            const header = document.getElementById('header');
            const main = document.getElementById('main');

            toggleSidebar.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                main.classList.toggle('collapsed');
                header.classList.toggle('collapsed');
                console.log('Sidebar collapsed:', sidebar.classList.contains('collapsed'));
            });
        </script>
    </body>
    </html>
<?php
} else {
    header("location: logout.php");
}
?>