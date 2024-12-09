<?php
session_start();
if (!empty($_SESSION['user_key']) && $_SESSION['role'] == "Mahasiswa") {
    $current_page = basename($_SERVER['PHP_SELF']);
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
    $query = "
    SELECT 
        tingkat_pelanggaran, 
        jumlah_pelanggaran
    FROM 
        dbo.RangkumanPelanggaran
    WHERE 
        nim = (SELECT nim FROM Mahasiswa WHERE user_id = ?)
    ORDER BY 
        tingkat_pelanggaran;
";
    $params = [$_SESSION['user_key']];
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        die("Query failed: " . print_r(sqlsrv_errors(), true));
    }

    // Fetch results
    $violations = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $violations[] = $row;
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Mahasiswa</title>
        <link rel="stylesheet" href="style/AdminStyles.css">
        <link rel="stylesheet" href="style/MDasboardMain.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="//cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

        <script>
            const statisticsData = <?php echo json_encode($statisticsData); ?>;
        </script>

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
                <h2>Dashboard Mahasiswa</h2>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main" id="main">
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
            <!-- Tombol -->
            <div class="button-group">
                <button id="loadViolationsTable" class="load-table-btn">
                    <i class="fas fa-table"></i> Tampilkan Tabel Pelanggaran
                </button>
                <button id="showGuide" class="load-table-btn">
                    <i class="fas fa-book"></i> Panduan Tata Tertib
                </button>
                <button id="loadStatistics" class="load-table-btn">
                    <i class="fas fa-chart-bar"></i> Statistik Pelanggaran
                </button>
            </div>

            <div id="dynamicContent">
                <!-- Konten Tabel Pelanggaran -->
                <div id="violationsTableContent" class="dynamic-content">
                    <h2>Tabel Pelanggaran</h2>
                    <table id="Tabel">
                        <thead>
                            <tr>
                                <th>Tingkat Pelanggaran</th>
                                <th>Jumlah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($violations as $violation): ?>
                                <tr>
                                    <td>Tingkat <?= htmlspecialchars($violation['tingkat_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($violation['jumlah_pelanggaran']) ?></td>
                                    <td>
                                        <?php
                                        if ($violation['jumlah_pelanggaran'] != 0) {
                                            $url = "mhs_listPelanggaran.php?tingkat_pelanggaran=" . urlencode($violation['tingkat_pelanggaran']);
                                            echo "<a href='{$url}' class='view-btn'>Lihat Laporan</a>";
                                        } else {
                                            echo "<button class='disabled-btn'>Lihat Laporan</button>";
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>


                <div id="mainContent">
                    <div id="tableContent" class="dynamic-content"></div>
                    <div id="guideContent" class="dynamic-content" style="display: none;">
                        <div class="guide-image-container">
                            <img src="img/po.jpg" alt="Panduan Tata Tertib" class="guide-image">
                        </div>
                        <div class="guide-content">
                            <h2 class="guide-title">Panduan Tata Tertib Mahasiswa</h2>
                            <p class="guide-description">
                                Tata tertib kampus membantu menciptakan lingkungan yang kondusif dan mendukung keberhasilan akademik.
                                Dengan memahami dan mematuhi aturan ini, mahasiswa dapat berkontribusi dalam menjaga keharmonisan kampus.
                            </p>
                            <div class="guide-points">
                                <h3>Poin Penting:</h3>
                                <ul>
                                    <li>Hadir tepat waktu di kelas dan kegiatan kampus.</li>
                                    <li>Berpakaian sesuai aturan kampus.</li>
                                    <li>Menjaga sopan santun dalam berkomunikasi.</li>
                                    <li>Tidak merusak fasilitas kampus.</li>
                                    <li>Menghindari segala bentuk plagiarisme.</li>
                                </ul>
                            </div>
                            <a href="downloads/panduan_tata_tertib.pdf" class="download-btn" download>
                                <i class="fas fa-download"></i> Download Panduan
                            </a>
                        </div>
                    </div>

                    <!-- Konten Statistik -->
                    <div id="statisticsContent" class="dynamic-content" style="display: none;">
                        <h2>Statistik Pelanggaran</h2>
                        <p>Statistik pelanggaran berdasarkan data yang telah dikumpulkan.</p>
                        <!-- Tambahkan elemen grafik di sini -->
                    </div>
                </div>

            </div>
            <script>
                const loadTableButton = document.getElementById('loadViolationsTable');
                const showGuideButton = document.getElementById('showGuide');
                const tableContent = document.getElementById('tableContent');
                const guideContent = document.getElementById('guideContent');

                const toggleSidebar = document.getElementById('toggleSidebar');
                const sidebar = document.getElementById('sidebar');
                const header = document.getElementById('header');
                const main = document.getElementById('main');

                toggleSidebar.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                    main.classList.toggle('collapsed');
                    header.classList.toggle('collapsed');
                });


                // Fungsi untuk membersihkan konten utama
                function clearMainContent() {
                    main.innerHTML = ''; // Menghapus semua isi sebelumnya
                }

                // Fungsi untuk memuat tabel pelanggaran
                function loadViolationsTable() {
                    clearMainContent(); // Bersihkan konten sebelumnya
                    const tableContent = `
            <h2>Tabel Pelanggaran</h2>
            <table id="Tabel">
                <thead>
                    <tr>
                        <th>Tingkat Pelanggaran</th>
                        <th>Jumlah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($violations as $violation): ?>
                        <tr>
                            <td>Tingkat <?= htmlspecialchars($violation['tingkat_pelanggaran']) ?></td>
                            <td><?= htmlspecialchars($violation['jumlah_pelanggaran']) ?></td>
                            <td>
                                <?php
                                if ($violation['jumlah_pelanggaran'] != 0) {
                                    $url = "mhs_listPelanggaran.php?tingkat_pelanggaran=" . urlencode($violation['tingkat_pelanggaran']);
                                    echo "<a href='{$url}' class='view-btn'>Lihat Laporan</a>";
                                } else {
                                    echo "<button class='disabled-btn'>Lihat Laporan</button>";
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        `;
                    main.innerHTML = tableContent; // Tambahkan konten tabel
                    $('#Tabel').DataTable({
                        paging: false,
                        searching: false,
                        ordering: false,
                        info: true,
                        language: {
                            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                        }
                    });
                }

                // Fungsi untuk menampilkan panduan tata tertib
                function showGuide() {
                    clearMainContent(); // Bersihkan konten sebelumnya
                    const guideContent = `
<div id="guideContent" class="dynamic-content" style="display: none;">
    <div class="guide-section">
        <h2>Panduan Tata Tertib Mahasiswa</h2>
        <p>
            Tata tertib kampus bertujuan untuk menciptakan lingkungan belajar yang tertib, aman, dan kondusif. 
            Berikut adalah beberapa poin penting yang harus dipatuhi mahasiswa:
        </p>
        <ul>
            <li>Hadir tepat waktu untuk semua jadwal kuliah dan kegiatan kampus.</li>
            <li>Mematuhi kode etik berpakaian yang ditetapkan kampus.</li>
            <li>Menjaga kebersihan dan ketertiban lingkungan kampus.</li>
            <li>Tidak melakukan tindakan yang mengganggu proses belajar mengajar.</li>
            <li>Menghormati dosen, staf, dan sesama mahasiswa.</li>
        </ul>
        <img src="img/guidebook.jpg" alt="Panduan Tata Tertib" class="img-guide">
        <p>
            Anda dapat mengunduh panduan tata tertib lengkap melalui tautan di bawah ini:
        </p>
        <a href="downloads/panduan_tata_tertib.pdf" class="download-btn" download>Download Panduan</a>
    </div>
</div>

        `;
                    main.innerHTML = guideContent; // Tambahkan konten panduan
                }
                // Fungsi untuk menyembunyikan semua konten
                function hideAllContents() {
                    const allContents = document.querySelectorAll('.dynamic-content');
                    allContents.forEach(content => {
                        content.style.display = 'none';
                    });
                }

                // Fungsi untuk menampilkan konten berdasarkan ID
                function showContent(contentId) {
                    hideAllContents();
                    const selectedContent = document.getElementById(contentId);
                    if (selectedContent) {
                        selectedContent.style.display = 'block';
                    }
                }
                // Event listener untuk tombol
                document.getElementById('loadViolationsTable').addEventListener('click', () => {
                    showContent('violationsTableContent');
                });

                document.getElementById('showGuide').addEventListener('click', () => {
                    showContent('guideContent');
                });

                document.getElementById('loadStatistics').addEventListener('click', () => {
                    showContent('statisticsContent');
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