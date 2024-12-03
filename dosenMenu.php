<?php
    session_start();
    $current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen</title>
    <link rel="stylesheet" href="style/MenuStyles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Basic fixes for dropdown and layout */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border: 1px solid #ccc;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            z-index: 10;
        }

        .dropdown-menu a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: black;
        }

        .dropdown-menu a:hover {
            background-color: #f1f1f1;
        }

        .dropdown:hover .dropdown-menu {
            display: block;
        }

        .notification-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border: 1px solid #ccc;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            z-index: 10;
            width: 300px;
        }

        .notification-dropdown.visible {
            display: block;
        }

        .notification-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .notification-dropdown ul li {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .notification-dropdown ul li:last-child {
            border-bottom: none;
        }

        .collapsed {
            display: none;
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
        <a href="dosenMenu.php" class="<?= ($current_page == 'dosenMenu.php') ? 'active' : '' ?>">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <a href="dsn_buatLaporan.php" class="<?= ($current_page == 'dsn_buatLaporan.php') ? 'active' : '' ?>">
            <i class="fas fa-user"></i><span>Buat Laporan</span>
        </a>
        <a href="dsn_listLaporan.php" class="<?= ($current_page == 'dsn_listLaporan.php') ? 'active' : '' ?>">
            <i class="fas fa-book"></i><span>List Laporan</span>
        </a>
    </div>
</div>
    <!-- Topbar -->
    <div class="topbar" id="topbar">
        <div class="profile-notifications">
            <div class="notifications" id="notification-icon">
                <i class="fas fa-bell"></i>
                <div class="notification-dropdown" id="notification-dropdown">
                    <h4>Notifikasi</h4>
                    <ul>
                        <li>Pelanggaran baru oleh mahasiswa A.</li>
                        <li>Dosen B mengajukan revisi data.</li>
                        <li>Pengingat rapat pukul 10.00.</li>
                    </ul>
                </div>
            </div>
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
        <div class="announcement">
            <div>
                <h2>Pengumuman Penting</h2>
                <p>Terdapat pelanggaran baru yang memerlukan perhatian Anda.</p>
            </div>
            <button>Detail</button>
        </div>
        <div class="dashboard-content">
            <div class="card">
                <h3>Total Mahasiswa</h3>
                <p>500</p>
            </div>
            <div class="card">
                <h3>Total Pelanggaran</h3>
                <p>45</p>
            </div>
            <div class="card">
                <h3>Total Dosen</h3>
                <p>30</p>
            </div>
        </div>
    </div>

    <!-- Toggle Button -->
    <button class="toggle-btn" id="toggle-btn">&lt;</button>

    <script>
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('main');
        const toggleBtn = document.getElementById('toggle-btn');
        const notificationIcon = document.getElementById('notification-icon');
        const notificationDropdown = document.getElementById('notification-dropdown');

        // Sidebar toggle functionality
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            //main.classList.toggle('collapsed');
            toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '>' : '<';
        });

        // Toggle notification dropdown visibility
        notificationIcon.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevent document click handler from firing
            notificationDropdown.classList.toggle('visible');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!notificationIcon.contains(event.target)) {
                notificationDropdown.classList.remove('visible');
            }
        });
    </script>
</body>
</html>
