<?php
/** Pembuka halaman: lang, title, viewport yang bisa di-zoom, landmark header. */
function page_head(string $title, string $assets, string $headerRight = '', string $bodyClass = ''): void {
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . e($title) . '</title>'
       . '<link rel="stylesheet" href="' . e($assets) . '/style.css">'
       . '<script src="' . e($assets) . '/app.js"></script></head><body class="' . e($bodyClass) . '">'
       . '<a class="skip" href="#isi">Lewati ke konten utama</a>'
       . '<header class="top"><p class="brand">Pengaduan &amp; Aspirasi Mahasiswa</p>'
       . '<div class="top-actions">' . $headerRight
       . '<button type="button" id="theme-toggle" class="btn-quiet" aria-pressed="false" hidden>Mode gelap</button>'
       . '</div></header>';
}

function page_foot(string $extra = ''): void {
    echo '<footer class="foot">' . $extra . '</footer></body></html>';
}
