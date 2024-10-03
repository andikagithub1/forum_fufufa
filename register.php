<?php
require 'includes/db.php';
require 'includes/header.php';

$username = $email = $password = $confirm_password = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Token CSRF tidak valid.";
    }

    // Ambil dan sanitasi input
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi
    if (empty($username)) {
        $errors[] = "Username diperlukan.";
    }
    if (empty($email)) {
        $errors[] = "Email diperlukan.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }
    if (empty($password)) {
        $errors[] = "Password diperlukan.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Password dan konfirmasi tidak cocok.";
    }

    // Cek jika tidak ada error
    if (empty($errors)) {
        // Cek apakah username atau email sudah ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Username atau email sudah digunakan.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Insert ke database
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password])) {
                echo '<div class="alert alert-success">Registrasi berhasil. <a href="login.php">Login sekarang</a>.</div>';
                // Reset variabel
                $username = $email = $password = $confirm_password = "";
                // Regenerate CSRF token setelah form berhasil disubmit
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $errors[] = "Terjadi kesalahan saat mendaftar.";
            }
        }
    }
}
?>

<h2>Register</h2>
<?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<form action="register.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" 
               value="<?php echo htmlspecialchars($username); ?>" required>
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" 
               value="<?php echo htmlspecialchars($email); ?>" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
     <div class="mb-3">
        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
    </div>
    <button type="submit" class="btn btn-primary">Register</button>
</form>
<!-- Tambahkan enkripsi form untuk mengizinkan upload file -->
<form action="register.php" method="post" enctype="multipart/form-data">
    <!-- Form sebelumnya -->
    <div class="mb-3">
        <label for="profile_image" class="form-label">Foto Profil</label>
        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
    </div>
   
</form>


<?php require 'includes/footer.php'; ?>
