# Sistem Pengaduan & Aspirasi Mahasiswa Terenkripsi

Aplikasi web PHP untuk menerima pengaduan mahasiswa dengan data pribadi yang terenkripsi. Nama, NIM, kategori, dan isi pesan dienkripsi di server memakai **AES-256-GCM**, lalu kunci AES-nya dibungkus dengan **RSA-OAEP** (kunci publik). Server hanya menyimpan kunci publik, jadi isi database tidak bisa dibaca tanpa **private key admin**. Admin masuk dengan file `.pem`, bukan password.

| | |
|---|---|
| **Nama** | Rafi Akmal Permana |
| **NIM** | 2430511065 |
| **Kelas** | 5B |

## Fitur

**Publik**
- Formulir pengaduan (nama, NIM, kategori, isi pesan) dengan validasi dan perlindungan CSRF.
- Isian tidak hilang saat validasi gagal.
- Mode terang/gelap dan navigasi keyboard.

**Admin (CRUD)**

| Operasi | Halaman | Keterangan |
|---|---|---|
| Create | `index.php` | Mahasiswa mengirim pengaduan lewat formulir publik, data langsung dienkripsi |
| Read | `admin/dashboard.php` | Daftar pengaduan terdekripsi, cari nama/NIM/pesan, saring per status |
| Update | `admin/edit.php?id=N` | Admin mengubah status (Baru, Diproses, Selesai). Data mahasiswa tidak bisa diubah |
| Delete | `admin/delete.php?id=N` | Halaman konfirmasi, lalu hapus permanen lewat POST |

Semua aksi tulis memakai metode POST dengan token CSRF, query berparameter (PDO), dan keluaran di-escape. Sesi admin habis 20 menit setelah masuk.

## Struktur proyek

```
config/        koneksi database (config.php, db.php)
database/      skema MySQL (database.sql)
includes/      auth.php, crypto.php, complaints.php, layout.php
keys/          public_key.pem dan generate_keys.php (diblokir lewat .htaccess)
public/        document root: index.php, assets/, admin/
```

`status` dan `diubah` disimpan di dalam payload terenkripsi, jadi skema database tidak berubah. Yang tidak terenkripsi hanya nomor urut dan waktu masuk.

## Instalasi (Laragon + HeidiSQL)

### 1. Siapkan lingkungan
1. Pasang Laragon (sudah termasuk PHP 8.1+, MySQL/MariaDB, Apache, dan HeidiSQL).
2. Pastikan ekstensi `openssl`, `sodium`, dan `pdo_mysql` aktif. Cek di Laragon: **Menu > PHP > Extensions**, atau jalankan `php -m` di Terminal Laragon.

### 2. Letakkan proyek
Salin folder proyek ke `C:\laragon\www\`, lalu klik **Start All** di Laragon. Aplikasi dibuka lewat folder `public`, misalnya:

- Formulir: `http://localhost/web-keamanan-data/public/`
- Login admin: `http://localhost/web-keamanan-data/public/admin/login.php`

Sesuaikan port bila Apache Anda tidak memakai port 80 (misalnya `localhost:81`).

### 3. Buat database lewat HeidiSQL
1. Di Laragon klik tombol **Database** untuk membuka HeidiSQL. Masuk dengan user `root`, password kosong, host `127.0.0.1`, port `3306`.
2. Pilih **File > Load SQL file**, lalu buka `database/database.sql`.
3. Klik **Execute** (ikon play biru) atau tekan **F9**.
4. Klik kanan daftar database di kiri dan pilih **Refresh**. Database `pengaduan_db` dengan tabel `complaints` harus muncul.

Alternatif lewat terminal: `mysql -u root -p < database/database.sql`

### 4. Atur koneksi
Edit `config/config.php`. Bawaan Laragon (`db_user` = `root`, `db_pass` kosong) sudah cocok untuk uji lokal. Jangan pakai akun root di produksi. Buat user khusus dengan hak `SELECT`, `INSERT`, `UPDATE`, dan `DELETE` pada tabel `complaints`:

```sql
CREATE USER 'pengaduan'@'localhost' IDENTIFIED BY 'ganti_password_ini';
GRANT SELECT, INSERT, UPDATE, DELETE ON pengaduan_db.complaints TO 'pengaduan'@'localhost';
```

Perintah di atas bisa dijalankan di tab **Query** HeidiSQL.

### 5. Buat pasangan kunci
Di Terminal Laragon:

```
cd C:\laragon\www\web-keamanan-data
php keys/generate_keys.php
```

- `keys/public_key.pem` tetap di server.
- `keys/admin_private_key.pem` pindahkan ke perangkat admin, simpan cadangan di tempat terpisah, lalu **hapus dari server**.

Private key hilang berarti data tidak bisa dipulihkan. Jangan jalankan `generate_keys.php` lagi setelah ada data, karena pasangan kunci berganti dan data lama tidak bisa dibuka.

## Pengujian

### Kirim pengaduan
1. Buka halaman formulir, isi semua kolom, klik "Kirim pengaduan". Pesan hijau "Terkirim" harus muncul.
2. Kirim dengan kolom kosong. Pesan merah muncul dan isian tidak hilang.

### Data terenkripsi di database
Di tab **Query** HeidiSQL:

```sql
SELECT id, HEX(ciphertext), HEX(wrapped_key), created_at FROM complaints;
```

Isinya harus deretan heksadesimal, tanpa nama, NIM, atau pesan yang terbaca.

### Login admin
1. Buka `/admin/login.php`, unggah `admin_private_key.pem` atau tempel isinya.
2. Dashboard menampilkan pengaduan dalam bentuk terbaca.
3. Uji negatif: kunci RSA lain atau teks acak harus ditolak.

### CRUD admin
1. **Create:** kirim pengaduan dari halaman formulir publik, lalu login admin. Entri muncul di atas dengan status "Baru".
2. **Read:** coba kolom cari (nama, NIM, atau potongan pesan) dan filter status.
3. **Update:** klik "Ubah status", pilih status baru, simpan. Di HeidiSQL, `HEX(ciphertext)` baris itu berubah. Nama, NIM, dan pesan mahasiswa tetap sama.
4. **Delete:** klik "Hapus", halaman konfirmasi muncul. "Ya, hapus" menghapus baris, "Batal" tidak mengubah apa pun.
5. Buka `/admin/edit.php?id=1` atau `/admin/delete.php?id=1` tanpa login. Harus dialihkan ke halaman login.

Catatan: nomor pengaduan memakai `AUTO_INCREMENT`, jadi tidak dipakai ulang setelah baris dihapus. Untuk mengosongkan data uji dan mulai dari 1 lagi, jalankan `TRUNCATE TABLE complaints;` di HeidiSQL.

### Keamanan dasar
1. Di kolom nama, ketik `' OR 1=1 --` lalu kirim. Harus tersimpan dan tampil sebagai teks biasa.
2. Ketik `<script>alert(1)</script>` di pesan. Di dashboard harus tampil sebagai teks, tanpa popup.
3. Ubah satu byte di kolom `ciphertext` lewat HeidiSQL. Dashboard menampilkan "Tidak bisa dibuka", tombol "Ubah status" disembunyikan, tombol "Hapus" tetap ada.

### Tampilan dan keyboard
1. Tekan Tab dari awal halaman: tautan lewati, tombol mode, kolom form, dan tombol kirim terurut, fokus terlihat.
2. Klik "Mode gelap" lalu muat ulang. Pilihan tetap dan kedua mode terbaca.
3. Kecilkan jendela hingga sekitar 375 px. Tidak ada scroll horizontal.

## Pengerasan untuk produksi
1. Pasang HTTPS (cookie sesi otomatis ber-flag `secure`).
2. Pastikan `keys/`, `config/`, `includes/` berada di luar document root.
3. Tambahkan pembatasan percobaan login dan pengiriman per IP (misalnya fail2ban atau rate limit web server).
4. Matikan `display_errors` di `php.ini`, aktifkan log error.
5. Jadwalkan cadangan database dan simpan cadangan private key terpisah.

## Penulis
Dibuat oleh **Rafi Akmal Permana** (NIM 2430511065, Kelas 5B).
