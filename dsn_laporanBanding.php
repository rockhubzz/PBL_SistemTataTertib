<?php
session_start();

class Database {
    private $conn;

    public function __construct() {
        $config = parse_ini_file('db_config.ini');
        $serverName = $config['serverName'];
        $connectionInfo = array(
            "Database" => $config['database'],
            "UID" => $config['username'],
            "PWD" => $config['password']
        );
        $this->conn = sqlsrv_connect($serverName, $connectionInfo);
        if (!$this->conn) {
            die("Connection failed: " . print_r(sqlsrv_errors(), true));
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function fetchBandingData($userKey) {
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
        $stmt = sqlsrv_query($this->conn, $query, $params);
        if (!$stmt) {
            die("Query failed: " . print_r(sqlsrv_errors(), true));
        }
        return $stmt;
    }
}

if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Dosen") {
    $userKey = $_SESSION['user_key'];
    $db = new Database();
    $stmt = $db->fetchBandingData($userKey);
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Laporan Banding</title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/DLaporanBandingMain.css">
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