<?php
const CIPHER = 'aes-256-gcm';

/** Enkripsi array data: AES-256-GCM untuk isi, RSA-OAEP untuk membungkus kunci AES. */
function encrypt_payload(array $data, string $publicKeyPem): array {
    $plaintext = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $dek = random_bytes(32);
    $iv  = random_bytes(12);
    $tag = '';

    $cipher = openssl_encrypt($plaintext, CIPHER, $dek, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($cipher === false) { throw new RuntimeException('Enkripsi gagal'); }

    $wrapped = '';
    if (!openssl_public_encrypt($dek, $wrapped, $publicKeyPem, OPENSSL_PKCS1_OAEP_PADDING)) {
        throw new RuntimeException('Pembungkusan kunci gagal');
    }
    sodium_memzero($dek);

    return ['ciphertext' => $cipher, 'iv' => $iv, 'tag' => $tag, 'wrapped_key' => $wrapped];
}

/** Dekripsi satu baris dengan private key admin. Null jika gagal. */
function decrypt_row(array $row, string $privateKeyPem): ?array {
    $pk = openssl_pkey_get_private($privateKeyPem);
    if ($pk === false) { return null; }

    $dek = '';
    if (!openssl_private_decrypt($row['wrapped_key'], $dek, $pk, OPENSSL_PKCS1_OAEP_PADDING)) {
        return null;
    }
    $plain = openssl_decrypt($row['ciphertext'], CIPHER, $dek, OPENSSL_RAW_DATA, $row['iv'], $row['tag']);
    sodium_memzero($dek);

    return $plain === false ? null : json_decode($plain, true);
}

/** Cek apakah private key pasangan dari public key server (challenge-response). */
function verify_private_key(string $privateKeyPem, string $publicKeyPem): bool {
    $pk = openssl_pkey_get_private($privateKeyPem);
    if ($pk === false) { return false; }

    $challenge = random_bytes(32);
    $enc = '';
    if (!openssl_public_encrypt($challenge, $enc, $publicKeyPem, OPENSSL_PKCS1_OAEP_PADDING)) {
        return false;
    }
    $dec = '';
    if (!openssl_private_decrypt($enc, $dec, $pk, OPENSSL_PKCS1_OAEP_PADDING)) {
        return false;
    }
    return hash_equals($challenge, $dec);
}
