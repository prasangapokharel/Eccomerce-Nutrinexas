<?php ob_start(); ?>

<!-- Native App Style Reset Password Page -->
<div class="min-h-screen relative flex items-center justify-center px-4 py-2 login-page fixed" 
     style="background-image: url('<?= ASSETS_URL ?>/images/screen/loginbg.png');">
    
    <!-- Overlay for better text readability -->
    <div class="absolute inset-0 bg-white bg-opacity-20"></div>
    
    <!-- Reset Password Form Container -->
    <div class="relative z-5 w-full max-w-sm mx-auto mb-40 fixed overflow-hidden">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-2">Reset Password</h1>
            <p class="text-[#626262] text-base">Create a new password for your account</p>
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

        <!-- Reset Password Form -->
        <div class="login-form bg-white rounded-2xl shadow-xl p-8">
            <form action="<?= \App\Core\View::url('auth/resetPassword/' . $token) ?>" method="post" class="space-y-6">
                <!-- New Password Field -->
                <div>
                    <input type="password" name="password" id="password" 
                           class="native-input w-full px-4 py-4 bg-primary/5 border border-gray-200 rounded-xl text-primary placeholder-gray-500 focus:outline-none focus:border-primary transition-colors" 
                           placeholder="New Password" required>
                </div>

                <!-- Confirm Password Field -->
                <div>
                    <input type="password" name="confirm_password" id="confirm_password" 
                           class="native-input w-full px-4 py-4 bg-primary/5 border border-gray-200 rounded-xl text-primary placeholder-gray-500 focus:outline-none focus:border-primary transition-colors" 
                           placeholder="Confirm Password" required>
                </div>

                <!-- Reset Password Button -->
                <button type="submit" class="w-full px-6 py-3 bg-primary text-white rounded-2xl font-semibold text-sm hover:bg-primary-dark transition-colors shadow-lg">
                    Reset Password
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

                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

                        </a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
