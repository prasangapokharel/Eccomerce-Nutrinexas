<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Reset Password' ?> - NutriNexus</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/tailwind.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-primary-50 to-accent/10 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-primary/10 mb-4">
                    <i class="fas fa-key text-primary text-3xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900">Reset Password</h2>
                <p class="mt-2 text-sm text-gray-600">Enter your registered email to get a reset link.</p>
            </div>

            <?php 
            $flash = \App\Core\Session::getFlash();
            if ($flash && is_array($flash) && isset($flash['type']) && isset($flash['message'])): 
                $type = $flash['type'];
                $message = $flash['message'];
                $colors = [
                    'success' => 'bg-success-50 border-success/30 text-success-dark',
                    'error' => 'bg-error-50 border-error/30 text-error-dark',
                    'warning' => 'bg-warning-50 border-warning/30 text-warning-dark',
                    'info' => 'bg-primary-50 border-primary/30 text-primary-700'
                ];
                $color = $colors[$type] ?? $colors['info'];
            ?>
                <div class="mb-6 p-4 rounded-lg border-2 <?= $color ?>">
                    <p class="text-sm font-medium"><?= htmlspecialchars($message) ?></p>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="<?= \App\Core\View::url('curior/forgot-password') ?>" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-gray-400"></i>Email Address
                    </label>
                    <input id="email" name="email" type="email" autocomplete="email" required 
                           value="<?= htmlspecialchars($email ?? '') ?>"
                           class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                           placeholder="example@domain.com">
                    <p class="mt-2 text-xs text-gray-500">We will send a secure link to this email.</p>
                </div>

                <button type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>Send Reset Link
                </button>
            </form>

            <div class="mt-6 text-center space-y-2">
                <a href="<?= \App\Core\View::url('curior/login') ?>" class="text-sm text-primary hover:text-primary/80 flex items-center justify-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Login
                </a>
                <p class="text-xs text-gray-500">Need help? Contact support for further assistance.</p>
            </div>
        </div>
    </div>
</body>
</html>

