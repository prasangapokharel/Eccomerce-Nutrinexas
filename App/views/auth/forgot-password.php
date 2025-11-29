<?php ob_start(); ?>

<!-- Native App Style Forgot Password Page -->
<link rel="preload" href="<?= ASSETS_URL ?>/images/screen/loginbg.png" as="image">
<div class="min-h-screen relative flex items-center justify-center px-4 py-2 login-page fixed" 
     style="background-image: url('<?= ASSETS_URL ?>/images/screen/loginbg.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    
    <!-- Overlay for better text readability -->
    <div class="absolute inset-0 bg-white bg-opacity-20"></div>
    
    <!-- Forgot Password Form Container -->
    <div class="relative z-5 w-full max-w-sm mx-auto mb-40 fixed overflow-hidden">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-2">Forgot Password</h1>
            <p class="text-[#626262] text-base">Reset your account password</p>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            <?php foreach ($errors as $error): ?>
                                <?= htmlspecialchars($error) ?><br>
                            <?php endforeach; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (\App\Core\Session::hasFlash()): ?>
            <?php $flash = \App\Core\Session::getFlash(); ?>
            <div class="mb-6 <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?> border border-green-200 rounded-lg p-4">
                <p class="text-sm"><?= htmlspecialchars($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Forgot Password Form -->
        <div class="login-form bg-white rounded-2xl shadow-xl p-8">
            <form action="<?= \App\Core\View::url('auth/forgotPassword') ?>" method="post" class="space-y-6">
                <!-- Username or Email Field -->
                <div>
                    <input type="text" name="identifier" id="identifier" value="<?= isset($identifier) ? htmlspecialchars($identifier) : '' ?>" 
                           class="native-input w-full px-4 py-4 bg-primary/5 border border-gray-200 rounded-xl text-primary placeholder-gray-500 focus:outline-none focus:border-primary transition-colors" 
                           placeholder="Username or Email" required>
                </div>

                <!-- Send Reset Link Button -->
                <button type="submit" class="w-full px-6 py-3 bg-primary text-white rounded-2xl font-semibold text-sm hover:bg-primary-dark transition-colors shadow-lg">
                    Send Reset Link
                </button>

                <!-- Back to Login Link -->
                <div class="text-center">
                    <p class="text-[#626262] text-sm">
                        Remember your password? 
                        <a href="<?= \App\Core\View::url('auth/login') ?>" class="text-primary font-semibold">
                            Back to Login
                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="mx-8 mt-6 bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <?php foreach ($errors as $error): ?>
                                    <?= htmlspecialchars($error) ?><br>
                                <?php endforeach; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (\App\Core\Session::hasFlash()): ?>
                <?php $flash = \App\Core\Session::getFlash(); ?>
                <div class="mx-8 mt-6 <?= $flash['type'] === 'success' ? 'bg-green-50  text-green-700' : 'bg-red-50 border-red-400 text-red-700' ?> border-l-4 p-4">
                    <p class="text-sm"><?= htmlspecialchars($flash['message']) ?></p>
                </div>
            <?php endif; ?>

            <div class="px-8 py-8">
                <div class="text-center mb-6">
                    <p class="text-gray-600">Enter your username or email address and we'll send you a link to reset your password.</p>
                </div>

                <form action="<?= \App\Core\View::url('auth/forgotPassword') ?>" method="post" class="space-y-6">
                    <div>
                        <label for="identifier" class="block text-sm font-semibold text-primary mb-2">Username or Email</label>
                        <input type="text" name="identifier" id="identifier" value="<?= isset($identifier) ? htmlspecialchars($identifier) : '' ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-200 focus:border-accent focus:outline-none text-gray-900 placeholder-gray-500" 
                               placeholder="Enter username or email" required>
                    </div>

                    <button type="submit" class="w-full px-6 py-3 bg-primary text-white rounded-2xl font-semibold text-sm hover:bg-primary-dark transition-colors shadow-lg">
                        Send Reset Link
                    </button>

                    <div class="text-center pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            Remember your password? 
                            <a href="<?= \App\Core\View::url('auth/login') ?>" class="text-accent font-semibold">
                                Back to Login
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>


    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>


    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="mx-8 mt-6 bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <?php foreach ($errors as $error): ?>
                                    <?= htmlspecialchars($error) ?><br>
                                <?php endforeach; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (\App\Core\Session::hasFlash()): ?>
                <?php $flash = \App\Core\Session::getFlash(); ?>
                <div class="mx-8 mt-6 <?= $flash['type'] === 'success' ? 'bg-green-50  text-green-700' : 'bg-red-50 border-red-400 text-red-700' ?> border-l-4 p-4">
                    <p class="text-sm"><?= htmlspecialchars($flash['message']) ?></p>
                </div>
            <?php endif; ?>

            <div class="px-8 py-8">
                <div class="text-center mb-6">
                    <p class="text-gray-600">Enter your username or email address and we'll send you a link to reset your password.</p>
                </div>

                <form action="<?= \App\Core\View::url('auth/forgotPassword') ?>" method="post" class="space-y-6">
                    <div>
                        <label for="identifier" class="block text-sm font-semibold text-primary mb-2">Username or Email</label>
                        <input type="text" name="identifier" id="identifier" value="<?= isset($identifier) ? htmlspecialchars($identifier) : '' ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-200 focus:border-accent focus:outline-none text-gray-900 placeholder-gray-500" 
                               placeholder="Enter username or email" required>
                    </div>

                    <button type="submit" class="w-full px-6 py-3 bg-primary text-white rounded-2xl font-semibold text-sm hover:bg-primary-dark transition-colors shadow-lg">
                        Send Reset Link
                    </button>

                    <div class="text-center pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            Remember your password? 
                            <a href="<?= \App\Core\View::url('auth/login') ?>" class="text-accent font-semibold">
                                Back to Login
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>


    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>


    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="mx-8 mt-6 bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <?php foreach ($errors as $error): ?>
                                    <?= htmlspecialchars($error) ?><br>
                                <?php endforeach; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (\App\Core\Session::hasFlash()): ?>
                <?php $flash = \App\Core\Session::getFlash(); ?>
                <div class="mx-8 mt-6 <?= $flash['type'] === 'success' ? 'bg-green-50  text-green-700' : 'bg-red-50 border-red-400 text-red-700' ?> border-l-4 p-4">
                    <p class="text-sm"><?= htmlspecialchars($flash['message']) ?></p>
                </div>
            <?php endif; ?>

            <div class="px-8 py-8">
                <div class="text-center mb-6">
                    <p class="text-gray-600">Enter your username or email address and we'll send you a link to reset your password.</p>
                </div>

                <form action="<?= \App\Core\View::url('auth/forgotPassword') ?>" method="post" class="space-y-6">
                    <div>
                        <label for="identifier" class="block text-sm font-semibold text-primary mb-2">Username or Email</label>
                        <input type="text" name="identifier" id="identifier" value="<?= isset($identifier) ? htmlspecialchars($identifier) : '' ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-200 focus:border-accent focus:outline-none text-gray-900 placeholder-gray-500" 
                               placeholder="Enter username or email" required>
                    </div>

                    <button type="submit" class="w-full px-6 py-3 bg-primary text-white rounded-2xl font-semibold text-sm hover:bg-primary-dark transition-colors shadow-lg">
                        Send Reset Link
                    </button>

                    <div class="text-center pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            Remember your password? 
                            <a href="<?= \App\Core\View::url('auth/login') ?>" class="text-accent font-semibold">
                                Back to Login
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>


    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>


    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="mx-8 mt-6 bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <?php foreach ($errors as $error): ?>
                                    <?= htmlspecialchars($error) ?><br>
                                <?php endforeach; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (\App\Core\Session::hasFlash()): ?>
                <?php $flash = \App\Core\Session::getFlash(); ?>
                <div class="mx-8 mt-6 <?= $flash['type'] === 'success' ? 'bg-green-50  text-green-700' : 'bg-red-50 border-red-400 text-red-700' ?> border-l-4 p-4">
                    <p class="text-sm"><?= htmlspecialchars($flash['message']) ?></p>
                </div>
            <?php endif; ?>

            <div class="px-8 py-8">
                <div class="text-center mb-6">
                    <p class="text-gray-600">Enter your username or email address and we'll send you a link to reset your password.</p>
                </div>

                <form action="<?= \App\Core\View::url('auth/forgotPassword') ?>" method="post" class="space-y-6">
                    <div>
                        <label for="identifier" class="block text-sm font-semibold text-primary mb-2">Username or Email</label>
                        <input type="text" name="identifier" id="identifier" value="<?= isset($identifier) ? htmlspecialchars($identifier) : '' ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-200 focus:border-accent focus:outline-none text-gray-900 placeholder-gray-500" 
                               placeholder="Enter username or email" required>
                    </div>

                    <button type="submit" class="w-full px-6 py-3 bg-primary text-white rounded-2xl font-semibold text-sm hover:bg-primary-dark transition-colors shadow-lg">
                        Send Reset Link
                    </button>

                    <div class="text-center pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            Remember your password? 
                            <a href="<?= \App\Core\View::url('auth/login') ?>" class="text-accent font-semibold">
                                Back to Login
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>


    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
