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
    b.alasan AS [Alasan]
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
        <title>Data Banding</title>
        <link rel="stylesheet" href="style/MenuStyles.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            .content-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            .content-table,
            th,
            td {
                border: 1px solid #ccc;
            }

            th,
            td {
                padding: 12px;
                text-align: left;
                background-color: #244785;
            }

            th {
                background-color: #007bff;
                color: white;
            }

            tr:nth-child(even) {
                background-color: #f2f2f2;
            }

            .dashboard-content {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            #selectedMenu {
                background-color: #353f4f;
            }

            .view-btn {
                background-color: #4CAF50;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 5px;
                cursor: pointer;
            }

            .view-btn:hover {
                background-color: #45a049;
            }
        </style>
    </head>

    <body>
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="img/logoPoltek.png" alt="Logo">
            </div>
            <div class="menu">
                <a href="dosenMenu.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
                <a href="dsn_buatLaporan.php"><i class="fas fa-user"></i><span>Buat Laporan</span></a>
                <a href="dsn_listLaporan.php"><i class="fas fa-book"></i><span>List Laporan</span></a>
                <a href="dsn_laporanBanding.php"><i class="fas fa-balance-scale"></i><span>Laporan Banding</span></a>
            </div>
        </div>
        <!-- Topbar -->
        <div class="topbar" id="topbar">
            <div class="profile-notifications">
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
        <!-- Main Content -->
        <div class="main" id="main">
            <h2>Data Banding</h2>
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
                                <tr>
                                    <td><?= htmlspecialchars($row['id_banding']) ?></td>
                                    <td><?= htmlspecialchars($row['id_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($row['Nama Pengaju']) ?></td>
                                    <td><?= htmlspecialchars($row['jenis_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($row['Alasan']) ?></td>
                                    <td>
                                        <button class="view-btn" onclick="handleAppealAction(<?= $row['id_banding'] ?>, 1)">Setuju</button>
                                        <button class="view-btn" style="background-color: red" onclick="handleAppealAction(<?= $row['id_banding'] ?>, 0)">Tolak</button>
                                    </td>
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
        <!-- Toggle Button -->
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
                        } else {
                            messageBox.style.color = 'red';
                        }
                        messageBox.textContent = data.message;

                        // Optionally, reload or update the table row
                        setTimeout(() => {
                            location.reload(); // Reload after 2 seconds
                        }, 2000);
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

            const toggleSidebar = () => {
                const sidebar = document.getElementById('sidebar');
                const toggleBtn = document.getElementById('toggle-btn');
                sidebar.classList.toggle('collapsed');
                toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '>' : '<';
            };

            document.getElementById('toggle-btn').addEventListener('click', toggleSidebar);
        </script>
    </body>

    </html>
<?php
} else {
    header("location: logout.php");
}
?>