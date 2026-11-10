<?php
// Jalankan sekali dari terminal: php keys/generate_keys.php
if (PHP_SAPI !== 'cli') { exit('Hanya untuk CLI'); }

$res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
if ($res === false) { fwrite(STDERR, "Gagal membuat kunci. Cek ekstensi openssl.\n"); exit(1); }

openssl_pkey_export($res, $privateKey);
$publicKey = openssl_pkey_get_details($res)['key'];

file_put_contents(__DIR__ . '/public_key.pem', $publicKey);
file_put_contents(__DIR__ . '/admin_private_key.pem', $privateKey);
chmod(__DIR__ . '/admin_private_key.pem', 0600);

echo "public_key.pem dibuat. Biarkan di server.\n";
echo "admin_private_key.pem dibuat. Pindahkan ke perangkat admin, lalu hapus dari server.\n";
