    <!-- PWA -->
    <link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
    <meta name="theme-color" content="#12131A">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MR.PEPSI">
    <link rel="apple-touch-icon" href="<?= url('icons/apple-touch-icon.png') ?>">
    <script>
    // PWA Single-Window Navigation:
    // Mencegah aplikasi PWA membuka jendela / tab browser baru berulang-ulang saat klik link internal
    (function() {
        document.addEventListener('click', function(e) {
            var a = e.target.closest('a');
            if (!a || !a.href) return;
            // Biarkan shortcut pengguna seperti Ctrl+Klik atau Cmd+Klik
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;

            try {
                var targetUrl = new URL(a.href, window.location.origin);
                // Jika domain sama (link internal aplikasi kita)
                if (targetUrl.origin === window.location.origin) {
                    // Kecuali link download unduh file biner PDF langsung (*.pdf atau download attribute)
                    if (a.hasAttribute('download')) return;
                    
                    // Hilangkan target="_blank" sehingga tetap berjalan di 1 jendela PWA yang sama
                    if (a.getAttribute('target') === '_blank') {
                        a.removeAttribute('target');
                    }
                }
            } catch (err) {}
        }, true);
    })();
    </script>

