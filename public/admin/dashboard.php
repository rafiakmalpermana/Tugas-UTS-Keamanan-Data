<?php
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/crypto.php';
require __DIR__ . '/../../includes/layout.php';
require __DIR__ . '/../../includes/complaints.php';
require __DIR__ . '/../../config/db.php';
require_admin();

$flash = [
    'updated' => 'Status berhasil diperbarui.',
    'deleted' => 'Pengaduan berhasil dihapus.',
][$_GET['status'] ?? ''] ?? '';
$missing = (($_GET['status'] ?? '') === 'missing');

$q  = trim((string)($_GET['q'] ?? ''));
$fs = (string)($_GET['s'] ?? '');
if (!in_array($fs, STATUS_LIST, true)) { $fs = ''; }

$loadError = false;
$items = [];
try {
    $rows = db()->query(
        'SELECT id, ciphertext, iv, tag, wrapped_key, created_at FROM complaints ORDER BY id DESC LIMIT 200'
    )->fetchAll();
    foreach ($rows as $r) {
        $data = decrypt_row($r, $_SESSION['admin_key']);
        // Penyaringan dilakukan setelah dekripsi karena isi di database tidak bisa dicari lewat SQL.
        if ($fs !== '' && ($data === null || ($data['status'] ?? 'Baru') !== $fs)) { continue; }
        if ($q !== '' && ($data === null || mb_stripos(implode(' ', [$data['nama'] ?? '', $data['nim'] ?? '', $data['pesan'] ?? '']), $q) === false)) { continue; }
        $items[] = ['id' => $r['id'], 'at' => $r['created_at'], 'data' => $data];
    }
} catch (Throwable $ex) {
    error_log($ex->getMessage());
    $loadError = true;
}

$logout = '<form method="post" action="logout.php"><input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
        . '<button type="submit" class="btn-quiet">Keluar</button></form>';

page_head('Dashboard Admin', '../assets', $logout, 'dash');
?>
<main id="isi">
  <h1>Pengaduan masuk</h1>
  <p class="lede">
    <?= ($q !== '' || $fs !== '') ? 'Ditemukan' : 'Total' ?> <?= count($items) ?> pengaduan, terbaru di atas.
  </p>

  <?php if ($flash !== ''): ?>
    <div class="notice ok" role="status"><?= e($flash) ?></div>
  <?php endif; ?>
  <?php if ($missing): ?>
    <div class="notice err" role="alert">Pengaduan tidak ditemukan atau sudah dihapus.</div>
  <?php endif; ?>

  <div class="toolbar">
    <form class="filter" method="get" action="dashboard.php" role="search">
      <label for="q" class="sr">Cari nama, NIM, atau isi pesan</label>
      <input id="q" name="q" type="search" placeholder="Cari nama, NIM, atau pesan" value="<?= e($q) ?>">
      <label for="s" class="sr">Status</label>
      <select id="s" name="s">
        <option value="">Semua status</option>
        <?php foreach (STATUS_LIST as $st): ?>
          <option <?= $fs === $st ? 'selected' : '' ?>><?= e($st) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-search">Cari</button>
      <?php if ($q !== '' || $fs !== ''): ?><a class="reset" href="dashboard.php">Hapus filter</a><?php endif; ?>
    </form>
  </div>

  <?php if ($loadError): ?>
    <div class="notice err" role="alert"><strong>Data gagal dimuat.</strong> Periksa pengaturan database di config/config.php.</div>
  <?php elseif (!$items): ?>
    <div class="empty"><?= ($q !== '' || $fs !== '') ? 'Tidak ada pengaduan yang cocok.' : 'Belum ada pengaduan.' ?></div>
  <?php else: ?>
    <ol class="list">
    <?php foreach ($items as $it): ?>
      <li class="entry">
        <div class="entry-main">
        <?php if ($it['data'] === null): ?>
          <p class="meta">Nomor <?= (int)$it['id'] ?> &middot; <?= e($it['at']) ?></p>
          <p class="fail">Tidak bisa dibuka. Kunci tidak cocok atau data sudah rusak.</p>
        <?php else: $stt = (string)($it['data']['status'] ?? 'Baru'); ?>
          <div class="entry-head">
            <h2><?= e($it['data']['nama'] ?? '-') ?> <span class="nim"><?= e($it['data']['nim'] ?? '-') ?></span></h2>
            <span class="badge badge-<?= e(strtolower($stt)) ?>"><?= e($stt) ?></span>
          </div>
          <p class="meta">Nomor <?= (int)$it['id'] ?> &middot; <?= e($it['at']) ?> &middot; <?= e($it['data']['kategori'] ?? '-') ?></p>
          <p class="body"><?= e($it['data']['pesan'] ?? '') ?></p>
        <?php endif; ?>
        </div>
        <div class="entry-actions">
          <?php if ($it['data'] !== null): ?>
            <a class="btn-quiet btn-link" href="edit.php?id=<?= (int)$it['id'] ?>">Ubah status</a>
          <?php endif; ?>
          <a class="btn-quiet btn-link btn-link-danger" href="delete.php?id=<?= (int)$it['id'] ?>">Hapus</a>
        </div>
      </li>
    <?php endforeach; ?>
    </ol>
  <?php endif; ?>
</main>
<?php page_foot('Sesi berakhir otomatis 20 menit setelah login.'); ?>
