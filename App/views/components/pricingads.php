<?php
$tiers = [
    [
        'key' => 'tier1',
        'label' => 'Tier 1 · Hero + Category Masthead',
        'price' => 'रु25,000 / week',
        'availability' => 'Dec 2 – Dec 8',
        'cta' => 'https://wa.me/9811388848?text=Book%20Tier%201%20Hero%20slot'
    ],
    [
        'key' => 'tier2',
        'label' => 'Tier 2 · Mid Page Discovery',
        'price' => 'रु15,000 / week',
        'availability' => 'Dec 5 – Dec 11',
        'cta' => 'https://wa.me/9811388848?text=Book%20Tier%202%20Mid%20page%20slot'
    ],
    [
        'key' => 'tier3',
        'label' => 'Tier 3 · Retarget + Blog',
        'price' => 'रु8,000 / week',
        'availability' => 'Dec 8 – Dec 14',
        'cta' => 'https://wa.me/9811388848?text=Book%20Tier%203%20Retarget%20slot'
    ]
];

$rows = [
    'Placement Reach' => [
        'tier1' => 'Homepage hero banner + top category masthead',
        'tier2' => 'Featured slots inside product grids & flash sale blocks',
        'tier3' => 'Cart, wishlist, blog sidebar + email follow-ups'
    ],
    'Guaranteed Impressions (per week)' => [
        'tier1' => '200k+ views',
        'tier2' => '120k+ views',
        'tier3' => '70k+ views'
    ],
    'Best For' => [
        'tier1' => 'Brand launches, festival campaigns',
        'tier2' => 'Seasonal promos, bundle pushes',
        'tier3' => 'Remarketing, restock alerts'
    ],
    'Creative Support' => [
        'tier1' => '3 refreshes + copy polishing',
        'tier2' => '2 refreshes + QA',
        'tier3' => '1 refresh + tracking tags'
    ],
    'Next Available Slot' => [
        'tier1' => $tiers[0]['availability'],
        'tier2' => $tiers[1]['availability'],
        'tier3' => $tiers[2]['availability']
    ]
];
?>

<div class="bg-white border border-neutral-100 rounded-2xl shadow-sm">
    <div class="px-4 py-6 text-center space-y-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-primary/70">Media kit snapshot</p>
        <h2 class="text-3xl font-bold text-primary">Compare Banner Slots</h2>
        <p class="text-neutral-600 text-sm">All tiers include live reporting, fraud-free traffic, and WhatsApp support.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-t border-neutral-100 text-sm">
            <thead class="bg-neutral-50 text-left">
                <tr>
                    <th class="px-4 py-4 text-neutral-600 font-semibold min-w-[200px]">Deliverables</th>
                    <?php foreach ($tiers as $tier): ?>
                        <th class="px-4 py-4 text-center">
                            <p class="text-primary font-semibold"><?= $tier['label'] ?></p>
                            <p class="text-mountain font-bold text-lg mt-1"><?= $tier['price'] ?></p>
                            <a href="<?= $tier['cta'] ?>" target="_blank" rel="noopener" class="btn btn-primary w-full mt-3 justify-center py-2 text-sm">
                                Book Slot
                            </a>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $label => $values): ?>
                    <tr class="border-t border-neutral-100">
                        <td class="align-top px-4 py-4 font-medium text-mountain bg-neutral-50"><?= $label ?></td>
                        <?php foreach ($tiers as $tier): ?>
                            <td class="px-4 py-4 text-center text-neutral-700">
                                <?= $values[$tier['key']] ?? '' ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="px-4 py-5 text-center text-xs text-neutral-500 border-t border-neutral-100">
        Need different flight dates? <a href="mailto:ads@nutrinexus.com?subject=Custom%20slot%20dates" class="text-primary font-semibold">Email the media desk</a>.
    </div>
</div>