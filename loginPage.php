<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="style/LoginStyles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* Add this CSS to ensure footer is at the bottom */
    html, body {
      height: 100%; /* Full height for body */
      margin: 0; /* Remove default margin */
    }
    .container {
      display: flex;
      flex-direction: column; /* Stack children vertically */
      min-height: 100vh; /* Full viewport height */
    }
    .content {
      flex: 1; /* Allow content to grow and fill space */
    }
    footer {
      text-align: center; /* Center text in footer */
      padding: 10px; /* Padding for footer */
    }
  </style>
</head>
<body>
  <!-- Container -->
  <div class="container">
    <!-- Header -->
    <div class="header">
      <div class="logo">
        <img src="img/LogoPLTK.png" alt="Logo Politeknik">
      </div>
      <div class="title">
        <h1>Politeknik Negeri Malang</h1>
        <h2>Login Sistem</h2>
      </div>
    </div>

    <!-- Content -->
    <div class="content">
      <!-- Left Section -->
      <div class="left-middle">
        <h1>Masuk ke Sistem</h1>
        <p>Pilih login sebagai Admin, Dosen, atau Mahasiswa.</p>
        
        <!-- Login Form -->
        <form action="login.php" method="POST">
          <!-- Role Selection -->
          <div class="user-options">
            <label class="option">
              <input type="radio" id="admin" name="role" value="Admin" required>
              <span class="option-background">Admin</span>
            </label>

            <label class="option">
              <input type="radio" id="dosen" name="role" value="Dosen" required>
              <span class="option-background">Dosen</span>
            </label>

            <label class="option">
              <input type="radio" id="mahasiswa" name="role" value="Mahasiswa" required>
              <span class="option-background">Mahasiswa</span>
            </label>
          </div>

          <!-- User Login -->
          <div class="input-group">
            <input type="text" name="username" placeholder="Masukkan ID" required>
          </div>
          <div class="input-group">
            <input type="password" name="password" placeholder="Masukkan Password" required>
          </div>
          <button type="submit" class="btn">MASUK</button>
        </form>

        <!-- Error Message -->
        <div class="error-message">
          <?php if (isset($_GET['error']) && $_GET['error'] == 'invalid_credentials'): ?>
            <p style="color: red;">Username atau password salah. Silakan coba lagi.</p>
          <?php endif; ?>
        </div>

        <!-- Additional Logo -->
        <div class="extra-logo">
          <img src="img/LogoPLTK.png" alt="Logo Politeknik" class="extra-logo-img">
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer>
      <p>&copy; 2024 Politeknik Negeri Malang. All rights reserved.</p>
    </footer>
  </div>
</body>
</html>