<?php
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/crypto.php';
require __DIR__ . '/../../includes/layout.php';
require __DIR__ . '/../../includes/complaints.php';
require __DIR__ . '/../../config/db.php';
require_admin();

$id  = (int)($_GET['id'] ?? 0);
$cfg = require __DIR__ . '/../../config/config.php';
$error = '';

try {
    $row = $id > 0 ? complaint_find($id) : null;
} catch (Throwable $ex) {
    error_log($ex->getMessage());
    $row = null;
}
$data = $row ? decrypt_row($row, $_SESSION['admin_key']) : null;
if ($data === null) { header('Location: dashboard.php?status=missing'); exit; }

$status    = (string)($data['status'] ?? 'Baru');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status    = (string)($_POST['status'] ?? '');

    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Halaman sudah terlalu lama terbuka. Isian Anda masih ada, simpan ulang saja.';
    } elseif (!in_array($status, STATUS_LIST, true)) {
        $error = 'Pilih salah satu status.';
    } else {
        try {
            // Identitas dan pesan mahasiswa tidak disentuh, hanya status.
            $payload = ['status' => $status, 'diubah' => date('c')] + $data;
            $pub = file_get_contents($cfg['public_key_path']);
            complaint_update($id, $payload, $pub);
            header('Location: dashboard.php?status=updated');
            exit;
        } catch (Throwable $ex) {
            error_log($ex->getMessage());
            $error = 'Status belum tersimpan karena ada gangguan di server. Coba lagi.';
        }
    }
}
$token = csrf_token();

page_head('Ubah status pengaduan nomor ' . $id, '../assets');
?>
<main id="isi">
  <h1>Ubah status pengaduan nomor <?= $id ?></h1>
  <p class="lede">Hanya status yang bisa diubah. Data mahasiswa tidak bisa diubah.</p>

  <?php if ($error !== ''): ?>
    <div class="notice err" role="alert"><strong>Belum tersimpan.</strong> <?= e($error) ?></div>
  <?php endif; ?>

  <div class="entry">
    <p class="meta">Nomor <?= (int)$row['id'] ?> &middot; <?= e($row['created_at']) ?> &middot; <?= e($data['kategori'] ?? '-') ?></p>
    <h2><?= e($data['nama'] ?? '-') ?> (<?= e($data['nim'] ?? '-') ?>)</h2>
    <p class="body"><?= e($data['pesan'] ?? '') ?></p>
  </div>

  <form class="card after-entry" method="post" action="edit.php?id=<?= $id ?>" data-busy="Menyimpan...">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">

    <label for="status">Status</label>
    <select id="status" name="status" required>
      <?php foreach (STATUS_LIST as $s): ?>
        <option <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>

    <div class="actions"><button type="submit">Simpan</button></div>
    <p class="actions"><a href="dashboard.php">Batal, kembali ke daftar</a></p>
  </form>
</main>
<?php page_foot(); ?>
