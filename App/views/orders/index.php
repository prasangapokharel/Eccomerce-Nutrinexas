<?php ob_start(); ?>

<div class="bg-neutral-50 min-h-screen">
    <div class="bg-neutral-50 px-4 py-8">
        <div class="max-w-screen-xl mx-auto">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="mb-6 rounded-xl border border-success bg-success/10 px-4 py-3 text-sm text-success flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= $_SESSION['flash_message'] ?></span>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 p-10 text-center max-w-lg mx-auto mt-8">
                    <div class="w-20 h-20 mx-auto rounded-full bg-neutral-100 flex items-center justify-center text-neutral-400 mb-4">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-2.293 2.293A1 1 0 005.4 17H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-slate-900 mb-2">No orders yet</h2>
                    <p class="text-sm text-slate-600 mb-6">You haven't placed any orders yet. Start shopping to see your history here.</p>
                    <a href="<?= \App\Core\View::url('products') ?>" class="btn inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="flex flex-wrap justify-between items-center gap-6">
                    <div class="max-w-96">
                        <h2 class="text-slate-900 text-2xl font-bold mb-3">Order History</h2>
                        <p class="text-base text-slate-600">View and manage your past orders</p>
                    </div>
                    <div class="w-full sm:w-auto">
                        <input
                            type="text"
                            id="order-search"
                            class="input w-full text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-primary/40"
                            placeholder="Search orders..."
                        />
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-8 mt-12">
                    <div class="flex flex-wrap items-center gap-3" id="order-filters">
                        <span class="text-[15px] font-medium text-slate-600">Filter by:</span>
                        <button class="filter-btn btn btn-outline" data-filter="all">All Orders</button>
                        <button class="filter-btn btn btn-outline" data-filter="delivered">Completed</button>
                        <button class="filter-btn btn btn-outline" data-filter="processing">Processing</button>
                        <button class="filter-btn btn btn-outline" data-filter="cancelled">Cancelled</button>
                    </div>
                    <div class="ml-auto w-full sm:w-auto">
                        <select id="order-sort" class="input w-full text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-primary/40 cursor-pointer">
                            <option value="newest">Sort by: Newest</option>
                            <option value="oldest">Sort by: Oldest</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-6 mt-6" id="orders-list">
                    <?php 
                    $statusColors = [
                        'pending' => 'bg-accent/10 text-accent border border-accent/30',
                        'processing' => 'bg-primary/10 text-primary border border-primary/30',
                        'shipped' => 'bg-primary/10 text-primary border border-primary/30',
                        'delivered' => 'bg-accent/10 text-accent border border-accent/30',
                        'cancelled' => 'bg-error/10 text-error border border-error'
                    ];
                    ?>
                    <?php foreach ($orders as $order): 
                        $statusKey = strtolower($order['status']);
                        $statusColor = $statusColors[$statusKey] ?? 'bg-neutral-100 text-neutral-800';
                        $invoice = $order['invoice'] ?? ('NTX' . str_pad($order['id'], 6, '0', STR_PAD_LEFT));
                        $itemsCount = $order['items_count'] ?? 0;
                    ?>
                        <div class="bg-white rounded-xl border border-neutral-300 overflow-hidden p-6 order-card"
                             data-status="<?= $statusKey ?>"
                             data-invoice="<?= strtolower($invoice) ?>"
                             data-total="<?= strtolower(number_format($order['total_amount'], 2)) ?>"
                             data-date="<?= strtotime($order['created_at']) ?>">
                            <div class="flex flex-wrap justify-between gap-6">
                                <div class="max-w-96">
                                    <div class="flex items-center gap-4">
                                        <span class="text-[15px] font-semibold text-slate-600">Order <?= htmlspecialchars($invoice) ?></span>
                                        <span class="px-3 py-1.5 text-xs font-medium rounded-md <?= $statusColor ?>"><?= ucfirst($order['status']) ?></span>
                                    </div>
                                    <p class="text-slate-600 text-sm mt-3">Placed on <?= date('M j, Y \\a\\t g:i A', strtotime($order['created_at'])) ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-semibold text-slate-900">रु<?= number_format($order['total_amount'], 2) ?></p>
                                    <p class="text-slate-600 text-sm mt-2"><?= $itemsCount ?> item<?= $itemsCount === 1 ? '' : 's' ?></p>
                                </div>
                            </div>

                            <?php if (!empty($order['items_preview'])): ?>
                                <hr class="border-neutral-300 my-6" />
                                <div class="flex flex-wrap items-center gap-8">
                                    <?php foreach ($order['items_preview'] as $item): 
                                        $image = $item['image_url'] ?? \App\Core\View::asset('images/products/default.jpg');
                                        ?>
                                        <div class="flex items-center gap-4">
                                            <div class="w-16 h-16 bg-white p-1 rounded-md overflow-hidden border border-primary/20">
                                                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>" class="w-full h-full object-contain"
                                                     onerror="this.src='<?= ASSETS_URL ?>/images/products/default.jpg'">
                                            </div>
                                            <div>
                                                <p class="text-[15px] font-medium text-slate-900"><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></p>
                                                <p class="text-xs text-slate-600 mt-1">Qty: <?= $item['quantity'] ?? 1 ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-8 flex flex-wrap gap-4">
                                <a href="<?= \App\Core\View::url('orders/view/' . $order['id']) ?>" class="btn btn-outline flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 512 512">
                                        <path d="M508.7 246c-4.6-6.3-113.6-153.2-252.7-153.2S7.8 239.8 3.2 246a16.9 16.9 0 0 0 0 19.9c4.6 6.3 113.6 153.2 252.7 153.2s248.2-146.9 252.7-153.2a16.9 16.9 0 0 0 0-19.9zM256 385.4c-102.5 0-191.3-97.5-217.6-129.4 26.3-31.9 114.9-129.4 217.6-129.4 102.5 0 191.3 97.5 217.6 129.4-26.3 31.9-115 129.4-217.6 129.4z"/>
                                        <path d="M256 154.7c-55.8 0-101.3 45.4-101.3 101.3s45.5 101.3 101.3 101.3 101.3-45.4 101.3-101.3S311.8 154.7 256 154.7zm0 168.8c-37.2 0-67.5-30.3-67.5-67.5s30.3-67.5 67.5-67.5 67.5 30.3 67.5 67.5-30.3 67.5-67.5 67.5z"/>
                                    </svg>
                                    View Details
                                </a>

                                <a href="<?= \App\Core\View::url('orders/reorder/' . $order['id']) ?>" 
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary/10 text-primary border border-primary/20 font-medium hover:bg-primary/20 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path>
                                        <path d="M21 3v5h-5"></path>
                                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path>
                                        <path d="M3 21v-5h5"></path>
                                    </svg>
                                    Reorder
                                </a>

                                <a href="<?= \App\Core\View::url('receipt/' . $order['id']) ?>" class="btn btn-outline flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 512 512">
                                        <path d="m433.8 106.3-96.4-91.2C327.1 5.3 313.7 0 299.6 0H116C85.7 0 61 24.7 61 55v402c0 30.3 24.7 55 55 55h280c30.3 0 55-24.7 55-55V146.2c0-15.1-6.3-29.6-17.2-39.9zM404.7 120H330c-2.8 0-5-2.2-5-5V44.6z"/>
                                        <path d="M363 200H143c-8.3 0-15 6.7-15 15s6.7 15 15 15h220c8.3 0 15-6.7 15-15s-6.7-15-15-15zm0 80H143c-8.3 0-15 6.7-15 15s6.7 15 15 15h220c8.3 0 15-6.7 15-15s-6.7-15-15-15zm-147.3 80H143c-8.3 0-15 6.7-15 15s6.7 15 15 15h72.7c8.3 0 15-6.7 15-15s-6.7-15-15-15z"/>
                                    </svg>
                                    Invoice
                                </a>

                                <?php 
                                $cancellableStatuses = ['pending', 'confirmed', 'processing', 'unpaid'];
                                if (in_array($order['status'], $cancellableStatuses)): ?>
                                    <button onclick="openCancelDrawer(<?= $order['id'] ?>)" class="btn btn-outline text-error border-error flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cancel Order Bottom Drawer -->
<div id="cancelDrawer" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeCancelDrawer()"></div>
    <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl transform transition-transform duration-300 ease-out translate-y-full" id="cancelDrawerContent">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-foreground">Cancel Order</h3>
                <button onclick="closeCancelDrawer()" class="text-neutral-400 hover:text-neutral-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="cancelOrderForm" onsubmit="submitCancelOrder(event)">
                <input type="hidden" id="cancelOrderId" name="order_id">
                <div class="mb-4">
                    <label for="cancelReason" class="block text-sm font-medium text-foreground mb-2">
                        Reason for Cancellation <span class="text-error">*</span>
                    </label>
                    <textarea id="cancelReason" 
                              name="reason" 
                              rows="4" 
                              required
                              class="input w-full rounded-xl resize-none"
                              placeholder="Please provide a reason for cancelling this order..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" 
                            onclick="closeCancelDrawer()"
                            class="btn btn-outline flex-1 justify-center">
                        Close
                    </button>
                    <button type="submit" 
                            class="btn btn-outline flex-1 justify-center text-error border-error">
                        Submit Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentCancelOrderId = null;

function openCancelDrawer(orderId) {
    currentCancelOrderId = orderId;
    document.getElementById('cancelOrderId').value = orderId;
    document.getElementById('cancelReason').value = '';
    document.getElementById('cancelDrawer').classList.remove('hidden');
    const drawer = document.getElementById('cancelDrawerContent');
    drawer.classList.remove('translate-y-full');
    document.body.style.overflow = 'hidden';
}

function closeCancelDrawer() {
    document.getElementById('cancelDrawer').classList.add('hidden');
    document.getElementById('cancelDrawerContent').classList.add('translate-y-full');
    document.body.style.overflow = '';
    currentCancelOrderId = null;
}

function submitCancelOrder(event) {
    event.preventDefault();
    
    const orderId = document.getElementById('cancelOrderId').value;
    const reason = document.getElementById('cancelReason').value.trim();
    
    if (!reason) {
        alert('Please provide a reason for cancellation');
        return;
    }
    
    const formData = new FormData();
    formData.append('reason', reason);
    
    fetch('<?= \App\Core\View::url('orders/cancel/') ?>' + orderId, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Order cancellation request submitted successfully');
            closeCancelDrawer();
            location.reload();
        } else {
            alert(data.message || 'Failed to cancel order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('#order-filters .filter-btn');
    const searchInput = document.getElementById('order-search');
    const sortSelect = document.getElementById('order-sort');
    const listContainer = document.getElementById('orders-list');

    function applyFilters() {
        const activeFilter = document.querySelector('#order-filters .filter-btn.active')?.dataset.filter || 'all';
        const term = (searchInput.value || '').toLowerCase();

        document.querySelectorAll('.order-card').forEach(card => {
            const matchesStatus = activeFilter === 'all' || card.dataset.status === activeFilter;
            const matchesSearch = !term || card.dataset.invoice.includes(term);
            card.classList.toggle('hidden', !(matchesStatus && matchesSearch));
        });
    }

    function setActiveButton(activeBtn) {
        filterButtons.forEach(btn => {
            btn.classList.add('btn-outline');
            btn.classList.remove('active');
        });
        activeBtn.classList.remove('btn-outline');
        activeBtn.classList.add('active');
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            setActiveButton(btn);
            applyFilters();
        });
    });

    if (filterButtons.length) {
        setActiveButton(filterButtons[0]);
    }

    searchInput.addEventListener('input', applyFilters);

    sortSelect.addEventListener('change', () => {
        const cards = Array.from(document.querySelectorAll('.order-card'));
        const sorted = cards.sort((a, b) => {
            const dateA = parseInt(a.dataset.date, 10);
            const dateB = parseInt(b.dataset.date, 10);
            return sortSelect.value === 'oldest' ? dateA - dateB : dateB - dateA;
        });
        sorted.forEach(card => listContainer.appendChild(card));
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>