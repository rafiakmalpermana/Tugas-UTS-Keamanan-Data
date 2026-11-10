<?php
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/crypto.php';
require __DIR__ . '/../../includes/layout.php';
require __DIR__ . '/../../includes/complaints.php';
require __DIR__ . '/../../config/db.php';
require_admin();

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;
$cfg     = require __DIR__ . '/../../config/config.php';
$error   = '';
$existing = [];
$form = ['nama' => '', 'nim' => '', 'kategori' => '', 'pesan' => '', 'status' => 'Baru'];

if ($editing) {
    try {
        $row = complaint_find($id);
    } catch (Throwable $ex) {
        error_log($ex->getMessage());
        $row = null;
    }
    $existing = $row ? decrypt_row($row, $_SESSION['admin_key']) : null;
    if ($existing === null) {
        http_response_code(404);
        header('Location: dashboard.php?status=missing');
        exit;
    }
    $form = array_merge($form, array_intersect_key($existing, $form));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$clean, $invalid] = validate_complaint($_POST);
    $status = (string)($_POST['status'] ?? '');
    $form = $clean + ['status' => $status];

    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Sesi form kedaluwarsa. Isian Anda masih ada, simpan ulang saja.';
    } elseif ($invalid !== '') {
        $error = $invalid;
    } elseif (!in_array($status, STATUS_LIST, true)) {
        $error = 'Pilih salah satu status.';
    } else {
        try {
            $pub = file_get_contents($cfg['public_key_path']);
            if ($editing) {
                $payload = $clean + ['status' => $status] + $existing + ['waktu' => date('c')];
                $payload['diubah'] = date('c');
                if (!complaint_update($id, $payload, $pub)) {
                    // rowCount 0 juga terjadi bila isi identik; pastikan baris memang masih ada
                    if (complaint_find($id) === null) { header('Location: dashboard.php?status=missing'); exit; }
                }
                header('Location: dashboard.php?status=updated');
            } else {
                complaint_insert($clean + ['status' => $status, 'waktu' => date('c'), 'dibuat_admin' => true], $pub);
                header('Location: dashboard.php?status=created');
            }
            exit;
        } catch (Throwable $ex) {
            error_log($ex->getMessage());
            $error = 'Data belum tersimpan karena ada gangguan di server. Isian Anda masih ada, coba lagi.';
        }
    }
}
$token = csrf_token();

$title = $editing ? 'Ubah pengaduan nomor ' . $id : 'Tambah pengaduan';
page_head($title, '../assets');
?>
<main id="isi">
  <h1><?= e($title) ?></h1>
  <p class="lede">Perubahan dienkripsi ulang dengan kunci publik server sebelum disimpan.</p>

  <?php if ($error !== ''): ?>
    <div class="notice err" role="alert"><strong>Belum tersimpan.</strong> <?= e($error) ?></div>
  <?php endif; ?>

  <form class="card" method="post" action="form.php<?= $editing ? '?id=' . $id : '' ?>" data-busy="Menyimpan...">
    <input type="hidden" name="csrf" value="<?= e($token) ?>">

    <fieldset>
      <legend>Identitas</legend>
      <div class="row2">
        <div>
          <label for="nama">Nama lengkap</label>
          <input id="nama" name="nama" maxlength="100" required value="<?= e($form['nama']) ?>">
        </div>
        <div>
          <label for="nim">NIM</label>
          <input id="nim" name="nim" maxlength="30" required value="<?= e($form['nim']) ?>">
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Pengaduan</legend>
      <div class="row2">
        <div>
          <label for="kategori">Kategori</label>
          <select id="kategori" name="kategori" required>
            <option value="" disabled <?= $form['kategori'] === '' ? 'selected' : '' ?>>Pilih kategori</option>
            <?php foreach (KATEGORI_LIST as $k): ?>
              <option <?= $form['kategori'] === $k ? 'selected' : '' ?>><?= e($k) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="status">Status</label>
          <select id="status" name="status" required>
            <?php foreach (STATUS_LIST as $s): ?>
              <option <?= $form['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <label for="pesan">Isi pesan <span class="hint">Maksimal 3000 karakter.</span></label>
      <textarea id="pesan" name="pesan" maxlength="3000" required><?= e($form['pesan']) ?></textarea>
    </fieldset>

    <button type="submit">Simpan</button>
    <p class="actions"><a href="dashboard.php">Batal, kembali ke daftar</a></p>
  </form>
</main>
<?php page_foot(); ?>
