<?php ob_start(); ?>

<div class="min-h-screen bg-neutral-50 py-10">
    <div class="container mx-auto px-4 max-w-6xl space-y-10">
        <div class="text-center space-y-4">
            <p class="text-sm uppercase tracking-wide text-primary/80 font-semibold">Premium Ad Inventory</p>
            <h1 class="text-4xl font-bold text-primary">Launch High-Intent Banner Ads on NutriNexus</h1>
            <p class="text-neutral-600 max-w-3xl mx-auto">
                Pick the slot that matches your campaign goals. Each tier includes creative support, live reporting,
                and priority placement across desktop + mobile experiences.
            </p>
            <div class="flex justify-center">
                <a href="https://wa.me/9811388848?text=I%20want%20to%20book%20an%20ad%20slot%20on%20NutriNexus"
                   target="_blank"
                   rel="noopener"
                   class="btn btn-primary px-6 py-3">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12.04 2C6.58 2 2.16 6.42 2.16 11.88c0 2.09.61 4.04 1.78 5.71L2 22l4.52-1.36a10 10 0 0 0 5.52 1.6h.01c5.46 0 9.88-4.42 9.88-9.88S17.5 2 12.04 2zm5.83 14.22c-.24.68-1.37 1.29-1.9 1.37-.5.08-1.14.11-1.84-.11-.42-.13-.96-.31-1.65-.61-2.92-1.26-4.83-4.15-4.98-4.34-.15-.2-1.19-1.58-1.19-3.02 0-1.45.76-2.16 1.04-2.45.28-.29.62-.36.82-.36.2 0 .41 0 .59.01.19.01.45-.07.71.54.24.59.83 2.03.9 2.18.07.15.12.34.02.54-.09.2-.14.34-.28.52-.15.17-.3.39-.43.52-.14.13-.28.28-.12.56.15.28.67 1.11 1.44 1.8 1 0.89 1.84 1.17 2.12 1.31.28.13.45.11.62-.07.17-.19.71-.82.9-1.1.19-.28.38-.23.64-.14.26.09 1.63.77 1.91.92.28.13.47.2.54.31.08.11.08.64-.16 1.32z"/>
                    </svg>
                    Book Slot on WhatsApp
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php
            $tiers = [
                [
                    'label' => 'Tier 1 · Above the Fold',
                    'price' => 'Starting at रु25,000 / week',
                    'features' => [
                        'Homepage hero + category masthead',
                        'Max visibility for product launches',
                        'Guaranteed 200k+ impressions'
                    ]
                ],
                [
                    'label' => 'Tier 2 · Mid Page Slots',
                    'price' => 'Starting at रु15,000 / week',
                    'features' => [
                        'Featured across product grids',
                        'Great for seasonal promos',
                        'Creative swaps included'
                    ]
                ],
                [
                    'label' => 'Tier 3 · Retargeting Add-ons',
                    'price' => 'Starting at रु8,000 / week',
                    'features' => [
                        'Cart, wishlist & blog placements',
                        'Perfect for remarketing flows',
                        'Performance recap every Friday'
                    ]
                ]
            ];
            foreach ($tiers as $tier): ?>
                <div class="bg-white border border-neutral-100 rounded-2xl p-6 shadow-sm flex flex-col gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary/80"><?= $tier['label'] ?></p>
                        <h3 class="text-xl font-bold text-mountain mt-2"><?= $tier['price'] ?></h3>
                    </div>
                    <ul class="space-y-2 text-sm text-neutral-600">
                        <?php foreach ($tier['features'] as $feature): ?>
                            <li class="flex items-start gap-2">
                                <span class="text-primary mt-0.5">•</span>
                                <span><?= $feature ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="https://wa.me/9811388848?text=I%20want%20to%20book%20<?= urlencode($tier['label']) ?>%20slot"
                       target="_blank"
                       rel="noopener"
                       class="btn btn-primary w-full justify-center py-2.5">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12.04 2C6.58 2 2.16 6.42 2.16 11.88c0 2.09.61 4.04 1.78 5.71L2 22l4.52-1.36a10 10 0 0 0 5.52 1.6h.01c5.46 0 9.88-4.42 9.88-9.88S17.5 2 12.04 2z"/>
                        </svg>
                        Get Slot
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-white rounded-2xl border border-neutral-100 shadow-sm">
            <?php include __DIR__ . '/../components/pricingads.php'; ?>
        </div>

        <div class="text-center space-y-3">
            <h3 class="text-2xl font-semibold text-primary">Need a custom package?</h3>
            <p class="text-neutral-600">Share your campaign objective and we’ll craft a tailored media plan within 24 hours.</p>
            <div class="flex justify-center">
                <a href="mailto:ads@nutrinexus.com?subject=NutriNexus%20Ad%20Inventory"
                   class="btn btn-secondary px-6 py-3">
                    Email Media Desk
                </a>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

