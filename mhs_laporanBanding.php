<?php
session_start();
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Mahasiswa") {
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
        <title>Laporan Banding</title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/MLaporanBandingMain.css">
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
                <h2>Laporan Banding</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
            <div class="table-container">
                <div class="report-section">
                    <div class="dashboard-content">
                        <table id="Tabel">
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
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <button class="toggle-btn" id="toggle-btn">&lt;</button>
        <div id="message" style="margin-top: 20px; color: green; text-align: center;"></div>
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
    sqlsrv_close($conn);
} else {
    header("location: logout.php");
}
?>