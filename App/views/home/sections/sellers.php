<?php
/**
 * Featured Sellers Section
 * 
 * Displays approved sellers with their logos in an optimized grid/carousel
 */

// Get sellers from parent scope or load them
if (!isset($sellers)) {
    $sellerModel = new \App\Models\Seller();
    $db = \App\Core\Database::getInstance();
    
    // Get active and approved sellers with logos
    $sellers = $db->query(
        "SELECT id, name, company_name, logo_url, status, is_approved
         FROM sellers 
         WHERE status = 'active' 
         AND is_approved = 1 
         AND logo_url IS NOT NULL 
         AND logo_url != ''
         ORDER BY created_at DESC
         LIMIT 12"
    )->all();
}

if (empty($sellers)) {
    return;
}

$useMarquee = count($sellers) > 8;
?>

<div class="bg-white mx-2 rounded-xl shadow-sm mb-4 overflow-hidden">
    <div class="flex items-center justify-between p-3 sm:p-4 border-b border-gray-100">
        <div>
            <h3 class="text-lg sm:text-xl font-bold text-gray-900">Our Trusted Sellers</h3>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Shop from verified and approved sellers</p>
        </div>
        <?php if (count($sellers) > 8): ?>
            <a href="<?= \App\Core\View::url('sellers') ?>" 
               class="text-primary hover:text-primary-dark font-medium text-sm sm:text-base transition-colors">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        <?php endif; ?>
    </div>
    
    <div class="p-3 sm:p-4">
        <?php if ($useMarquee): ?>
            <!-- Marquee for many sellers -->
            <div class="overflow-hidden">
                <div class="flex gap-4 sm:gap-6 animate-marquee">
                    <?php 
                    // Duplicate sellers for seamless loop
                    $duplicatedSellers = array_merge($sellers, $sellers);
                    foreach ($duplicatedSellers as $seller): 
                    ?>
                        <div class="flex-shrink-0 w-32 sm:w-40 group">
                            <a href="<?= \App\Core\View::url('seller/' . urlencode($seller['company_name'] ?? $seller['name'])) ?>" 
                               class="block bg-gray-50 rounded-xl p-4 sm:p-5 hover:bg-primary/5 transition-all duration-300 hover:shadow-md border border-gray-100 hover:border-primary/20">
                                <div class="aspect-square mb-3 flex items-center justify-center">
                                    <img src="<?= htmlspecialchars($seller['logo_url']) ?>" 
                                         alt="<?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>"
                                         class="max-w-full max-h-full object-contain filter group-hover:scale-105 transition-transform duration-300"
                                         loading="lazy"
                                         onerror="this.onerror=null; this.src='<?= \App\Core\View::asset('images/default-avatar.png') ?>'">
                                </div>
                                <div class="text-center">
                                    <p class="text-xs sm:text-sm font-semibold text-gray-900 line-clamp-2 group-hover:text-primary transition-colors">
                                        <?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>
                                    </p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Grid for fewer sellers -->
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3 sm:gap-4">
                <?php foreach ($sellers as $seller): ?>
                    <div class="group">
                        <a href="<?= \App\Core\View::url('seller/' . urlencode($seller['company_name'] ?? $seller['name'])) ?>" 
                           class="block bg-gray-50 rounded-xl p-3 sm:p-4 hover:bg-primary/5 transition-all duration-300 hover:shadow-md border border-gray-100 hover:border-primary/20">
                            <div class="aspect-square mb-2 sm:mb-3 flex items-center justify-center">
                                <img src="<?= htmlspecialchars($seller['logo_url']) ?>" 
                                     alt="<?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>"
                                     class="max-w-full max-h-full object-contain filter group-hover:scale-105 transition-transform duration-300"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='<?= \App\Core\View::asset('images/default-avatar.png') ?>'">
                            </div>
                            <div class="text-center">
                                <p class="text-xs sm:text-sm font-semibold text-gray-900 line-clamp-2 group-hover:text-primary transition-colors">
                                    <?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
@keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

.animate-marquee {
    animation: marquee 30s linear infinite;
}

.animate-marquee:hover {
    animation-play-state: paused;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

