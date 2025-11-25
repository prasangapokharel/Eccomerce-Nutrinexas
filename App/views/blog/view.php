<?php
$post = $data['post'];
$title = $post['meta_title'] ?: $post['title'];
$description = $post['meta_description'] ?: $post['excerpt'];
ob_start();
?>

<!-- SEO Meta Tags -->
<?php if (!empty($post['meta_keywords'])): ?>
<meta name="keywords" content="<?= htmlspecialchars($post['meta_keywords']) ?>">
<?php endif; ?>

<!-- Open Graph Meta Tags -->
<meta property="og:title" content="<?= htmlspecialchars($post['og_title'] ?: $post['title']) ?>">
<meta property="og:description" content="<?= htmlspecialchars($post['og_description'] ?: $post['excerpt']) ?>">
<meta property="og:type" content="article">
<meta property="og:url" content="<?= htmlspecialchars(\App\Core\View::url('blog/view/' . $post['slug'])) ?>">
<?php if (!empty($post['og_image'] ?: $post['featured_image'])): ?>
<meta property="og:image" content="<?= htmlspecialchars($post['og_image'] ?: $post['featured_image']) ?>">
<?php endif; ?>

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($post['og_title'] ?: $post['title']) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($post['og_description'] ?: $post['excerpt']) ?>">
<?php if (!empty($post['og_image'] ?: $post['featured_image'])): ?>
<meta name="twitter:image" content="<?= htmlspecialchars($post['og_image'] ?: $post['featured_image']) ?>">
<?php endif; ?>

<!-- Canonical URL -->
<?php if (!empty($post['canonical_url'])): ?>
<link rel="canonical" href="<?= htmlspecialchars($post['canonical_url']) ?>">
<?php endif; ?>

<!-- Structured Data -->
<?php if (!empty($data['structuredData'])): ?>
<script type="application/ld+json">
<?= json_encode($data['structuredData'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?>
</script>
<?php endif; ?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100" style="scroll-behavior: smooth;">
    <!-- Hidden breadcrumb on mobile, visible on desktop with modern styling -->
    <div class="hidden md:block mx-4 pt-6 pb-2">
        <nav class="text-sm">
            <div class="flex items-center space-x-2 text-gray-500">
                <a href="<?= \App\Core\View::url('') ?>" class="hover:text-primary transition-colors duration-200 font-medium">Home</a>
                <span class="text-gray-300">/</span>
                <a href="<?= \App\Core\View::url('blog') ?>" class="hover:text-primary transition-colors duration-200 font-medium">Blog</a>
                <?php if (!empty($post['category_name'])): ?>
                <span class="text-gray-300">/</span>
                <a href="<?= isset($post['category_slug']) ? \App\Core\View::url('blog/category/' . $post['category_slug']) : \App\Core\View::url('blog') ?>" class="hover:text-primary transition-colors duration-200 font-medium">
                    <?= htmlspecialchars($post['category_name']) ?>
                </a>
                <?php endif; ?>
                <span class="text-gray-300">/</span>
                <span class="text-primary font-semibold"><?= htmlspecialchars(substr($post['title'], 0, 40)) ?>...</span>
            </div>
        </nav>
    </div>

    <!-- Modern article header with gradient and enhanced styling -->
    <div class="bg-white mx-3 md:mx-4 mt-4 rounded-2xl shadow-lg border border-gray-100 mb-4 overflow-hidden">
        <div class="p-4 md:p-6">
            <?php if (!empty($post['category_name'])): ?>
            <a href="<?= isset($post['category_slug']) ? \App\Core\View::url('blog/category/' . $post['category_slug']) : \App\Core\View::url('blog') ?>" 
               class="inline-flex items-center bg-gradient-to-r from-primary to-primary-dark text-white text-sm px-4 py-2 rounded-full mb-4 hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-tag mr-2 text-xs"></i>
                <?= htmlspecialchars($post['category_name']) ?>
            </a>
            <?php endif; ?>
            
            <h1 class="text-2xl md:text-4xl font-bold bg-gradient-to-r from-primary to-primary-dark bg-clip-text text-transparent mb-4 leading-tight"><?= htmlspecialchars($post['title']) ?></h1>
            
            <?php 
            if ($post['excerpt'] && strtolower(trim($post['excerpt'])) !== strtolower(trim($post['title']))): 
            ?>
            <p class="text-base md:text-lg text-gray-600 mb-6 leading-relaxed"><?= htmlspecialchars($post['excerpt']) ?></p>
            <?php endif; ?>
            
            <!-- Enhanced meta info with modern flex layout and icons -->
            <div class="flex flex-wrap items-center gap-4 md:gap-6 text-sm text-gray-500 mb-6 p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl">
                <div class="flex items-center bg-white px-3 py-2 rounded-lg shadow-sm">
                    <div class="w-2 h-2 bg-[#C5A572] rounded-full mr-3"></div>
                    <span class="font-medium">By <?= htmlspecialchars((!empty($post['first_name']) || !empty($post['last_name'])) ? trim(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? '')) : (!empty($post['author_name']) ? $post['author_name'] : 'Unknown Author')) ?></span>
                </div>
                <div class="flex items-center bg-white px-3 py-2 rounded-lg shadow-sm">
                    <i class="fas fa-calendar text-accent mr-2"></i>
                    <span><?= !empty($post['published_at']) ? date('F j, Y', strtotime($post['published_at'])) : date('F j, Y') ?></span>
                </div>
                <div class="flex items-center bg-white px-3 py-2 rounded-lg shadow-sm">
                    <i class="fas fa-clock text-accent mr-2"></i>
                    <span><?= $post['reading_time'] ?> min read</span>
                </div>
                <div class="flex items-center bg-white px-3 py-2 rounded-lg shadow-sm">
                    <i class="fas fa-eye text-accent mr-2"></i>
                    <span><?= number_format($post['views_count']) ?> views</span>
                </div>
            </div>

            <!-- Perfect image display with proper aspect ratio and no cutting -->
            <?php if ($post['featured_image']): ?>
            <div class="mb-6 -mx-4 md:-mx-6">
                <div class="relative overflow-hidden rounded-xl md:rounded-2xl">
                    <img src="<?= htmlspecialchars($post['featured_image']) ?>" 
                         alt="<?= htmlspecialchars($post['title']) ?>"
                         class="w-full h-64 md:h-96 object-cover transition-transform duration-500 hover:scale-105"
                         style="object-position: center;">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Enhanced article content with better typography -->
    <div class="bg-white mx-3 md:mx-4 rounded-2xl shadow-lg border border-gray-100 mb-4">
        <div class="p-4 md:p-6">
            <div class="prose prose-lg max-w-none">
                <?= !empty($post['content']) ? $post['content'] : '<p class="text-gray-500">Content not available.</p>' ?>
            </div>
        </div>
    </div>

    <!-- Banner Ads Section (External) - Tier 2 Position -->
    <section class="py-4">
        <?php
        use App\Services\BannerAdDisplayService;
        use App\Helpers\AdTrackingHelper;
        
        $bannerService = new BannerAdDisplayService();
        $banner = $bannerService->getBannerForPlacement('tier2', 'blog'); // Tier 2 for blog pages
        ?>
        
        <?php if (!empty($banner)): ?>
            <div class="mx-2 mb-4">
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden relative">
                    <!-- Ads Label - Top Right -->
                    <div class="absolute top-2 right-2 z-20 bg-black/80 text-white px-2.5 py-1 rounded-md text-xs font-bold backdrop-blur-sm shadow-lg" style="letter-spacing: 0.5px;">
                        Ads
                    </div>
                    
                    <a href="<?= htmlspecialchars($banner['banner_link'] ?? '#') ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       onclick="<?= AdTrackingHelper::getClickTrackingJS($banner['id']) ?>"
                       class="block relative">
                        <img src="<?= htmlspecialchars($banner['banner_image']) ?>" 
                             alt="Advertisement" 
                             class="w-full h-auto object-cover rounded-2xl"
                             style="max-width: 100%; height: auto; display: block;"
                             loading="lazy"
                             onload="<?= AdTrackingHelper::getReachTrackingJS($banner['id']) ?>"
                             onerror="this.style.display='none'">
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Modern tags section with gradient styling -->
    <?php if (!empty($post['tags'])): ?>
    <div class="bg-white mx-3 md:mx-4 rounded-2xl shadow-lg border border-gray-100 mb-4">
        <div class="p-4 md:p-6">
            <h3 class="text-lg font-bold text-[#0A3167] mb-4 flex items-center">
                <i class="fas fa-tags text-[#C5A572] mr-2"></i>
                Tags
            </h3>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($post['tags'] as $tag): ?>
                <a href="<?= \App\Core\View::url('blog/tag/' . $tag['slug']) ?>" 
                   class="inline-flex items-center bg-gradient-to-r from-accent to-accent-dark text-white text-sm px-4 py-2 rounded-full hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                    #<?= htmlspecialchars($tag['name']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modern share buttons with gradient styling -->
    <div class="bg-white mx-3 md:mx-4 rounded-2xl shadow-lg border border-gray-100 mb-4">
        <div class="p-4 md:p-6">
            <h3 class="text-lg font-bold text-[#0A3167] mb-4 flex items-center">
                <i class="fas fa-share-alt text-[#C5A572] mr-2"></i>
                Share this article
            </h3>
            <div class="grid grid-cols-2 md:flex md:flex-wrap gap-3">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(\App\Core\View::url('blog/view/' . $post['slug'])) ?>" 
                   target="_blank" class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                    <i class="fab fa-facebook-f mr-2"></i> Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode(\App\Core\View::url('blog/view/' . $post['slug'])) ?>&text=<?= urlencode($post['title']) ?>" 
                   target="_blank" class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-400 to-blue-500 text-white rounded-xl hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                    <i class="fab fa-twitter mr-2"></i> Twitter
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(\App\Core\View::url('blog/view/' . $post['slug'])) ?>" 
                   target="_blank" class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-700 to-blue-800 text-white rounded-xl hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                    <i class="fab fa-linkedin-in mr-2"></i> LinkedIn
                </a>
                <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' ' . \App\Core\View::url('blog/view/' . $post['slug'])) ?>" 
                   target="_blank" class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                    <i class="fab fa-whatsapp mr-2"></i> WhatsApp
                </a>
            </div>
        </div>
    </div>

    <!-- Enhanced related posts with perfect image display -->
    <?php if (!empty($data['relatedPosts'])): ?>
    <div class="bg-white mx-3 md:mx-4 rounded-2xl shadow-lg border border-gray-100 mb-6">
        <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-100">
            <h3 class="text-lg font-bold text-[#0A3167] flex items-center">
                <i class="fas fa-newspaper text-[#C5A572] mr-2"></i>
                Related Articles
            </h3>
        </div>
        <div class="p-4 md:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($data['relatedPosts'] as $related): ?>
                <div class="group cursor-pointer" onclick="window.location.href='<?= \App\Core\View::url('blog/view/' . $related['slug']) ?>'">
                    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden transition-all duration-300 hover:shadow-xl hover:border-[#C5A572] transform hover:scale-105">
                        <?php if ($related['featured_image']): ?>
                        <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                            <img src="<?= htmlspecialchars($related['featured_image']) ?>" 
                                 alt="<?= htmlspecialchars($related['title']) ?>"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                 style="object-position: center;">
                        </div>
                        <?php endif; ?>
                        <div class="p-4">
                            <h4 class="font-bold text-primary mb-2 line-clamp-2 group-hover:text-accent transition-colors duration-200">
                                <?= htmlspecialchars($related['title']) ?>
                            </h4>
                            <p class="text-gray-600 text-sm mb-3 line-clamp-2 leading-relaxed">
                                <?= htmlspecialchars($related['excerpt'] ?? '') ?>
                            </p>
                            <div class="text-xs text-accent font-medium">
                                <?php 
                                    $relatedDate = !empty($related['published_at']) ? $related['published_at'] : (!empty($related['created_at']) ? $related['created_at'] : null);
                                    if ($relatedDate) {
                                        echo date('M j, Y', strtotime($relatedDate));
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Enhanced styles with smooth scrolling and perfect image display */
html {
    scroll-behavior: smooth;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
    -webkit-line-clamp: 2;
    line-clamp: 2;
}

.aspect-video {
    aspect-ratio: 16 / 9;
}

.prose {
    line-height: 1.8;
    color: #374151;
}

.prose h2, .prose h3 {
    margin-top: 2.5rem;
    margin-bottom: 1.25rem;
    font-weight: 700;
    color: #0A3167;
}

.prose h2 {
    font-size: 1.75rem;
    border-bottom: 2px solid #C5A572;
    padding-bottom: 0.5rem;
}

.prose h3 {
    font-size: 1.5rem;
}

.prose p {
    margin-bottom: 1.25rem;
    text-align: justify;
}

.prose ul, .prose ol {
    margin-bottom: 1.25rem;
    padding-left: 2rem;
}

.prose li {
    margin-bottom: 0.75rem;
}

.prose strong {
    font-weight: 700;
    color: #0A3167;
}

.prose a {
    color: #C5A572;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.prose a:hover {
    color: #0A3167;
    text-decoration: underline;
}

.prose blockquote {
    border-left: 4px solid #C5A572;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 1.5rem;
    margin: 2rem 0;
    border-radius: 0.75rem;
    font-style: italic;
}

.prose img {
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    margin: 2rem auto;
    max-width: 100%;
    height: auto;
}

/* Perfect image display - no cutting */
img {
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}

/* Smooth transitions for all interactive elements */
* {
    transition: all 0.2s ease;
}

/* Enhanced mobile responsiveness */
@media (max-width: 768px) {
    .prose {
        font-size: 1rem;
        line-height: 1.7;
    }
    
    .prose h2 {
        font-size: 1.5rem;
    }
    
    .prose h3 {
        font-size: 1.25rem;
    }
}
</style>

<?php
$content = ob_get_clean();
include dirname(dirname(__FILE__)) . '/layouts/main.php';
?>
