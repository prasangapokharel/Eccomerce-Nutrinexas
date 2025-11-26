<?php ob_start(); ?>
<?php
$title = 'My Account - NutriNexas';
$description = 'Manage your account, earnings, affiliate program, and more at NutriNexas.';

$displayName = htmlspecialchars($_SESSION['user_name'] ?? ($user['first_name'] ?? 'User'));
$displayEmail = htmlspecialchars($user['email'] ?? 'hello@nutrinexus.com');
$isVip = isset($user['sponsor_status']) && $user['sponsor_status'] === 'active';

// Ensure ASSETS_URL is defined
if (!defined('ASSETS_URL')) {
    if (defined('URLROOT')) {
        define('ASSETS_URL', URLROOT . '/public');
    } elseif (defined('BASE_URL')) {
        define('ASSETS_URL', BASE_URL . (defined('DEVELOPMENT') && DEVELOPMENT ? '' : '/public'));
    } else {
        define('ASSETS_URL', '');
    }
}

$defaultAvatarUrl = \App\Core\View::asset('images/default-avatar.png');
$profileImageUrl = $defaultAvatarUrl;

if (!empty($user['profile_image'])) {
    $rawProfileImage = $user['profile_image'];
    if (filter_var($rawProfileImage, FILTER_VALIDATE_URL)) {
        $profileImageUrl = $rawProfileImage;
    } else {
        $profileImageUrl = ASSETS_URL . '/profileimage/' . ltrim($rawProfileImage, '/');
    }
}

$profileImageUrl = htmlspecialchars($profileImageUrl, ENT_QUOTES, 'UTF-8');
$defaultAvatarUrl = htmlspecialchars($defaultAvatarUrl, ENT_QUOTES, 'UTF-8');
$vipBadgeUrl = htmlspecialchars(\App\Core\View::asset('images/icons/vip.png'), ENT_QUOTES, 'UTF-8');

$primaryLinks = [
    ['label' => 'My Profile', 'hint' => 'Personal info', 'href' => \App\Core\View::url('user/profile'), 'icon' => 'user'],
    ['label' => 'My Orders', 'hint' => 'Track purchases', 'href' => \App\Core\View::url('orders'), 'icon' => 'bag'],
    ['label' => 'Saved Addresses', 'hint' => 'Delivery locations', 'href' => \App\Core\View::url('user/addresses'), 'icon' => 'map'],
    ['label' => 'Payment Methods', 'hint' => 'Secure payments', 'href' => \App\Core\View::url('user/payments'), 'icon' => 'card'],
    ['label' => 'My Balance', 'hint' => 'View earnings', 'href' => \App\Core\View::url('user/balance'), 'icon' => 'wallet'],
    ['label' => 'Withdraw', 'hint' => 'Request withdrawal', 'href' => \App\Core\View::url('user/withdraw'), 'icon' => 'withdraw']
];

$supportLinks = [
    ['label' => 'Invite Friends', 'hint' => 'Share & earn', 'href' => \App\Core\View::url('user/invite'), 'icon' => 'gift'],
    ['label' => 'Help & Support', 'hint' => 'FAQ & chat', 'href' => \App\Core\View::url('support'), 'icon' => 'help'],
    ['label' => 'Settings', 'hint' => 'Notifications, security', 'href' => \App\Core\View::url('user/settings'), 'icon' => 'settings']
];

function renderAccountIcon($name)
{
    switch ($name) {
        case 'user':
            return '<path d="M12 14c-4 0-7 2-7 4v1h14v-1c0-2-3-4-7-4zm0-2a4 4 0 100-8 4 4 0 000 8z"/>';
        case 'bag':
            return '<path d="M6 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-1"/><path d="M16 7a4 4 0 10-8 0"/>';
        case 'map':
            return '<path d="M9 3l-6 3v13l6-3 6 3 6-3V3l-6 3-6-3z"/><path d="M9 3v13"/><path d="M15 6v13"/>';
        case 'card':
            return '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/>';
        case 'gift':
            return '<rect x="2" y="7" width="20" height="13" rx="2"/><path d="M12 7v13"/><path d="M2 12h20"/><path d="M12 7a2.5 2.5 0 01-2.5-2.5C9.5 3 10.5 2 12 3.5 13.5 2 14.5 3 14.5 4.5A2.5 2.5 0 0112 7z"/>';
        case 'help':
            return '<circle cx="12" cy="12" r="9"/><path d="M9 9a3 3 0 115 2c-1 1-.8 1.5-1 3"/><circle cx="12" cy="17" r=".5"/>';
        case 'settings':
            return '<circle cx="12" cy="12" r="3"/><path d="M19 15a1.65 1.65 0 00.33 1.82l.05.05a2 2 0 01-2.82 2.82l-.06-.05A1.65 1.65 0 0015 19a1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19a1.65 1.65 0 00-1.82.33l-.05.05a2 2 0 01-2.82-2.82l.05-.05A1.65 1.65 0 005 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 005 9a1.65 1.65 0 00-.33-1.82l-.05-.05a2 2 0 012.82-2.82l.05.05A1.65 1.65 0 009 5a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09A1.65 1.65 0 0015 5a1.65 1.65 0 001.82-.33l.05-.05a2 2 0 012.82 2.82l-.05.05A1.65 1.65 0 0019 9c0 .55.22 1.08.6 1.47l.05.05A2 2 0 0121 12a2 2 0 01-1.35 1.859c-.36.145-.65.436-.8.8z"/>';
        case 'wallet':
            return '<rect x="2" y="7" width="20" height="13" rx="2"/><path d="M12 7v13"/><path d="M2 12h20"/><path d="M12 7a2.5 2.5 0 01-2.5-2.5C9.5 3 10.5 2 12 3.5 13.5 2 14.5 3 14.5 4.5A2.5 2.5 0 0112 7z"/>';
        case 'withdraw':
            return '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>';
        default:
            return '<circle cx="12" cy="12" r="9"/>';
    }
}
?>

<div class="min-h-screen bg-neutral-50 py-6 px-4">
    <div class="w-full max-w-3xl mx-auto bg-white rounded-3xl border border-primary/10 shadow-sm px-5 py-6 space-y-6">
        <header class="flex items-center justify-between text-primary">
            <button type="button" class="w-10 h-10 rounded-2xl border border-primary/20 flex items-center justify-center" onclick="history.back()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 4L6 12l8 8"/>
                </svg>
            </button>
            <div class="text-sm font-semibold tracking-wide">My Account</div>
            <a href="<?= \App\Core\View::url('user/notifications') ?>" class="w-10 h-10 rounded-2xl border border-primary/20 flex items-center justify-center">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 16H5l1-1V9a4 4 0 118 0v6l1 1h-2z"/>
                    <path d="M10 19a2 2 0 002-2H8a2 2 0 002 2z"/>
                </svg>
            </a>
        </header>

        <section class="bg-primary/5 rounded-3xl p-4 text-primary flex items-center gap-4">
            <div class="relative flex-shrink-0">
                <div class="relative w-14 h-14 rounded-2xl bg-white border <?= $isVip ? 'border-primary border-2' : 'border-primary/20' ?> overflow-hidden shadow-sm">
                    <img src="<?= $profileImageUrl ?>"
                         alt="<?= $displayName ?> profile photo"
                         loading="lazy"
                         decoding="async"
                         class="w-full h-full object-cover"
                         onerror="this.src='<?= $defaultAvatarUrl ?>'; this.onerror=null;">
                </div>
                <?php if ($isVip): ?>
                    <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-white rounded-full p-0.5 shadow-lg border border-primary/20 flex items-center justify-center">
                        <img src="<?= $vipBadgeUrl ?>"
                             alt="VIP"
                             class="w-full h-full object-contain"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="hidden w-full h-full items-center justify-center bg-gradient-to-br from-primary to-primary/80 rounded-full">
                            <span class="text-[8px] font-bold text-white leading-none">VIP</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-primary/70">Hello,</p>
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <?= $displayName ?>
                    <?php if ($isVip): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gradient-to-r from-primary to-primary/90 text-white shadow-sm">
                            VIP
                        </span>
                    <?php endif; ?>
                </h2>
                <p class="text-xs text-primary/70 truncate"><?= $displayEmail ?></p>
            </div>
        </section>

        <section class="space-y-3">
            <?php foreach ($primaryLinks as $link): ?>
                <a href="<?= $link['href'] ?>" class="flex items-center gap-3 rounded-2xl border border-primary/10 bg-white px-4 py-3 transition hover:border-primary/30 hover:bg-primary/5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <?= renderAccountIcon($link['icon']) ?>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-primary"><?= $link['label'] ?></p>
                        <span class="text-xs text-primary/60"><?= $link['hint'] ?></span>
                    </div>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                        <path d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            <?php endforeach; ?>
        </section>

        <section class="space-y-3">
            <?php foreach ($supportLinks as $link): ?>
                <a href="<?= $link['href'] ?>" class="flex items-center gap-3 rounded-2xl border border-primary/10 bg-white px-4 py-3 transition hover:border-primary/30 hover:bg-primary/5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <?= renderAccountIcon($link['icon']) ?>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-primary"><?= $link['label'] ?></p>
                        <?php if (!empty($link['hint'])): ?>
                            <span class="text-xs text-primary/60"><?= $link['hint'] ?></span>
                        <?php endif; ?>
                    </div>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                        <path d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            <?php endforeach; ?>
        </section>

        <a href="<?= \App\Core\View::url('auth/logout') ?>" class="w-full bg-destructive/10 text-destructive font-semibold px-6 py-3 rounded-2xl text-sm text-center border border-destructive/20 block">
            Log Out
        </a>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
