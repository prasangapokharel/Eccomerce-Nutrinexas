<?php
$title = 'Blog - Health & Nutrition Articles | NutriNexas';
$description = 'Discover expert health and nutrition articles, supplement guides, and wellness tips from NutriNexas professionals.';
ob_start();
?>

<div class="min-h-screen bg-neutral-50 pb-10">
    <!-- Blog Hero Section -->
    <div class="mx-4 mt-6 rounded-3xl bg-gradient-to-r from-primary to-secondary shadow-lg overflow-hidden">
        <div class="flex flex-col md:flex-row items-center text-white px-6 py-10 gap-6">
            <div class="flex-1 text-center md:text-left">
                <p class="text-xs uppercase tracking-[0.3em] font-semibold text-white/80 mb-3">NutriNexus Journal</p>
                <h1 class="text-3xl md:text-4xl font-bold leading-tight mb-3">Health & Nutrition Insights for Every Goal</h1>
                <p class="text-white/90 max-w-2xl">Curated articles, supplement breakdowns, meal plans, and wellbeing tactics—designed with the same polish as our storefront product cards.</p>
            </div>
        </div>
    </div>

    <?php
    $renderBlogCard = function($post, $variant = 'standard') {
        $category = $post['category_name'] ?? null;
        $author = trim(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? '')) ?: 'NutriNexus Editorial';
        $date = date('M j, Y', strtotime($post['published_at'] ?? $post['created_at'] ?? 'now'));
        $readingTime = $post['reading_time'] ?? '—';
        $image = $post['featured_image'] ?? null;
        $excerpt = $post['excerpt'] ?? '';
        $sizeClasses = $variant === 'feature' ? 'aspect-[5/3] md:aspect-[4/3]' : 'aspect-[4/3]';
    ?>
        <a href="<?= \App\Core\View::url('blog/view/' . $post['slug']) ?>" class="group block">
            <div class="relative bg-white border border-neutral-100 rounded-2xl shadow-sm overflow-hidden hover:shadow-lg">
                <div class="relative <?= $sizeClasses ?> bg-neutral-100 overflow-hidden">
                    <?php if ($image): ?>
                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($post['title']) ?>"
                         class="w-full h-full object-cover" loading="lazy">
                    <?php endif; ?>
                    <?php if ($category): ?>
                    <span class="absolute top-4 left-4 inline-flex items-center rounded-full bg-white/90 text-primary text-xs font-semibold px-3 py-1 shadow">
                        <?= htmlspecialchars($category) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex items-center justify-between text-xs text-neutral-500">
                        <span><?= $date ?></span>
                        <span class="inline-flex items-center gap-1"><i class="far fa-clock"></i> <?= $readingTime ?> min read</span>
                    </div>
                    <h3 class="text-lg font-semibold text-primary line-clamp-2 group-hover:text-primary-dark">
                        <?= htmlspecialchars($post['title']) ?>
                    </h3>
                    <p class="text-sm text-neutral-600 line-clamp-3"><?= htmlspecialchars($excerpt) ?></p>
                    <div class="flex items-center justify-between text-xs text-neutral-500">
                        <span>By <?= htmlspecialchars($author) ?></span>
                        <span class="inline-flex items-center gap-1 text-primary font-semibold">
                            Read Article <i class="fas fa-arrow-right text-xs"></i>
                        </span>
                    </div>
                </div>
            </div>
        </a>
    <?php };
    ?>

    <!-- Featured Posts -->
    <?php if (!empty($data['featuredPosts'])): ?>
    <div class="bg-white mx-4 rounded-3xl shadow-sm border border-neutral-100 mb-6">
        <div class="flex items-center justify-between px-6 py-5 border-b border-neutral-100">
            <div>
                <p class="text-xs uppercase tracking-widest text-primary/70 font-semibold">Editor’s Picks</p>
                <h2 class="text-2xl font-bold text-mountain">Featured Articles</h2>
            </div>
            <a href="<?= \App\Core\View::url('blog') ?>" class="btn btn-secondary px-4 py-2 text-sm">View All</a>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach (array_slice($data['featuredPosts'], 0, 3) as $post): ?>
                    <?php $renderBlogCard($post, 'feature'); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Categories -->
    <?php if (!empty($data['categories'])): ?>
    <div class="bg-white mx-4 rounded-xl shadow-sm mb-4">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900">Categories</h2>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <?php foreach ($data['categories'] as $category): ?>
                <a href="<?= \App\Core\View::url('blog/category/' . $category['slug']) ?>" 
                   class="text-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($category['name']) ?></div>
                    <?php if ($category['description']): ?>
                    <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars(substr($category['description'], 0, 50)) ?>...</div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Latest Articles -->
    <div class="bg-white mx-4 rounded-3xl shadow-sm border border-neutral-100 mb-6">
        <div class="flex items-center justify-between px-6 py-5 border-b border-neutral-100">
            <div>
                <p class="text-xs uppercase tracking-widest text-primary/70 font-semibold">Fresh Reads</p>
                <h2 class="text-2xl font-bold text-mountain">Latest Articles</h2>
            </div>
        </div>
        <div class="p-6">
            <?php if (empty($data['posts'])): ?>
            <div class="text-center py-8">
                <div class="text-gray-500 mb-2">
                    <i class="fas fa-newspaper text-4xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No articles found</h3>
                <p class="text-gray-600">Check back soon for new health and nutrition content!</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($data['posts'] as $post): ?>
                    <?php $renderBlogCard($post); ?>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($data['totalPages'] > 1): ?>
            <div class="flex justify-center mt-6">
                <div class="flex space-x-2">
                    <?php if ($data['currentPage'] > 1): ?>
                    <a href="<?= \App\Core\View::url('blog?page=' . ($data['currentPage'] - 1)) ?>" 
                       class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $data['totalPages']; $i++): ?>
                    <a href="<?= \App\Core\View::url('blog?page=' . $i) ?>" 
                       class="px-3 py-2 text-sm border rounded-md <?= $i === $data['currentPage'] ? 'bg-primary text-white border-primary' : 'border-gray-300 hover:bg-gray-50' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($data['currentPage'] < $data['totalPages']): ?>
                    <a href="<?= \App\Core\View::url('blog?page=' . ($data['currentPage'] + 1)) ?>" 
                       class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Popular Articles Sidebar -->
    <?php if (!empty($data['popularPosts'])): ?>
    <div class="bg-white mx-4 rounded-xl shadow-sm mb-4">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900">Popular Articles</h2>
        </div>
        <div class="p-4">
            <div class="space-y-3">
                <?php foreach ($data['popularPosts'] as $popular): ?>
                <div class="flex space-x-3 cursor-pointer hover:bg-neutral-50 p-2 rounded-lg"
                     onclick="window.location.href='<?= \App\Core\View::url('blog/view/' . $popular['slug']) ?>'">
                    <?php if ($popular['featured_image']): ?>
                    <img src="<?= htmlspecialchars($popular['featured_image']) ?>" 
                         alt="<?= htmlspecialchars($popular['title']) ?>"
                         class="w-16 h-16 object-cover rounded-lg">
                    <?php endif; ?>
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 line-clamp-2 mb-1">
                            <?= htmlspecialchars($popular['title']) ?>
                        </h4>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span><?= number_format($popular['views_count'] ?? 0) ?> views</span>
                            <span><?= date('M j', strtotime($popular['published_at'] ?? $popular['created_at'] ?? 'now')) ?></span>
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
.line-clamp-2 {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
    -webkit-line-clamp: 2;
    line-clamp: 2;
}
.line-clamp-3 {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
    -webkit-line-clamp: 3;
    line-clamp: 3;
}
.aspect-video {
    aspect-ratio: 16 / 9;
}
</style>

<?php
$content = ob_get_clean();
include dirname(dirname(__FILE__)) . '/layouts/main.php';
?>
