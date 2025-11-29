<?php ob_start(); ?>
<?php
$referralLink = \App\Core\View::url('auth/register?ref=' . ($user['referral_code'] ?? ''));
?>
<div class="min-h-screen bg-neutral-50">
    <div class="container mx-auto px-4 py-6 max-w-4xl">
            <h1 class="font-heading text-3xl text-primary mb-8">Invite Friends & Earn</h1>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <?php 
                    $isSuccess = ($_SESSION['flash_type'] ?? '') === 'success';
                    $flashClasses = $isSuccess 
                        ? 'bg-success/10 border-success text-success' 
                        : 'bg-error/10 border-error text-error';
                ?>
                <div class="<?= $flashClasses ?> border-l-4 px-4 py-3 rounded-lg relative mb-6" role="alert">
                    <span class="block sm:inline"><?= $_SESSION['flash_message'] ?></span>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
                <?php unset($_SESSION['flash_type']); ?>
            <?php endif; ?>
            
            <?php if (!empty($isSponsorActive)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden mb-8">
                <div class="p-6 border-b border-neutral-200">
                    <h2 class="font-heading text-xl text-primary">Your Referral Link</h2>
                    <p class="text-sm text-neutral-600 mt-2">Share this link with your friends and earn <?= $commissionRate ?? 5 ?>% commission on their purchases!</p>
                </div>
                
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <input type="text" value="<?= htmlspecialchars($referralLink, ENT_QUOTES, 'UTF-8') ?>" 
                               class="input native-input flex-1 bg-neutral-50" readonly id="referralLink" data-referral-link="<?= htmlspecialchars($referralLink, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" onclick="copyReferralLink()" class="btn btn-primary" id="copyReferralBtn">
                            Copy Link
                        </button>
                    </div>
                    <p id="copyMessage" class="text-success mt-2 hidden">Link copied to clipboard!</p>
                    <p id="copyError" class="text-error mt-2 hidden">Unable to copy automatically. Please copy the link manually.</p>
                </div>
            </div>
<?php else: ?>
            <div class="bg-white rounded-2xl shadow-sm border border-error/30 overflow-hidden mb-8">
                <div class="p-6 border-b border-error/40">
                    <h2 class="font-heading text-xl text-error">Sponsorship Inactive</h2>
                    <p class="text-sm text-error/80 mt-2">Your sponsor status is inactive. You can browse referrals but are not eligible for sponsor income until activated.</p>
                </div>
                <div class="p-6">
                    <p class="text-neutral-700">Please contact support to activate your sponsor account.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-sm p-6 text-center border border-neutral-100">
                    <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-primary mb-2">Total Referrals</h3>
                    <p class="text-3xl font-bold text-primary"><?= $referralCount ?? 0 ?></p>
                    <p class="text-sm text-neutral-500 mt-1">People you've referred</p>
                </div>
                
                <div class="bg-white rounded-2xl shadow-sm p-6 text-center border border-neutral-100">
                    <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-primary mb-2">Total Earnings</h3>
                    <p class="text-3xl font-bold text-accent">रु<?= number_format($earnings ?? 0, 2) ?></p>
                    <p class="text-sm text-neutral-500 mt-1">Commission earned</p>
                </div>
                
                <div class="bg-white rounded-2xl shadow-sm p-6 text-center border border-neutral-100">
                    <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-primary mb-2">Referred Orders</h3>
                    <p class="text-3xl font-bold text-primary"><?= $stats['referred_orders'] ?? 0 ?></p>
                    <p class="text-sm text-neutral-500 mt-1">Orders from referrals</p>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden mb-8">
                <div class="p-6 border-b border-neutral-200">
                    <h2 class="font-heading text-xl text-primary">How It Works</h2>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-xl font-bold text-primary">1</span>
                            </div>
                            <h3 class="text-lg font-medium text-primary mb-2">Share Your Link</h3>
                            <p class="text-sm text-neutral-600">Share your unique referral link with friends and family</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-xl font-bold text-primary">2</span>
                            </div>
                            <h3 class="text-lg font-medium text-primary mb-2">They Shop</h3>
                            <p class="text-sm text-neutral-600">When they make a purchase using your link, it's tracked automatically</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-xl font-bold text-primary">3</span>
                            </div>
                            <h3 class="text-lg font-medium text-primary mb-2">You Earn</h3>
                            <p class="text-sm text-neutral-600">Earn 10% commission on every purchase they make, regardless of payment method</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($referrals)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden">
                    <div class="p-6 border-b border-neutral-200">
                        <h2 class="font-heading text-xl text-primary">Your Referrals (<?= count($referrals) ?>)</h2>
                        <p class="text-sm text-neutral-600 mt-1">People you've successfully referred to NutriNexus</p>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($referrals as $referral): ?>
                                <div class="bg-neutral-50 rounded-lg p-6 border border-neutral-200 hover:shadow-md transition-shadow">
                                    <!-- Profile Image and Name -->
                                    <div class="flex items-center mb-4">
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($referral['profile_image'])): ?>
                                                <img src="<?= ASSETS_URL ?>/profileimage/<?= htmlspecialchars($referral['profile_image']) ?>" 
                                                     alt="<?= htmlspecialchars($referral['first_name'] . ' ' . $referral['last_name']) ?>"
                                                     class="h-12 w-12 rounded-full object-cover border-2 border-white shadow-sm"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white font-bold text-sm border-2 border-white shadow-sm" style="display: none;">
                                                    <?= strtoupper(substr($referral['first_name'], 0, 1) . substr($referral['last_name'], 0, 1)) ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="h-12 w-12 rounded-full bg-primary flex items-center justify-center text-white font-bold text-lg">
                                                    <?= strtoupper(substr($referral['first_name'], 0, 1) . substr($referral['last_name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <h3 class="text-lg font-semibold text-mountain">
                                                <?= htmlspecialchars($referral['first_name'] . ' ' . $referral['last_name']) ?>
                                            </h3>
                                            <p class="text-sm text-neutral-500"><?= htmlspecialchars($referral['email']) ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Stats -->
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-neutral-600">Orders:</span>
                                            <span class="text-sm font-medium text-primary"><?= $referral['order_count'] ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-neutral-600">Total Spent:</span>
                                            <span class="text-sm font-medium text-accent">रु<?= number_format($referral['total_spent'], 2) ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-neutral-600">Joined:</span>
                                            <span class="text-sm text-neutral-500"><?= date('M j, Y', strtotime($referral['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Status Badge -->
                                    <div class="mt-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-success/10 text-success">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Active Member
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden">
                    <div class="p-6 border-b border-neutral-200">
                        <h2 class="font-heading text-xl text-primary">Your Referrals</h2>
                    </div>
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users text-neutral-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-mountain mb-2">No referrals yet</h3>
                        <p class="text-neutral-500 mb-6">Start sharing your referral link to earn commissions!</p>
                        <button type="button" onclick="copyReferralLink()" class="btn btn-primary" id="copyReferralBtnEmpty">
                            Copy Your Referral Link
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
async function copyReferralLink() {
    var linkInput = document.getElementById("referralLink");
    var referralUrl = linkInput?.dataset?.referralLink || linkInput?.value || "";
    var successMessage = document.getElementById("copyMessage");
    var errorMessage = document.getElementById("copyError");

    if (successMessage) { successMessage.classList.add("hidden"); }
    if (errorMessage) { errorMessage.classList.add("hidden"); }

    if (!referralUrl) {
        if (errorMessage) { errorMessage.classList.remove("hidden"); }
        return;
    }

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(referralUrl);
        } else {
            linkInput.readOnly = false;
            linkInput.focus();
            linkInput.select();
            document.execCommand("copy");
            linkInput.readOnly = true;
            linkInput.setSelectionRange(0, 0);
        }

        if (successMessage) {
            successMessage.classList.remove("hidden");
            setTimeout(function() {
                successMessage.classList.add("hidden");
            }, 2500);
        }
    } catch (error) {
        if (errorMessage) {
            errorMessage.classList.remove("hidden");
            setTimeout(function() {
                errorMessage.classList.add("hidden");
            }, 3500);
        }
    }
}
</script>
<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>