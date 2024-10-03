<?php
require 'includes/db.php';
require 'includes/header.php';

$email = "";
$success = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validasi CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $errors[] = "Token CSRF tidak valid.";
    }

    if (empty($email)) {
        $errors[] = "Email diperlukan.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    } else {
        // Cek apakah email ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Buat token reset
            $token = bin2hex(random_bytes(50));
            // Simpan token ke database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
            if ($stmt->execute([$token, $email])) {
                // Kirim email dengan link reset (disini hanya ditampilkan)
                $reset_link = "http://localhost/forum-reddit/reset_password.php?token=$token";
                // Sebagai contoh, kita tampilkan linknya
                $success = "Link reset password: <a href='$reset_link'>$reset_link</a>";
                // Sebaiknya, kirim email sebenarnya di sini menggunakan PHPMailer atau library lain
            } else {
                $errors[] = "Terjadi kesalahan saat membuat token reset.";
            }
        } else {
            $errors[] = "Email tidak ditemukan.";
        }
    }
}
?>

<h2>Lupa Password</h2>
<?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if($success): ?>
    <div class="alert alert-success">
        <?php echo $success; ?>
    </div>
<?php else: ?>
<form action="forgot_password.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <div class="mb-3">
        <label for="email" class="form-label">Masukkan Email Anda</label>
        <input type="email" class="form-control" id="email" name="email" 
               value="<?php echo htmlspecialchars($email); ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Kirim Link Reset</button>
</form>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
