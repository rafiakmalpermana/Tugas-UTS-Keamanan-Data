<?php
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/crypto.php';
require __DIR__ . '/../../includes/layout.php';
start_secure_session();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Sesi form kedaluwarsa. Coba lagi.';
    } else {
        $pem = '';
        if (!empty($_FILES['keyfile']['tmp_name']) && is_uploaded_file($_FILES['keyfile']['tmp_name'])) {
            if ($_FILES['keyfile']['size'] <= 8192) { $pem = file_get_contents($_FILES['keyfile']['tmp_name']); }
        } else {
            $pem = trim((string)($_POST['keytext'] ?? ''));
        }

        $cfg = require __DIR__ . '/../../config/config.php';
        $pub = file_get_contents($cfg['public_key_path']);

        usleep(random_int(200000, 400000)); // memperlambat percobaan berulang
        if ($pem !== '' && verify_private_key($pem, $pub)) {
            session_regenerate_id(true);
            $_SESSION['admin_key'] = $pem;
            $_SESSION['login_at']  = time();
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Kunci ini tidak cocok dengan yang terpasang di server.';
    }
}
$token = csrf_token();

page_head('Masuk Admin', '../assets');
?>
<main id="isi">
  <h1>Masuk admin</h1>
  <p class="lede">Masuknya pakai file kunci (.pem), bukan password.</p>

  <?php if ($error !== ''): ?>
    <div class="notice err" role="alert"><strong>Gagal masuk.</strong> <?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="login.php" enctype="multipart/form-data" data-busy="Memeriksa kunci...">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">

    <label for="keyfile">File kunci <span class="hint">Berkas .pem, maksimal 8 KB.</span></label>
    <input id="keyfile" type="file" name="keyfile" accept=".pem">

    <label for="keytext">Atau tempel isi kuncinya</label>
    <textarea id="keytext" class="pem" name="keytext" rows="8" spellcheck="false" autocomplete="off"
      placeholder="-----BEGIN PRIVATE KEY-----"></textarea>

    <div class="actions"><button type="submit">Masuk</button></div>
  </form>
</main>
<?php page_foot('<a href="../index.php">Kembali ke formulir pengaduan</a>'); ?>
