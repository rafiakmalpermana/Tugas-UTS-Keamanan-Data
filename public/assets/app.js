(function () {
  var root = document.documentElement, KEY = 'pengaduan-tema';
  function read() { try { return localStorage.getItem(KEY); } catch (e) { return null; } }
  function write(v) { try { localStorage.setItem(KEY, v); } catch (e) {} }

  var theme = read();
  if (theme !== 'dark' && theme !== 'light') {
    theme = (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
  }
  root.setAttribute('data-theme', theme);

  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('theme-toggle');
    if (btn) {
      btn.hidden = false; // tombol hanya muncul jika JS aktif, sehingga tidak pernah mati
      btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
      btn.addEventListener('click', function () {
        theme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', theme);
        btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        write(theme);
      });
    }

    var form = document.querySelector('form[data-busy]');
    if (form) {
      var submit = form.querySelector('button[type=submit]');
      var label = submit ? submit.textContent : '';
      form.addEventListener('submit', function () {
        if (submit) { submit.disabled = true; submit.textContent = form.getAttribute('data-busy'); }
      });
      window.addEventListener('pageshow', function () {
        if (submit) { submit.disabled = false; submit.textContent = label; }
      });
    }
  });
})();
