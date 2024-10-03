<?php
require 'includes/db.php';
require 'includes/header.php';

// Cek apakah pengguna sedang login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil ID komentar dari parameter GET
$comment_id = $_GET['id'] ?? '';

if (empty($comment_id) || !is_numeric($comment_id)) {
    echo '<div class="alert alert-danger">Comment ID tidak valid.</div>';
    require 'includes/footer.php';
    exit;
}

// Ambil data komentar dari database
$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch();

if (!$comment) {
    echo '<div class="alert alert-danger">Komentar tidak ditemukan.</div>';
    require 'includes/footer.php';
    exit;
}

// Cek apakah pengguna adalah pemilik komentar
if ($_SESSION['user_id'] !== $comment['user_id']) {
    echo '<div class="alert alert-danger">Anda tidak memiliki izin untuk mengedit komentar ini.</div>';
    require 'includes/footer.php';
    exit;
}

// Ambil data postingan terkait untuk kembali ke postingan setelah edit
$stmt_post = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$stmt_post->execute([$comment['post_id']]);
$post = $stmt_post->fetch();

$content = $comment['comment'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil dan sanitasi input
    $content = trim($_POST['comment']);
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validasi CSRF token
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $errors[] = "Token CSRF tidak valid.";
    }

    // Validasi
    if (empty($content)) {
        $errors[] = "Komentar diperlukan.";
    }

    // Jika tidak ada error, update komentar
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE comments SET comment = ? WHERE id = ?");
        if ($stmt->execute([$content, $comment_id])) {
            // Redirect ke halaman postingan terkait
            header("Location: index.php#post-" . $comment['post_id']);
            exit;
        } else {
            $errors[] = "Terjadi kesalahan saat mengupdate komentar.";
        }
    }
}
?>

<h2>Edit Komentar</h2>
<?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<form action="edit_comment.php?id=<?php echo htmlspecialchars($comment_id); ?>" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <div class="mb-3">
        <label for="comment" class="form-label">Komentar</label>
        <textarea class="form-control" id="comment" name="comment" rows="4" required><?php echo htmlspecialchars($content); ?></textarea>
    </div>
    <button type="submit" class="btn btn-success">Update Komentar</button>
    <a href="index.php#post-<?php echo $comment['post_id']; ?>" class="btn btn-secondary">Batal</a>
</form>

<?php require 'includes/footer.php'; ?>
