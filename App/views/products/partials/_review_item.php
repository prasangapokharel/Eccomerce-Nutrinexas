<?php
// This partial view is used for displaying individual review items
// It can be rendered server-side and returned via AJAX for dynamic insertion
?>
<div class="bg-gray-50 rounded-lg p-4 mb-4 review-item" data-review-id="<?= $rev['id'] ?>">
    <div class="flex items-start justify-between mb-2">
        <div class="flex items-center">
            <div class="relative mr-3">
                <?php if (!empty($rev['profile_image'])): ?>
                    <?php 
                    $profileImageUrl = filter_var($rev['profile_image'], FILTER_VALIDATE_URL)
                        ? $rev['profile_image']
                        : ASSETS_URL . '/profileimage/' . basename($rev['profile_image']);
                    ?>
                    <div class="w-10 h-10 <?= isset($rev['sponsor_status']) && $rev['sponsor_status'] === 'active' ? 'ring-2 ring-accent' : '' ?> rounded-full overflow-hidden">
                        <img src="<?= htmlspecialchars($profileImageUrl) ?>" 
                             alt="Profile" 
                             class="w-full h-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                        <div class="w-full h-full bg-primary rounded-full flex items-center justify-center text-white font-bold text-sm" style="display: none;">
                            <?= !empty($rev['user_id']) ? substr($rev['first_name'] ?? 'G', 0, 1) : 'G' ?>
                        </div>
                    </div>
                    <?php if (isset($rev['sponsor_status']) && $rev['sponsor_status'] === 'active'): ?>
                        <div class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-white rounded-full p-0.5 shadow-md">
                            <img src="<?= ASSETS_URL ?>/images/icons/vip.png" 
                                 alt="VIP" 
                                 class="w-full h-full object-contain"
                                 onerror="this.style.display='none';">
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-bold text-sm">
                        <?= !empty($rev['user_id']) ? substr($rev['first_name'] ?? 'G', 0, 1) : 'G' ?>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900 text-sm">
                    <?= !empty($rev['user_id']) ? htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']) : 'Guest' ?>
                </h4>
                <div class="flex items-center">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg class="w-4 h-4 <?= $i <= $rev['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">
                <?= date('M j, Y', strtotime($rev['created_at'])) ?>
            </span>
            <?php 
            // Check if this review belongs to the logged-in user
            $currentUserId = \App\Core\Session::get('user_id');
            $isOwnReview = !empty($currentUserId) && !empty($rev['user_id']) && (int)$rev['user_id'] === (int)$currentUserId;
            ?>
            <?php if ($isOwnReview): ?>
                <button type="button" 
                        class="delete-review-btn text-red-500 hover:text-red-700 transition-colors p-1.5 hover:bg-red-50 rounded-lg" 
                        data-review-id="<?= $rev['id'] ?>"
                        title="Delete Review">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
    </div>
    <p class="text-gray-700 text-sm mb-3"><?= htmlspecialchars($rev['review'] ?? $rev['review_text'] ?? '') ?></p>
    
    <?php if (!empty($rev['image_path'])): ?>
        <div class="mt-3">
            <?php 
            // Check if it's a cloud URL or local path
            $imageSrc = filter_var($rev['image_path'], FILTER_VALIDATE_URL) 
                ? $rev['image_path'] 
                : ASSETS_URL . '/uploads/reviews/' . basename($rev['image_path']);
            ?>
            <img src="<?= htmlspecialchars($imageSrc) ?>" alt="Review image" class="max-h-32 rounded-lg object-contain border border-gray-100" onerror="this.style.display='none'" />
        </div>
    <?php endif; ?>
</div>
