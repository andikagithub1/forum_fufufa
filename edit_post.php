<?php
require 'includes/db.php';
require 'includes/header.php';

// Cek apakah pengguna sedang login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil ID postingan dari parameter GET
$post_id = $_GET['id'] ?? '';

if (empty($post_id) || !is_numeric($post_id)) {
    echo '<div class="alert alert-danger">Post ID tidak valid.</div>';
    require 'includes/footer.php';
    exit;
}

// Ambil data postingan dari database
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    echo '<div class="alert alert-danger">Post tidak ditemukan.</div>';
    require 'includes/footer.php';
    exit;
}

// Cek apakah pengguna adalah pemilik postingan
if ($_SESSION['user_id'] !== $post['user_id']) {
    echo '<div class="alert alert-danger">Anda tidak memiliki izin untuk mengedit postingan ini.</div>';
    require 'includes/footer.php';
    exit;
}

$title = $post['title'];
$content = $post['content'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil dan sanitasi input
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validasi CSRF token
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $errors[] = "Token CSRF tidak valid.";
    }

    // Validasi
    if (empty($title)) {
        $errors[] = "Judul diperlukan.";
    }
    if (empty($content)) {
        $errors[] = "Konten diperlukan.";
    }

    // Jika tidak ada error, update postingan
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
        if ($stmt->execute([$title, $content, $post_id])) {
            echo '<div class="alert alert-success">Postingan berhasil diupdate. <a href="index.php">Kembali ke Forum</a>.</div>';
            // Optional: Redirect setelah beberapa detik
            // header("Refresh:3; url=index.php");
            // exit;
        } else {
            $errors[] = "Terjadi kesalahan saat mengupdate postingan.";
        }
    }
}
?>

<h2>Edit Postingan</h2>
<?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<form action="edit_post.php?id=<?php echo htmlspecialchars($post_id); ?>" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <div class="mb-3">
        <label for="title" class="form-label">Judul</label>
        <input type="text" class="form-control" id="title" name="title" 
               value="<?php echo htmlspecialchars($title); ?>" required>
    </div>
    <div class="mb-3">
        <label for="content" class="form-label">Konten</label>
        <textarea class="form-control" id="content" name="content" rows="5" required><?php echo htmlspecialchars($content); ?></textarea>
    </div>
    <button type="submit" class="btn btn-success">Update Postingan</button>
    <a href="index.php" class="btn btn-secondary">Batal</a>
</form>

<?php require 'includes/footer.php'; ?>
