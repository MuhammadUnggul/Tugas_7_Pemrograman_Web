<?php
session_start();
require_once __DIR__ . '/classes/GuestBook.php';

// ---- Koneksi PDO (sesuaikan dengan database kamu) ----
try {
    $pdo = new PDO('mysql:host=localhost;dbname=perpustakaan;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('Koneksi database gagal.');
}

$guestBook = new GuestBook($pdo);

// Fungsi sanitasi keluaran
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ---- Token CSRF ----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$sukses = false;
$nama = $email = $pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek token CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Token CSRF tidak valid.');
    }

    $nama  = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');

    // ---- Validasi ----
    if ($nama === '') {
        $errors[] = 'Nama tidak boleh kosong.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if (mb_strlen($pesan) < 5) {
        $errors[] = 'Pesan minimal 5 karakter.';
    }

    if (!$errors) {
        $guestBook->simpan($nama, $email, $pesan);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // token baru
        $_SESSION['flash'] = 'Pesan berhasil dikirim!';
        header('Location: guestbook.php'); // Post/Redirect/Get
        exit;
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$daftar = $guestBook->semua();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buku Tamu Perpustakaan</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 30px auto; padding: 0 15px; }
        label { display: block; margin-top: 10px; }
        input, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        button { margin-top: 12px; padding: 8px 16px; }
        .error { background: #fde8e8; color: #b00020; padding: 10px; margin: 10px 0; }
        .ok { background: #e6f4ea; color: #1e7e34; padding: 10px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Buku Tamu Perpustakaan</h1>

    <?php if ($flash): ?>
        <div class="ok"><?= e($flash) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="guestbook.php">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

        <label for="nama">Nama</label>
        <input type="text" id="nama" name="nama" value="<?= e($nama) ?>">

        <label for="email">Email</label>
        <input type="text" id="email" name="email" value="<?= e($email) ?>">

        <label for="pesan">Pesan</label>
        <textarea id="pesan" name="pesan" rows="4"><?= e($pesan) ?></textarea>

        <button type="submit">Kirim</button>
    </form>

    <h2>Daftar Pesan</h2>
    <table>
        <tr>
            <th>No</th><th>Nama</th><th>Email</th><th>Pesan</th><th>Tanggal</th>
        </tr>
        <?php if (!$daftar): ?>
            <tr><td colspan="5">Belum ada pesan.</td></tr>
        <?php endif; ?>
        <?php foreach ($daftar as $i => $row): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= e($row['nama']) ?></td>
                <td><?= e($row['email']) ?></td>
                <td><?= nl2br(e($row['pesan'])) ?></td>
                <td><?= e($row['tanggal_kirim']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
