<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki role Admin
$config = parse_ini_file('db_config.ini');
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Admin") {
    // Extract connection details
    $serverName = $config['serverName'];
    $connectionInfo = array(
        "Database" => $config['database'],
        "UID" => $config['username'],
        "PWD" => $config['password']
    );

    // Membuat koneksi ke database
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    
    // Cek koneksi
    if (!$conn) {
        die("Connection failed: " . print_r(sqlsrv_errors(), true));
    }
    $current_page = basename($_SERVER['PHP_SELF']);
    // Query untuk mendapatkan data total mahasiswa, pelanggaran, dan dosen
    $query = "
        SELECT COUNT(nim) AS jml_mhs,
               (SELECT COUNT(id_pelanggaran) FROM dbo.Pelanggaran) AS jml_pelanggaran,
               (SELECT COUNT(nip) FROM dbo.Dosen) AS jml_dosen
        FROM dbo.Mahasiswa";
    
    $stmt = sqlsrv_query($conn, $query);
    if ($stmt === false) {
        die("Error executing query: " . print_r(sqlsrv_errors(), true));
    }
    
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $jml_mhs = $row['jml_mhs'];
    $jml_plg = $row['jml_pelanggaran'];
    $jml_dsn = $row['jml_dosen'];

    // Query untuk mendapatkan statistik kategori pelanggaran
    $query_stats = "
        SELECT jenis_pelanggaran, COUNT(*) as jumlah
        FROM dbo.Pelanggaran
        GROUP BY jenis_pelanggaran";
    
    $stmt_stats = sqlsrv_query($conn, $query_stats);
    if ($stmt_stats === false) {
        die("Error executing stats query: " . print_r(sqlsrv_errors(), true));
    }

    $stats = [];
    while ($row_stats = sqlsrv_fetch_array($stmt_stats, SQLSRV_FETCH_ASSOC)) {
        $stats[] = $row_stats;
    }

    // Encode data statistik untuk digunakan di frontend (JSON)
    $json_stats = json_encode($stats);

    // Query untuk mendapatkan statistik tingkat pelanggaran
    $query_tingkat = "
        SELECT tingkat_pelanggaran, COUNT(*) as jumlah
        FROM dbo.Pelanggaran
        GROUP BY tingkat_pelanggaran";

    $stmt_tingkat = sqlsrv_query($conn, $query_tingkat);
    if ($stmt_tingkat === false) {
        die("Error executing tingkat query: " . print_r(sqlsrv_errors(), true));
    }

    $tingkat = [];
    while ($row_tingkat = sqlsrv_fetch_array($stmt_tingkat, SQLSRV_FETCH_ASSOC)) {
        $tingkat[] = $row_tingkat;
    }

    // Encode data statistik tingkat pelanggaran untuk digunakan di frontend (JSON)
    $json_tingkat = json_encode($tingkat);

    // Menutup koneksi SQL Server
    sqlsrv_close($conn);
} else {
    // Jika pengguna tidak memiliki session atau bukan admin, arahkan ke halaman logout
    header("location: logout.php");
}
?>


    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Admin</title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/ADasboardMain.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
<script>
    console.log(statsData); // Untuk memastikan data yang diterima

</script>
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
                <h2>Dashboard Admin</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
    <div class="cards"></div>
    <!-- Card: Total Mahasiswa -->
    <a href="admin_kelolaMhs.php" class="card">
        <div class="card">
            <div class="icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="details">
                <h3>Total Mahasiswa</h3>
                <p><?php echo $jml_mhs; ?></p>
            </div>
        </div>
    </a>
    
    <!-- Card: Total Dosen -->
    <a href="admin_kelolaDsn.php" class="card">
        <div class="card">
            <div class="icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="details">
                <h3>Total Dosen</h3>
                <p><?php echo $jml_dsn; ?></p>
            </div>
        </div>
    </a>
    
    <!-- Card: Total Pelanggaran -->
    <a href="admin_laporanMasuk.php" class="card">
        <div class="card">
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="details">
                <h3>Total Pelanggaran</h3>
                <p><?php echo $jml_plg; ?></p>
            </div>
        </div>
    </a>



                  <!-- Section 1: Informasi Penting -->
        <div class="important-info">
          <div class="info-box">
            <img src="img/LogoPLTK.png" alt="Tata Tertib" class="info-image">
            <h3>Pentingnya Tata Tertib</h3>
            <p>
              Tata tertib membantu menciptakan lingkungan yang kondusif untuk belajar. 
              Pelanggaran tata tertib dapat mempengaruhi akademik dan reputasi mahasiswa.
            </p>
          </div>
        </div>
                <!-- Section 2: Grafik Statistik -->
<!-- Section untuk Grafik Statistik Pelanggaran -->
<div class="statistics">
    <h3>Statistik Pelanggaran</h3>
    <div class="charts">
        <!-- Pie chart untuk kategori pelanggaran -->
        <canvas id="violationsPieChart" width="400" height="400"></canvas>
        <!-- Bar chart untuk kategori pelanggaran -->
        <canvas id="violationsBarChart" width="400" height="400"></canvas>
        <!-- Bar chart untuk tingkat pelanggaran -->
        <canvas id="violationsLevelBarChart" width="400" height="400"></canvas>
    </div>
</div>
</div>



<script>
    const statsData = <?php echo $json_stats; ?>; // Data statistik kategori pelanggaran
    const tingkatData = <?php echo $json_tingkat; ?>; // Data statistik tingkat pelanggaran

    // Persiapkan data untuk Pie Chart (Kategori Pelanggaran)
    const categories = statsData.map(stat => stat.jenis_pelanggaran); // Kategori pelanggaran
    const counts = statsData.map(stat => stat.jumlah); // Jumlah pelanggaran per kategori

    // Pie Chart untuk Kategori Pelanggaran
    const pieChartCtx = document.getElementById('violationsPieChart').getContext('2d');
    const pieChart = new Chart(pieChartCtx, {
        type: 'pie',
        data: {
            labels: categories,
            datasets: [{
                label: 'Jumlah Pelanggaran per Kategori',
                data: counts,
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
            }]
        },
        options: {
            responsive: true
        }
    });

    // Bar Chart untuk Kategori Pelanggaran
    const barChartCtx = document.getElementById('violationsBarChart').getContext('2d');
    const barChart = new Chart(barChartCtx, {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [{
                label: 'Jumlah Pelanggaran per Kategori',
                data: counts,
                backgroundColor: '#4BC0C0',
                borderColor: '#36A2EB',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Persiapkan data untuk Bar Chart (Tingkat Pelanggaran)
    const tingkatLabels = tingkatData.map(stat => stat.tingkat_pelanggaran); // Tingkat pelanggaran
    const tingkatCounts = tingkatData.map(stat => stat.jumlah); // Jumlah pelanggaran per tingkat
    const pastelColors = [
    '#ff9aa2', '#ffb7b2', '#ffdac1', '#e2f0cb', '#b5ead7', '#c7ceea', '#d5a6bd'
];
    // Bar Chart untuk Tingkat Pelanggaran
    const tingkatBarChartCtx = document.getElementById('violationsLevelBarChart').getContext('2d');
    const tingkatBarChart = new Chart(tingkatBarChartCtx, {
        type: 'bar',
        data: {
            labels: tingkatLabels,
            datasets: [{
                label: 'Jumlah Pelanggaran per Tingkat',
                data: tingkatCounts,
                backgroundColor: pastelColors, // Warna pelangi untuk setiap batang
                borderColor: '#333',
                borderWidth: 1
            }]
        },
        responsive: true,
        scales: {
            x: {
                ticks: {
                    color: '#333'
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#333'
                }
            }
        },
        plugins: {
            legend: {
                labels: {
                    color: '#333'
                }
            }
        }
    });

            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');
            const header = document.getElementById('header');
            const main = document.getElementById('main');

            toggleSidebar.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                main.classList.toggle('collapsed');
                header.classList.toggle('collapsed');
            });
        </script>
    </body>

    </html>
<?php
?>
<script>

    const statsData = <?php echo $json_stats; ?>;
</script>
