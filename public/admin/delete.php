<?php
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/crypto.php';
require __DIR__ . '/../../includes/layout.php';
require __DIR__ . '/../../includes/complaints.php';
require __DIR__ . '/../../config/db.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Sesi form kedaluwarsa. Coba lagi.';
    } else {
        try {
            $ok = $id > 0 && complaint_delete($id);
            header('Location: dashboard.php?status=' . ($ok ? 'deleted' : 'missing'));
            exit;
        } catch (Throwable $ex) {
            error_log($ex->getMessage());
            $error = 'Data belum terhapus karena ada gangguan di server. Coba lagi.';
        }
    }
}

try {
    $row = $id > 0 ? complaint_find($id) : null;
} catch (Throwable $ex) {
    error_log($ex->getMessage());
    $row = null;
}
if ($row === null) { header('Location: dashboard.php?status=missing'); exit; }
$data = decrypt_row($row, $_SESSION['admin_key']);

page_head('Hapus pengaduan', '../assets');
?>
<main id="isi">
  <h1>Hapus pengaduan nomor <?= (int)$row['id'] ?>?</h1>
  <p class="lede">Data dihapus permanen dari database dan tidak bisa dikembalikan.</p>

  <?php if ($error !== ''): ?>
    <div class="notice err" role="alert"><strong>Gagal.</strong> <?= e($error) ?></div>
  <?php endif; ?>

  <div class="entry">
    <p class="meta">Nomor <?= (int)$row['id'] ?> &middot; <?= e($row['created_at']) ?></p>
    <?php if ($data === null): ?>
      <p class="fail">Isinya tidak bisa dibuka dengan kunci Anda.</p>
    <?php else: ?>
      <h2><?= e($data['nama'] ?? '-') ?> (<?= e($data['nim'] ?? '-') ?>)</h2>
      <p class="body"><?= e(mb_strimwidth((string)($data['pesan'] ?? ''), 0, 300, '...')) ?></p>
    <?php endif; ?>
  </div>

  <form method="post" action="delete.php" data-busy="Menghapus..." class="row-actions">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
    <button type="submit" class="btn-danger">Ya, hapus</button>
    <a href="dashboard.php">Batal</a>
  </form>
</main>
<?php page_foot(); ?>
