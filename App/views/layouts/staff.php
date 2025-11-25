<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Staff Dashboard' ?> - NutriNexus</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/tailwind.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= \App\Core\View::asset('css/theme.css') ?>">
    
    <style>
        .bg-primary {
            background-color: #0A3167;
        }
        .bg-primary-dark {
            background-color: #082A5A;
        }
        .text-primary {
            color: #0A3167;
        }
        .text-primary-dark {
            color: #082A5A;
        }
        .border-primary {
            border-color: #0A3167;
        }
        .focus\:ring-primary:focus {
            --tw-ring-color: #0A3167;
        }
        .focus\:border-primary:focus {
            border-color: #0A3167;
        }
        .text-primary-light {
            color: #3B82F6;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?= $content ?>
</body>
</html>