<?php ob_start(); ?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-primary">
                <i class="fas fa-user-tie text-white text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Staff Login
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Sign in to your staff account
            </p>
        </div>
        
        <form class="mt-8 space-y-6" method="POST">
            <!-- Flash Messages -->
            <?php 
            $flashMessage = \App\Helpers\FlashHelper::getFlashMessage('success');
            if ($flashMessage): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?= $flashMessage ?>
                </div>
            <?php endif; ?>

            <?php 
            $flashError = \App\Helpers\FlashHelper::getFlashMessage('error');
            if ($flashError): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?= $flashError ?>
                </div>
            <?php endif; ?>

            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email Address
                    </label>
                    <div class="mt-1">
                        <input id="email" 
                               name="email" 
                               type="email" 
                               autocomplete="email" 
                               required 
                               class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                               placeholder="Enter your email">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <div class="mt-1">
                        <input id="password" 
                               name="password" 
                               type="password" 
                               autocomplete="current-password" 
                               required 
                               class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                               placeholder="Enter your password">
                    </div>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-lock text-primary-light"></i>
                    </span>
                    Sign In
                </button>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Having trouble? 
                    <a href="mailto:admin@nutrinexus.com" class="font-medium text-primary hover:text-primary-dark">
                        Contact Administrator
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<style>
/* Custom styles for staff login */
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

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/staff.php'; ?>
