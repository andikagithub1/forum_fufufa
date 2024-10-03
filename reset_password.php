<?php
require 'includes/db.php';
require 'includes/header.php';

$token = $_GET['token'] ?? '';
$password = $confirm_password = "";
$errors = [];
$success = "";

// Cek token
if (empty($token)) {
    echo '<div class="alert alert-danger">Token tidak valid.</div>';
    require 'includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validasi CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $errors[] = "Token CSRF tidak valid.";
    }

    // Validasi
    if (empty($password)) {
        $errors[] = "Password diperlukan.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Password dan konfirmasi tidak cocok.";
    }

    if (empty($errors)) {
        // Cari user dengan token
        $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            // Update password dan hapus token
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user['id']])) {
                $success = "Password berhasil direset. <a href='login.php'>Login sekarang</a>.";
                // Regenerate CSRF token setelah form disubmit
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $errors[] = "Terjadi kesalahan saat mereset password.";
            }
        } else {
            $errors[] = "Token tidak valid atau sudah digunakan.";
        }
    }
}
?>

<h2>Reset Password</h2>
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
<form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <div class="mb-3">
        <label for="password" class="form-label">Password Baru</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
     <div class="mb-3">
        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
    </div>
    <button type="submit" class="btn btn-primary">Reset Password</button>
</form>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
