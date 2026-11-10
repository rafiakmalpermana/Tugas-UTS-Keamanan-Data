<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/crypto.php';
require __DIR__ . '/../includes/layout.php';
require __DIR__ . '/../includes/complaints.php';
require __DIR__ . '/../config/db.php';

$kategoriList = KATEGORI_LIST;
$old   = ['nama' => '', 'nim' => '', 'kategori' => '', 'pesan' => ''];
$error = '';
$sent  = (($_GET['status'] ?? '') === 'ok');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$old, $invalid] = validate_complaint($_POST);

    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Halaman ini sudah terlalu lama terbuka. Teks Anda masih ada, kirim ulang saja.';
    } elseif ($invalid !== '') {
        $error = $invalid;
    } else {
        try {
            $cfg = require __DIR__ . '/../config/config.php';
            complaint_insert($old + ['status' => 'Baru', 'waktu' => date('c')], file_get_contents($cfg['public_key_path']));

            header('Location: index.php?status=ok');
            exit;
        } catch (Throwable $ex) {
            error_log($ex->getMessage());
            $error = 'Pengaduan belum tersimpan karena ada gangguan di server. Teks Anda masih ada, coba kirim lagi.';
        }
    }
}
$token = csrf_token();

page_head('Pengaduan dan Aspirasi Mahasiswa', 'assets', '', 'wide');
?>
<main id="isi" class="split">
  <section class="panel" aria-labelledby="judul">
    <h1 id="judul">Punya keluhan atau usulan untuk kampus?</h1>
    <p class="panel-lede">Isi formulir ini. Nama, NIM, dan isi pesan disimpan terenkripsi, jadi hanya admin yang memegang kunci yang bisa membacanya.</p>

    <h2 class="panel-h">Sebelum mengirim</h2>
    <ul class="notes">
      <li>Nama dan NIM wajib diisi. Pengaduan tanpa identitas tidak bisa dikirim.</li>
      <li>Satu pengaduan untuk satu masalah. Kalau ada beberapa, kirim terpisah.</li>
      <li>Isi pesan maksimal 3000 karakter, sekitar 450 kata.</li>
      <li>Yang tidak dienkripsi cuma nomor urut dan waktu masuk.</li>
    </ul>
  </section>

  <div class="formcol">
    <?php if ($sent): ?>
      <div class="notice ok" role="status"><strong>Terkirim.</strong> Pengaduan Anda sudah masuk.</div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="notice err" role="alert"><strong>Belum terkirim.</strong> <?= e($error) ?></div>
    <?php endif; ?>

    <form class="card" method="post" action="index.php" data-busy="Mengirim...">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">

      <fieldset>
        <legend>Identitas</legend>
        <div class="row2">
          <div>
            <label for="nama">Nama lengkap</label>
            <input id="nama" name="nama" maxlength="100" autocomplete="name" required value="<?= e($old['nama']) ?>">
          </div>
          <div>
            <label for="nim">NIM</label>
            <input id="nim" name="nim" maxlength="30" required value="<?= e($old['nim']) ?>">
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Pengaduan</legend>
        <label for="kategori">Kategori</label>
        <select id="kategori" name="kategori" required>
          <option value="" disabled <?= $old['kategori'] === '' ? 'selected' : '' ?>>Pilih kategori</option>
          <?php foreach ($kategoriList as $k): ?>
            <option <?= $old['kategori'] === $k ? 'selected' : '' ?>><?= e($k) ?></option>
          <?php endforeach; ?>
        </select>

        <label for="pesan">Isi pesan <span class="hint">Ceritakan masalahnya dengan jelas. Maksimal 3000 karakter.</span></label>
        <textarea id="pesan" name="pesan" maxlength="3000" required><?= e($old['pesan']) ?></textarea>
      </fieldset>

      <button type="submit">Kirim pengaduan</button>
    </form>
  </div>
</main>
<?php page_foot('<a href="admin/login.php">Masuk sebagai admin</a>'); ?>
