<?php
require 'includes/db.php';
require 'includes/header.php';

// Handle pembuatan postingan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    // Validasi CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($csrf_token !== $_SESSION['csrf_token']) {
        echo '<div class="alert alert-danger">Token CSRF tidak valid.</div>';
    } else {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $errors = [];

        if (empty($title)) {
            $errors[] = "Judul diperlukan.";
        }
        if (empty($content)) {
            $errors[] = "Konten diperlukan.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $title, $content])) {
                echo '<div class="alert alert-success">Postingan berhasil dibuat.</div>';
            } else {
                echo '<div class="alert alert-danger">Terjadi kesalahan saat membuat postingan.</div>';
            }
        }
    }
}

// Ambil semua postingan
$stmt = $pdo->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
$posts = $stmt->fetchAll();
?>

<h2>Forum</h2>

<?php if(isset($_SESSION['user_id'])): ?>
    <button class="btn btn-primary mb-3" data-bs-toggle="collapse" data-bs-target="#createPost">Buat Postingan Baru</button>
    <div id="createPost" class="collapse mb-4">
        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form action="index.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Judul</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Konten</label>
                <textarea class="form-control" id="content" name="content" rows="4" required><?php echo isset($content) ? htmlspecialchars($content) : ''; ?></textarea>
            </div>
            <button type="submit" class="btn btn-success">Posting</button>
        </form>
    </div>
<?php else: ?>
    <p><a href="login.php">Login</a> atau <a href="register.php">Register</a> untuk membuat postingan.</p>
<?php endif; ?>

<hr>

<?php if($posts): ?>
    <?php foreach($posts as $post): ?>
        <div class="card mb-3" id="post-<?php echo $post['id']; ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5><?php echo htmlspecialchars($post['title']); ?></h5>
                    <small>oleh <?php echo htmlspecialchars($post['username']); ?> pada <?php echo $post['created_at']; ?></small>
                </div>
                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] === $post['user_id']): ?>
                    <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <hr>
                <h6>Komentar:</h6>
                <?php
                // Handle komentar
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id']) && isset($_SESSION['user_id'])) {
                    // Validasi CSRF token
                    $csrf_token = $_POST['csrf_token'] ?? '';
                    if ($csrf_token === $_SESSION['csrf_token']) {
                        $post_id = $_POST['post_id'];
                        $comment = trim($_POST['comment']);
                        if (!empty($comment)) {
                            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
                            $stmt->execute([$post_id, $_SESSION['user_id'], $comment]);
                            // Refresh halaman untuk menampilkan komentar baru
                            header("Location: index.php#post-" . $post_id);
                            exit;
                        }
                    }
                }

                // Ambil komentar untuk setiap postingan
                $stmt_comments = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY comments.created_at ASC");
                $stmt_comments->execute([$post['id']]);
                $comments = $stmt_comments->fetchAll();
                if($comments):
                    foreach($comments as $c):
                ?>
                    <div class="mb-2">
                        <strong><?php echo htmlspecialchars($c['username']); ?></strong> <small><?php echo $c['created_at']; ?></small>
                        <p><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] === $c['user_id']): ?>
                            <a href="edit_comment.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <?php endif; ?>
                    </div>
                <?php
                    endforeach;
                else:
                    echo "<p>Belum ada komentar.</p>";
                endif;
                ?>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <form action="index.php#post-<?php echo $post['id']; ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <div class="mb-3">
                            <label for="comment" class="form-label">Tambahkan Komentar</label>
                            <textarea class="form-control" id="comment" name="comment" rows="2" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm">Komentar</button>
                    </form>
                <?php else: ?>
                    <p><a href="login.php">Login</a> untuk menambahkan komentar.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Belum ada postingan.</p>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
