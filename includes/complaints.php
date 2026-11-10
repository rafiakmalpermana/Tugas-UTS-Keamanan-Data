<?php
/** Daftar kategori dan status yang dipakai formulir publik dan panel admin. */
const KATEGORI_LIST = ['Akademik', 'Fasilitas', 'Administrasi', 'Kemahasiswaan', 'Lainnya'];
const STATUS_LIST   = ['Baru', 'Diproses', 'Selesai'];

/**
 * Validasi isian pengaduan. Mengembalikan [data bersih, pesan error].
 * Pesan error kosong berarti lolos.
 */
function validate_complaint(array $in): array {
    $d = [];
    foreach (['nama', 'nim', 'kategori', 'pesan'] as $k) { $d[$k] = trim((string)($in[$k] ?? '')); }

    if ($d['nama'] === '' || $d['nim'] === '' || $d['pesan'] === '') {
        return [$d, 'Nama, NIM, dan isi pesan wajib diisi.'];
    }
    if (mb_strlen($d['nama']) > 100 || mb_strlen($d['pesan']) > 3000) {
        return [$d, 'Nama maksimal 100 karakter dan isi pesan maksimal 3000 karakter.'];
    }
    if (!preg_match('/^[0-9A-Za-z.\-\/]{3,30}$/', $d['nim'])) {
        return [$d, 'NIM hanya boleh berisi huruf, angka, titik, strip, atau garis miring (3 sampai 30 karakter).'];
    }
    if (!in_array($d['kategori'], KATEGORI_LIST, true)) {
        return [$d, 'Pilih salah satu kategori.'];
    }
    return [$d, ''];
}

/** Simpan pengaduan baru (terenkripsi). Mengembalikan id baris. */
function complaint_insert(array $data, string $publicKeyPem): int {
    $pkg = encrypt_payload($data, $publicKeyPem);
    $st = db()->prepare('INSERT INTO complaints (ciphertext, iv, tag, wrapped_key) VALUES (:c, :i, :t, :k)');
    complaint_bind($st, $pkg);
    $st->execute();
    return (int)db()->lastInsertId();
}

/** Enkripsi ulang dan timpa isi satu pengaduan. Mengembalikan true bila ada baris yang cocok. */
function complaint_update(int $id, array $data, string $publicKeyPem): bool {
    $pkg = encrypt_payload($data, $publicKeyPem);
    $st = db()->prepare('UPDATE complaints SET ciphertext = :c, iv = :i, tag = :t, wrapped_key = :k WHERE id = :id');
    complaint_bind($st, $pkg);
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();
    return $st->rowCount() > 0;
}

function complaint_delete(int $id): bool {
    $st = db()->prepare('DELETE FROM complaints WHERE id = :id');
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();
    return $st->rowCount() > 0;
}

/** Ambil satu baris mentah, atau null bila tidak ada. */
function complaint_find(int $id): ?array {
    $st = db()->prepare('SELECT id, ciphertext, iv, tag, wrapped_key, created_at FROM complaints WHERE id = :id');
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();
    $row = $st->fetch();
    return $row === false ? null : $row;
}

function complaint_bind(PDOStatement $st, array $pkg): void {
    $st->bindValue(':c', $pkg['ciphertext'],  PDO::PARAM_LOB);
    $st->bindValue(':i', $pkg['iv'],          PDO::PARAM_LOB);
    $st->bindValue(':t', $pkg['tag'],         PDO::PARAM_LOB);
    $st->bindValue(':k', $pkg['wrapped_key'], PDO::PARAM_LOB);
}
