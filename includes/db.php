<?php
$host = 'localhost';
$db   = 'forum_reddit';
$user = 'root';
$pass = ''; // Default password untuk XAMPP adalah kosong
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Mengaktifkan exception pada error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch sebagai associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Nonaktifkan emulasi prepared statements
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Jika terjadi error koneksi, tampilkan pesan error
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
