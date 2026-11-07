<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/../partials/pwa_head.php'; ?>
    <title>Login - MR.PEPSI UPGRADE</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        pepsi: {
                            navy: '#12131A',
                            blue: '#2B3A9E',
                            red: '#E3262E'
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        heading: ['"Bricolage Grotesque"', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 text-slate-900 font-sans min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <?= $content ?>
    </div>
    <?php require __DIR__ . '/../partials/pwa_script.php'; ?>
</body>
</html>
