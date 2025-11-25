<footer class="font-header tracking-wide text-white px-10 pt-12 pb-6 mt-12">
        <div class="flex flex-wrap justify-between gap-10">
            <div class="max-w-md">
                <a href='<?= URLROOT ?>'>
<img src="https://qkjsnpejxzujoaktpgpq.supabase.co/storage/v1/object/public/nutrinexas/logo.svg" alt="Nutri Nexas" class='w-16 h-16 sm:w-20 sm:h-20 rounded-full' />
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
                <h4 class="text-accent font-semibold text-base relative max-sm:cursor-pointer">Products</h4>

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
                <h4 class="text-accent font-semibold text-base relative max-sm:cursor-pointer">Support</h4>
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
                <h4 class="text-accent font-semibold text-base relative max-sm:cursor-pointer">Newsletter</h4>

                <form class="mt-6" action="<?= URLROOT ?>/newsletter/subscribe" method="post">
                    <div class="relative max-w-xs">
                        <input type="email" name="email" placeholder="Enter your email" class="w-full px-4 py-2 text-gray-700 bg-white border rounded-none focus:border-accent" required />
                        <button type="submit" class="absolute inset-y-0 right-0 px-3 text-sm font-medium text-white bg-accent rounded-r-md hover:bg-accent-dark">
                            Subscribe
                        </button>
                    </div>
                </form>
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
