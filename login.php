<?php
require 'includes/db.php';
require 'includes/header.php';

$email_or_username = $password = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil dan sanitasi input
    $email_or_username = trim($_POST['email_or_username']);
    $password = $_POST['password'];
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validasi CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $errors[] = "Token CSRF tidak valid.";
    }

    // Validasi
    if (empty($email_or_username)) {
        $errors[] = "Email atau Username diperlukan.";
    }
    if (empty($password)) {
        $errors[] = "Password diperlukan.";
    }

    // Cek jika tidak ada error
    if (empty($errors)) {
        // Cari user berdasarkan email atau username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email_or_username, $email_or_username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            // Regenerate session ID untuk keamanan
            session_regenerate_id(true);
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Email/Username atau password salah.";
        }
    }
}
?>

<h2>Login</h2>
<?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<form action="login.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <div class="mb-3">
        <label for="email_or_username" class="form-label">Email atau Username</label>
        <input type="text" class="form-control" id="email_or_username" name="email_or_username" 
               value="<?php echo htmlspecialchars($email_or_username); ?>" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <div class="mb-3">
        <a href="forgot_password.php">Lupa Password?</a>
    </div>
    <button type="submit" class="btn btn-primary">Login</button>
</form>

<?php require 'includes/footer.php'; ?>
