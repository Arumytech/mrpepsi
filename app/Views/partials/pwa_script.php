    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('<?= url('sw.js') ?>').catch(function () {});
            });
        }
    </script>
