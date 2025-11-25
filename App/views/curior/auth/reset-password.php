<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Create New Password' ?> - NutriNexus</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/tailwind.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-primary-50 to-accent/10 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-primary/10 mb-4">
                    <i class="fas fa-lock text-primary text-3xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900">Create a New Password</h2>
                <p class="mt-2 text-sm text-gray-600">Choose a strong password to secure your account.</p>
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

            <form class="space-y-6" action="<?= \App\Core\View::url('curior/reset-password/' . urlencode($token)) ?>" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-key mr-2 text-gray-400"></i>New Password
                    </label>
                    <input id="password" name="password" type="password" required 
                           class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                           placeholder="Enter new password">
                    <?php if (!empty($errors['password'])): ?>
                        <p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($errors['password']) ?></p>
                    <?php else: ?>
                        <p class="mt-2 text-xs text-gray-500">Minimum 6 characters.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-check mr-2 text-gray-400"></i>Confirm Password
                    </label>
                    <input id="confirm_password" name="confirm_password" type="password" required 
                           class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                           placeholder="Re-enter password">
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($errors['confirm_password']) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <i class="fas fa-save mr-2"></i>Update Password
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="<?= \App\Core\View::url('curior/login') ?>" class="text-sm text-primary hover:text-primary/80 flex items-center justify-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>

