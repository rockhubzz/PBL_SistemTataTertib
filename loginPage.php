<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="style/LoginStyles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
  <!-- Container -->
  <div class="container">
    <!-- Header -->
    <div class="header">
      <div class="logo">
        <img src="img/logoPoltek.png" alt="Logo Politeknik">
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
              <input type="radio" id="admin" name="role" value="Admin" required>
              <label for="admin">Admin</label>

              <input type="radio" id="dosen" name="role" value="Dosen" required>
              <label for="dosen">Dosen</label>

              <input type="radio" id="mahasiswa" name="role" value="Mahasiswa" required>
              <label for="mahasiswa">Mahasiswa</label>
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
        <?php if (!empty($errorMessage)): ?>
          <div class="error-message">
            <p style="color: red;"><?= htmlspecialchars($errorMessage); ?></p>
          </div>
        <?php endif; ?>
      </div>
      
      <!-- Additional Logo -->
      <div class="extra-logo">
        <img src="img/logoPoltek.png" alt="Logo Politeknik" class="extra-logo-img">
      </div>
    </div>

    <!-- Footer -->
    <footer>
      <p>&copy; 2024 Politeknik Negeri Malang. All rights reserved.</p>
      <div class="extra-links">
        <a href="#">Bantuan</a> | <a href="#">Kebijakan</a>
      </div>
    </footer>
  </div>
</body>
</html>
