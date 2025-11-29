<footer class="font-header tracking-wide text-white px-10 pt-12 pb-6 mt-12">
        <div class="flex flex-wrap justify-between gap-10">
            <div class="max-w-md">
                <a href='<?= URLROOT ?>'>
                    <img src="<?= \App\Core\View::asset('images/logo/logo.png') ?>" alt="Nutri Nexas" class='w-16 h-16 sm:w-20 sm:h-20 rounded-full' loading="lazy" decoding="async" onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'" />
                </a>
                <div class="mt-6">
                    <p class="text-gray-300 leading-relaxed text-sm">Nutri Nexas is your trusted source for premium quality supplements. We offer a wide range of products to support your health and fitness journey, from protein powders to vitamins and everything in between.</p>
                </div>
                <ul class="mt-10 flex space-x-5">
                    <li>
                        <a href='https://www.facebook.com/people/Nutri-Nexas/61565815894156/?ref=pro_upsell_xav_ig_profile_page_web' class="text-accent hover:text-accent-dark">
                            <i class="fab fa-facebook-f text-xl"></i>
                        </a>
                    </li>
                    <li>
                        <a href='https://www.tiktok.com/@nutrinexas' class="text-accent hover:text-accent-dark">
                            <i class="fab fa-tiktok text-xl"></i>
                        </a>
                    </li>
                    <li>
                        <a href='https://www.instagram.com/nutrinexasnp/' class="text-accent hover:text-accent-dark">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="max-lg:min-w-[140px]">
                <h4 class="text-white font-semibold text-base relative max-sm:cursor-pointer">Products</h4>

                <ul class="mt-6 space-y-4">
                    <li>
                        <a href='<?= URLROOT ?>/products/category/Protein' class='hover:text-accent text-gray-300 text-sm'>Protein</a>
                    </li>
                    <li>
                        <a href='<?= URLROOT ?>/products/category/Creatine' class='hover:text-accent text-gray-300 text-sm'>Creatine</a>
                    </li>
                    <li>
                        <a href='<?= URLROOT ?>/products/category/Pre-Workout' class='hover:text-accent text-gray-300 text-sm'>Pre-Workout</a>
                    </li>
                    <li>
                        <a href='<?= URLROOT ?>/products/category/Vitamins' class='hover:text-accent text-gray-300 text-sm'>Vitamins</a>
                    </li>
                    <li>
                        <a href='<?= URLROOT ?>/products/category/Fat-Burners' class='hover:text-accent text-gray-300 text-sm'>Fat Burners</a>
                    </li>
                </ul>
            </div>

            <div class="max-lg:min-w-[140px]">
                <h4 class="text-white font-semibold text-base relative max-sm:cursor-pointer">Support</h4>
                <ul class="space-y-4 mt-6">
                    <li>
                        <a href='<?= URLROOT ?>/pages/faq' class='hover:text-accent text-gray-300 text-sm'>FAQ</a>
                    </li>
                    <li>
                        <a href='<?= URLROOT ?>/pages/shipping' class='hover:text-accent text-gray-300 text-sm'>Shipping</a>
                    </li>
                    <li>
                        <a href='<?= URLROOT ?>/pages/returns' class='hover:text-accent text-gray-300 text-sm'>Returns</a>
                    </li>
                    <li>
                        <a href='<?= URLROOT ?>/pages/contact' class='hover:text-accent text-gray-300 text-sm'>Contact Us</a>
                    </li>
                </ul>
            </div>

            <div class="max-lg:min-w-[140px]">
                <h4 class="text-white font-semibold text-base relative max-sm:cursor-pointer">Newsletter</h4>

                <form class="mt-6" action="<?= URLROOT ?>/newsletter/subscribe" method="post">
                    <div class="relative max-w-xs">
                        <input type="email" name="email" placeholder="Enter your email" class="w-full px-4 py-2 text-gray-700 bg-white border rounded-2xl focus:border-accent" required />
                        <button type="submit" class="absolute inset-y-0 right-0 px-3 text-sm font-medium text-white bg-accent rounded-r-2xl hover:bg-accent-dark">
                            Subscribe
                        </button>
                    </div>
                </form>

                <div class="mt-6 max-w-xs">
                    <a href="<?= URLROOT ?>/seller/login" class="w-full inline-flex items-center gap-3 px-4 py-3 bg-white text-primary rounded-2xl font-semibold text-sm border border-neutral-200 shadow-sm hover:bg-neutral-100">
                        <img src="<?= \App\Core\View::asset('images/graphics/seller.png') ?>" alt="Seller portal" class="w-10 h-10 rounded-full border border-neutral-200" loading="lazy" decoding="async">
                        <div class="text-left">
                            <p class="leading-tight">Become a Seller</p>
                            <p class="text-xs font-normal text-neutral-500">0 selling commission</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <hr class="mt-10 mb-6 border-gray-700" />

        <!-- Footer Banner Ad -->
        <?php include dirname(__DIR__) . '/components/FooterBanner.php'; ?>

        <div class="flex flex-wrap max-md:flex-col gap-4">
            <ul class="md:flex md:space-x-6 max-md:space-y-2">
                <li>
                    <a href='<?= URLROOT ?>/pages/terms' class='hover:text-accent text-gray-300 text-sm'>Terms of Service</a>
                </li>
                <li>
                    <a href='<?= URLROOT ?>/pages/privacy' class='hover:text-accent text-gray-300 text-sm'>Privacy Policy</a>
                </li>
                <li>
                    <a href='<?= URLROOT ?>/pages/cookies' class='hover:text-accent text-gray-300 text-sm'>Cookie Policy</a>
                </li>
            </ul>

            <p class='text-gray-300 text-sm md:ml-auto'>© <?= date('Y') ?> Nutri Nexas. All rights reserved.</p>
        </div>
    </footer>

    <!-- Removed non-existent main.js to prevent 404; use optimized bundle if needed -->
    <script src="<?= URLROOT ?>/js/optimized.js"></script>
