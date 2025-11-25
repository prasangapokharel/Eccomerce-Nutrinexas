<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Curior Dashboard' ?> - NutriNexus</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/tailwind.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Shared Styles -->
    <link rel="stylesheet" href="<?= \App\Core\View::asset('layouts/shared-styles.css') ?>">
</head>
<body class="bg-gray-50">
    <?= $content ?? '' ?>
    
    <!-- Scripts -->
    <script>
        // Auto-hide flash messages
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            flashMessages.forEach(message => {
                setTimeout(() => {
                    message.style.transition = 'opacity 0.5s ease-out';
                    message.style.opacity = '0';
                    setTimeout(() => {
                        message.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>