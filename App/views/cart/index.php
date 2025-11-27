<?php 
ob_start(); 
use App\Helpers\CurrencyHelper;
?>

<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
        <!-- Cart Container -->
        <div id="cart-container">
            <?php if (empty($cartItems)): ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center mt-8 max-w-md mx-auto">
                    <svg class="h-16 w-16 mx-auto text-gray-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h2 class="h4-semibold text-primary mb-2">Your cart is empty</h2>
                    <p class="body1-regular text-gray-600 mb-6">Explore our products and start shopping today.</p>
                    <a href="<?= \App\Core\View::url('products') ?>" class="  bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg text-sm font-medium inline-block transition-colors">
                        Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-3xl shadow-sm">
                    <div class="flex max-md:flex-col gap-12 max-lg:gap-6 h-full">
                        <div class="flex-1 max-md:-order-1">
                            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                                <div class="bg-gray-100 rounded-2xl p-4 sm:p-6 space-y-6">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex flex-wrap items-center justify-between gap-4">
                                            <h3 class="text-lg font-semibold text-slate-900">Your Cart</h3>
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center">
                                                    <input type="checkbox" id="selectAll" class="w-5 h-5 text-primary bg-white border-2 border-gray-300 rounded focus:ring-2 focus:ring-primary focus:ring-offset-0">
                                                    <label for="selectAll" class="ml-3 text-gray-800 text-sm font-semibold">Select all</label>
                                                </div>
                                                <span class="text-gray-600 text-sm font-medium" id="selectedCount">0 selected</span>
                                            </div>
                                        </div>
                                        <hr class="border-gray-300 hidden lg:block">
                                    </div>

                                    <div id="cart-items-container" class="sm:space-y-6 space-y-8">
                                        <?php foreach ($cartItems as $item): ?>
                                            <div class="cart-item grid sm:grid-cols-3 items-center gap-4 max-sm:gap-3 bg-white rounded-xl border border-transparent hover:border-primary/30 transition-all p-4 max-sm:p-3" data-product-id="<?= $item['product']['id'] ?>">
                                                <div class="sm:col-span-2 flex sm:items-center max-sm:flex-col gap-4 max-sm:gap-3 w-full">
                                                    <div class="flex items-start gap-3 w-full">
                                                        <input type="checkbox" class="item-checkbox w-5 h-5 text-primary bg-white border-2 border-gray-300 rounded focus:ring-2 focus:ring-primary focus:ring-offset-0 mt-1" checked>
                                                        <div class="w-16 h-16 sm:w-24 sm:h-24 shrink-0 bg-white p-2 rounded-md border">
                                                            <?php $imageUrl = htmlspecialchars($item['product']['image_url'] ?? \App\Core\View::asset('images/products/default.jpg')); ?>
                                                            <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($item['product']['product_name']) ?>" class="w-full h-full object-contain" onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'; this.onerror=null;">
                                                        </div>
                                                        <div class="space-y-2 flex-1 min-w-0">
                                                            <h4 class="text-[15px] font-semibold text-slate-900 truncate">
                                                                <?= htmlspecialchars($item['product']['product_name']) ?>
                                                            </h4>
                                                            <button type="button" class="text-xs font-medium text-error hover:text-error-dark" onclick="removeCartItem(<?= $item['id'] ?>, <?= $item['product']['id'] ?>)">Remove</button>
                                                            <div class="flex flex-wrap items-center gap-3 mt-2">
                                                                <div>
                                                                    <button type="button" class="flex items-center px-2.5 py-1.5 border border-gray-300 text-slate-900 text-xs font-medium bg-white rounded-md">
                                                                        <?= htmlspecialchars($item['product']['category'] ?? 'General') ?>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 fill-gray-500 inline ml-2.5" viewBox="0 0 24 24">
                                                                            <path fill-rule="evenodd" d="M11.99997 18.1669a2.38 2.38 0 0 1-1.68266-.69733l-9.52-9.52a2.38 2.38 0 1 1 3.36532-3.36532l7.83734 7.83734 7.83734-7.83734a2.38 2.38 0 1 1 3.36532 3.36532l-9.52 9.52a2.38 2.38 0 0 1-1.68266.69734z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                                <div class="flex items-center px-2.5 py-1.5 border border-gray-300 text-slate-900 text-xs rounded-md bg-white">
                                                                    <button type="button" class="p-1 rounded-md hover:bg-gray-100 transition-colors" onclick="updateCartItem(<?= $item['product']['id'] ?>, 'decrease')" aria-label="Decrease quantity">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current" viewBox="0 0 124 124">
                                                                            <path d="M112 50H12C5.4 50 0 55.4 0 62s5.4 12 12 12h100c6.6 0 12-5.4 12-12s-5.4-12-12-12z"></path>
                                                                        </svg>
                                                                    </button>
                                                                    <span class="mx-3 quantity-display-main text-base font-semibold" data-product-id="<?= $item['product']['id'] ?>"><?= $item['quantity'] ?></span>
                                                                    <button type="button" class="p-1 rounded-md hover:bg-gray-100 transition-colors" onclick="updateCartItem(<?= $item['product']['id'] ?>, 'increase')" aria-label="Increase quantity">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current" viewBox="0 0 42 42">
                                                                            <path d="M37.059 16H26V4.941C26 2.224 23.718 0 21 0s-5 2.224-5 4.941V16H4.941C2.224 16 0 18.282 0 21s2.224 5 4.941 5H16v11.059C16 39.776 18.282 42 21 42s5-2.224 5-4.941V26h11.059C39.776 26 42 23.718 42 21s-2.224-5-4.941-5z"></path>
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="sm:ml-auto flex flex-col items-start sm:items-end gap-2">
                                                    <h4 class="text-[15px] font-semibold text-slate-900">रु<?= number_format($item['product']['sale_price'] ?? $item['product']['price'], 2) ?></h4>
                                                    <p class="text-xs text-slate-500">Inclusive of taxes</p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php $hasCoupon = !empty($appliedCoupon) && $couponDiscount > 0; ?>
                        <aside id="cart-summary"
                               class="bg-gray-100 md:h-screen md:sticky md:top-6 md:min-w-[360px] rounded-2xl p-6 border border-gray-200"
                               data-tax-rate="<?= $taxRate ?>"
                               data-original-subtotal="<?= $originalTotals['subtotal'] ?>"
                               data-original-tax="<?= $originalTotals['tax'] ?>"
                               data-original-final="<?= $originalTotals['final'] ?>"
                               data-applied-code="<?= !empty($appliedCoupon['code']) ? htmlspecialchars($appliedCoupon['code']) : '' ?>">
                            <div class="relative h-full">
                                <div class="space-y-6 md:overflow-auto md:h-[calc(100vh-3rem)]">
                                    <div class="space-y-4">
                                        <?php foreach ($cartItems as $item): ?>
                                            <div class="flex items-start gap-4">
                                                <div class="w-16 h-16 flex p-2 shrink-0 bg-white rounded-md">
                                                    <?php $imageUrl = htmlspecialchars($item['product']['image_url'] ?? \App\Core\View::asset('images/products/default.jpg')); ?>
                                                    <img src="<?= $imageUrl ?>" class="w-full object-contain" alt="<?= htmlspecialchars($item['product']['product_name']) ?>" onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'; this.onerror=null;">
                                                </div>
                                                <div class="w-full">
                                                    <h3 class="text-sm text-slate-900 font-semibold"><?= htmlspecialchars($item['product']['product_name']) ?></h3>
                                                    <ul class="text-xs text-slate-900 space-y-2 mt-3">
                                                        <li class="flex flex-wrap gap-4">Quantity <span class="ml-auto"><?= $item['quantity'] ?></span></li>
                                                        <li class="flex flex-wrap gap-4">Total Price <span class="ml-auto font-semibold">रु<?= number_format($item['subtotal'] ?? ($item['product']['sale_price'] ?? $item['product']['price']) * $item['quantity'], 2) ?></span></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <hr class="border-gray-300">

                                    <div>
                                        <ul class="text-slate-500 font-medium space-y-4 text-sm">
                                            <li class="flex flex-wrap gap-4">Subtotal <span class="ml-auto text-slate-900 font-semibold">रु<span id="subtotal" data-original="<?= $originalTotals['subtotal'] ?>" data-current="<?= $total ?>"><?= number_format($total, 2) ?></span></span></li>
                                            <li id="cart-discount-row" class="flex flex-wrap gap-4 text-sm <?= $hasCoupon ? '' : 'hidden' ?>">Discount <span class="ml-auto font-semibold text-success">-रु<span id="cart-discount-amount"><?= number_format($couponDiscount, 2) ?></span></span></li>
                                            <li class="flex flex-wrap gap-4">Tax (<?= number_format($taxRate * 100, 0) ?>%) <span class="ml-auto text-slate-900 font-semibold">रु<span id="tax" data-original="<?= $originalTotals['tax'] ?>" data-current="<?= $tax ?>"><?= number_format($tax, 2) ?></span></span></li>
                                            <li class="flex flex-wrap gap-4 text-[15px] font-semibold text-slate-900">Total <span class="ml-auto">रु<span id="final-total" data-original="<?= $originalTotals['final'] ?>" data-current="<?= $finalTotal ?>"><?= number_format($finalTotal, 2) ?></span></span></li>
                                        </ul>

                                    <div class="mt-6 space-y-3">
                                        <div id="cart-applied-coupon" class="<?= $hasCoupon ? '' : 'hidden' ?> flex items-center justify-between p-3 bg-primary/5 border border-primary/20 rounded-lg">
                                            <div>
                                                <p class="text-sm font-semibold text-primary" id="cart-applied-code"><?= $hasCoupon ? htmlspecialchars($appliedCoupon['code']) : '' ?></p>
                                                <p class="text-xs text-primary/70" id="cart-applied-discount"><?= $hasCoupon ? 'Saved रु' . number_format($couponDiscount, 2) : '' ?></p>
                                            </div>
                                            <button type="button" id="cart-remove-coupon-btn" class="text-error hover:text-error-dark" aria-label="Remove coupon">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <div id="cart-coupon-form" class="space-y-2 <?= $hasCoupon ? 'hidden' : '' ?>">
                                            <p class="text-slate-900 text-sm font-medium">Do you have a promo code?</p>
                                            <div class="flex border border-primary overflow-hidden rounded-full">
                                                <input type="text" id="cart-coupon-code" placeholder="Promo code"
                                                       class="flex-1 outline-0 bg-white text-slate-600 text-sm px-4 py-2.5 uppercase" autocomplete="off">
                                                <button type="button" id="cart-apply-coupon-btn"
                                                        class="btn btn-outline rounded-none rounded-r-full">
                                                    Apply
                                                </button>
                                            </div>
                                            <p id="cart-coupon-message" class="hidden text-sm"></p>
                                        </div>

                                        <div class="space-y-3 hidden lg:block">
                                            <a href="<?= \App\Core\View::url('checkout') ?>" class="btn w-full justify-center">
                                                Proceed to Checkout
                                            </a>
                                            <a href="<?= \App\Core\View::url('products') ?>" class="btn btn-outline w-full justify-center">
                                                Continue Shopping
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>

                <!-- Sticky Checkout + Info -->
                <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 shadow-lg z-50 lg:hidden">
                    <div class="flex items-center gap-3">
                        <button type="button" id="orderStepsBtn" class="btn btn-outline !px-4">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <span class="text-sm font-medium">Tutorial</span>
                        </button>
                        <a href="<?= \App\Core\View::url('checkout') ?>" class="btn flex-1 justify-center">
                            Proceed To Checkout
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Cart Modal -->
    <div id="cartModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="inline-block align-bottom bg-white rounded-t-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Modal Header -->
                <div class="bg-white px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">All Cart Items</h3>
                    <button onclick="closeCartModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div class="bg-white px-6 py-4 max-h-96 overflow-y-auto">
                    <div class="space-y-4">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-modal-item flex items-center space-x-4 p-3 bg-gray-50 rounded-lg" data-product-id="<?= $item['product']['id'] ?>">
                                <div class="w-12 h-12 rounded-lg overflow-hidden flex-shrink-0">
                                    <?php 
                                        $imageUrl = htmlspecialchars($item['product']['image_url'] ?? \App\Core\View::asset('images/products/default.jpg'));
                                    ?>
                                    <img src="<?= $imageUrl ?>" 
                                         onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'; this.onerror=null;" 
                                         alt="<?= htmlspecialchars($item['product']['product_name']) ?>" 
                                         class="w-full h-full object-cover">
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-gray-900 truncate">
                                        <?= htmlspecialchars($item['product']['product_name']) ?>
                                    </h4>
                                    <p class="text-primary font-semibold text-sm">
                                        रु<?= number_format($item['product']['sale_price'] ?? $item['product']['price'], 2) ?>
                                    </p>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <div class="flex items-center bg-gray-100 rounded-lg">
                                        <button type="button" 
                                                onclick="updateCartItem(<?= $item['product']['id'] ?>, 'decrease')" 
                                                class="px-2 py-1 text-white bg-primary hover:bg-primary-dark rounded-l-lg" 
                                                aria-label="Decrease quantity">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                        </button>
                                        <span class="px-3 py-1 text-xs font-medium bg-white border-t border-b border-gray-200 quantity-display-main" data-product-id="<?= $item['product']['id'] ?>"><?= $item['quantity'] ?></span>
                                        <button type="button" 
                                                onclick="updateCartItem(<?= $item['product']['id'] ?>, 'increase')" 
                                                class="px-2 py-1 text-white bg-primary hover:bg-primary-dark rounded-r-lg" 
                                                aria-label="Increase quantity">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <button type="button" 
                                            onclick="removeCartItem(<?= $item['id'] ?>, <?= $item['product']['id'] ?>)" 
                                            class="p-1 text-error hover:text-error-dark hover:bg-error/10 rounded transition-colors" 
                                            aria-label="Remove item">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-4 flex space-x-3">
                    <button onclick="closeCartModal()" class="btn btn-outline flex-1 justify-center">
                        Close
                    </button>
                    <a href="<?= \App\Core\View::url('cart') ?>" class="btn flex-1 justify-center">
                        View Full Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Steps Drawer -->
<div id="orderStepsOverlay" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden"></div>
<div id="orderStepsDrawer" class="fixed bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-xl z-50 transform translate-y-full transition-transform duration-300">
  <div class="bg-white px-6 py-4 border-b border-gray-200 flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-900">Order in 6 clean steps</h3>
    <button id="closeStepsDrawer" class="text-gray-400 hover:text-gray-600" aria-label="Close">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>
  <div class="bg-white px-6 py-4">
    <ol class="space-y-3 text-sm text-gray-800">
      <li class="flex items-start gap-3"><span class="w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs">1</span> Add products to cart</li>
      <li class="flex items-start gap-3"><span class="w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs">2</span> Review quantities and selected items</li>
      <li class="flex items-start gap-3"><span class="w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs">3</span> Proceed to checkout</li>
      <li class="flex items-start gap-3"><span class="w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs">4</span> Enter delivery details</li>
      <li class="flex items-start gap-3"><span class="w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs">5</span> Choose payment method</li>
      <li class="flex items-start gap-3"><span class="w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs">6</span> Confirm order and receive confirmation</li>
    </ol>
  </div>
  <div class="bg-gray-50 px-6 py-4 flex space-x-3">
    <button id="closeStepsDrawerFooter" class="btn btn-outline flex-1 justify-center">Close</button>
    <a href="<?= \App\Core\View::url('checkout') ?>" class="btn flex-1 justify-center">Go to Checkout</a>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const openBtn = document.getElementById('orderStepsBtn');
  const overlay = document.getElementById('orderStepsOverlay');
  const drawer = document.getElementById('orderStepsDrawer');
  const closeBtn = document.getElementById('closeStepsDrawer');
  const closeBtn2 = document.getElementById('closeStepsDrawerFooter');

  function openDrawer(){
    overlay && overlay.classList.remove('hidden');
    drawer && drawer.classList.remove('translate-y-full');
    document.body.style.overflow = 'hidden';
  }
  function closeDrawer(){
    overlay && overlay.classList.add('hidden');
    drawer && drawer.classList.add('translate-y-full');
    document.body.style.overflow = '';
  }

  openBtn && openBtn.addEventListener('click', openDrawer);
  closeBtn && closeBtn.addEventListener('click', closeDrawer);
  closeBtn2 && closeBtn2.addEventListener('click', closeDrawer);
  overlay && overlay.addEventListener('click', closeDrawer);
});
</script>

<style>
/* Drawer fallback if Tailwind classes are unavailable */
#orderStepsDrawer { transition: transform 0.3s ease; }
#orderStepsDrawer.translate-y-full { transform: translateY(100%); }
</style>

<!-- Essential Styles -->
<style>
    .cart-item {
        border-radius: 12px;
        margin-bottom: 0;
        overflow: hidden;
        word-wrap: break-word;
    }
    
    .cart-item h3 a {
        color: var(--primary-color, #7C3AED);
        text-decoration: none;
    }
    
    /* Enhanced checkbox styling */
    input[type="checkbox"] {
        appearance: none;
        background-color: white;
        border: 2px solid #d1d5db;
        border-radius: 6px;
        cursor: pointer;
        position: relative;
    }
    
    input[type="checkbox"]:checked {
        background-color: var(--primary-color, #7C3AED);
        border-color: var(--primary-color, #7C3AED);
    }
    
    input[type="checkbox"]:checked::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 12px;
        height: 12px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m4.5 12.75 6 6 9-13.5' /%3E%3C/svg%3E");
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
    }
    
    input[type="checkbox"]:indeterminate {
        background-color: #6b7280;
        border-color: #6b7280;
    }
    
    input[type="checkbox"]:indeterminate::after {
        content: '−';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 14px;
        font-weight: bold;
    }
    
    /* Cart item selection state */
    .cart-item.selected {
        background-color: rgba(124, 58, 237, 0.05);
        border-color: var(--primary-color, #7C3AED);
    }
    
    /* Mobile optimizations */
    @media (max-width: 640px) {
        .container {
            padding-left: 16px !important;
            padding-right: 16px !important;
        }
        
        .cart-item {
            padding: 12px !important;
        }
        
        .cart-item img {
            width: 48px !important;
            height: 48px !important;
        }
    }
</style>

<script>
// Show alert using global component if available
function showMessage(message, type = 'error') {
    if (window.AppAlert) {
        window.AppAlert.show(message, type);
        return;
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `fixed bottom-20 left-4 z-50 px-4 py-2 rounded-lg text-white font-medium ${type === 'success' ? 'bg-success' : 'bg-error'}`;
    messageDiv.textContent = message;
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.remove();
    }, 3000);
}

const summaryWrapper = document.getElementById('cart-summary');
const subtotalEl = document.getElementById('subtotal');
const taxEl = document.getElementById('tax');
const finalEl = document.getElementById('final-total');
const discountRow = document.getElementById('cart-discount-row');
const discountAmountEl = document.getElementById('cart-discount-amount');
const couponForm = document.getElementById('cart-coupon-form');
const couponMessageEl = document.getElementById('cart-coupon-message');
const appliedCouponBox = document.getElementById('cart-applied-coupon');
const appliedCodeEl = document.getElementById('cart-applied-code');
const appliedDiscountEl = document.getElementById('cart-applied-discount');
const couponInput = document.getElementById('cart-coupon-code');
const applyCouponBtn = document.getElementById('cart-apply-coupon-btn');
const removeCouponBtn = document.getElementById('cart-remove-coupon-btn');
let appliedCouponCode = summaryWrapper ? (summaryWrapper.dataset.appliedCode || '') : '';

function formatCurrency(value) {
    return Number(parseFloat(value || 0)).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updateDisplayedTotals(subtotalValue, taxValue, finalValue) {
    if (!subtotalEl || !taxEl || !finalEl) return;
    subtotalEl.dataset.current = subtotalValue;
    taxEl.dataset.current = taxValue;
    finalEl.dataset.current = finalValue;
    subtotalEl.textContent = formatCurrency(subtotalValue);
    taxEl.textContent = formatCurrency(taxValue);
    finalEl.textContent = formatCurrency(finalValue);
}

function setBaseTotals(subtotalValue, taxValue, finalValue) {
    if (!subtotalEl || !taxEl || !finalEl) return;
    subtotalEl.dataset.original = subtotalValue;
    taxEl.dataset.original = taxValue;
    finalEl.dataset.original = finalValue;
    if (!appliedCouponCode) {
        updateDisplayedTotals(subtotalValue, taxValue, finalValue);
    }
}

function showCartCouponMessage(message, type = 'success') {
    if (!couponMessageEl) return;
    couponMessageEl.textContent = message;
    couponMessageEl.className = `text-sm ${type === 'error' ? 'text-error' : 'text-success'}`;
    couponMessageEl.classList.remove('hidden');
    setTimeout(() => couponMessageEl.classList.add('hidden'), 4000);
}

function refreshTotalsFromResponse(data) {
    if (!data) return;
    setBaseTotals(parseFloat(data.cart_total || subtotalEl?.dataset.original || 0), parseFloat(data.tax || taxEl?.dataset.original || 0), parseFloat(data.final_total || finalEl?.dataset.original || 0));
    if (appliedCouponCode) {
        applyCartCoupon({ code: appliedCouponCode, silent: true });
    }
}

function applyCartCoupon({ code: overrideCode = null, silent = false } = {}) {
    const code = (overrideCode || (couponInput ? couponInput.value : '')).trim().toUpperCase();
    if (!code) {
        showCartCouponMessage('Please enter a coupon code', 'error');
        return;
    }

    if (!overrideCode && applyCouponBtn) {
        applyCouponBtn.disabled = true;
        applyCouponBtn.textContent = 'Applying...';
    }

    fetch('<?= ASSETS_URL ?>/checkout/validateCoupon', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            handleCartCouponApply(data, silent);
            if (!overrideCode && couponInput) {
                couponInput.value = '';
                couponInput.dispatchEvent(new Event('input'));
            }
        } else if (!silent) {
            showCartCouponMessage(data.message || 'Unable to apply coupon', 'error');
        }
    })
    .catch(() => {
        if (!silent) {
            showCartCouponMessage('Failed to apply coupon. Please try again.', 'error');
        }
    })
    .finally(() => {
        if (!overrideCode && applyCouponBtn) {
            applyCouponBtn.disabled = false;
            applyCouponBtn.textContent = 'Apply';
        }
    });
}

function handleCartCouponApply(data, silent = false) {
    if (!data?.coupon) return;
    const discount = parseFloat(data.discount || 0);
    const finalAmount = parseFloat(data.final_amount || 0);
    const baseSubtotal = parseFloat(subtotalEl.dataset.original || 0);
    const discountedSubtotal = Math.max(0, baseSubtotal - discount);
    const recalculatedTax = Math.max(0, finalAmount - discountedSubtotal);

    updateDisplayedTotals(discountedSubtotal, recalculatedTax, finalAmount);
    if (discountAmountEl) {
        discountAmountEl.textContent = formatCurrency(discount);
    }
    discountRow?.classList.remove('hidden');

    appliedCouponCode = data.coupon.code;
    if (summaryWrapper) {
        summaryWrapper.dataset.appliedCode = appliedCouponCode;
    }
    if (appliedCouponBox) {
        appliedCouponBox.classList.remove('hidden');
    }
    if (couponForm) {
        couponForm.classList.add('hidden');
    }
    if (appliedCodeEl) {
        appliedCodeEl.textContent = data.coupon.code;
    }
    if (appliedDiscountEl) {
        appliedDiscountEl.textContent = 'Saved रु' + formatCurrency(discount);
    }
    if (!silent) {
        showCartCouponMessage('Coupon applied successfully!', 'success');
    }
}

function removeCartCoupon() {
    if (!removeCouponBtn) return;
    removeCouponBtn.disabled = true;

    fetch('<?= ASSETS_URL ?>/checkout/removeCoupon', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            appliedCouponCode = '';
            if (summaryWrapper) {
                summaryWrapper.dataset.appliedCode = '';
            }
            discountRow?.classList.add('hidden');
            appliedCouponBox?.classList.add('hidden');
            if (couponForm) {
                couponForm.classList.remove('hidden');
            }
            if (appliedDiscountEl) {
                appliedDiscountEl.textContent = '';
            }
            updateDisplayedTotals(parseFloat(subtotalEl.dataset.original || 0), parseFloat(taxEl.dataset.original || 0), parseFloat(finalEl.dataset.original || 0));
            showCartCouponMessage('Coupon removed successfully!', 'success');
        } else {
            showCartCouponMessage(data.message || 'Failed to remove coupon', 'error');
        }
    })
    .catch(() => {
        showCartCouponMessage('Failed to remove coupon. Please try again.', 'error');
    })
    .finally(() => {
        removeCouponBtn.disabled = false;
    });
}

if (applyCouponBtn && couponInput) {
    applyCouponBtn.addEventListener('click', () => applyCartCoupon());
}

if (removeCouponBtn) {
    removeCouponBtn.addEventListener('click', removeCartCoupon);
}

if (couponInput) {
    couponInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
        if (applyCouponBtn) {
            applyCouponBtn.disabled = this.value.trim().length === 0;
        }
    });
    couponInput.dispatchEvent(new Event('input'));
}

if (appliedCouponCode) {
    applyCartCoupon({ code: appliedCouponCode, silent: true });
}

// Update cart item quantity
function updateCartItem(productId, action) {
    const quantityDisplay = document.querySelector(`[data-product-id="${productId}"].quantity-display-main`);
    if (quantityDisplay) {
        quantityDisplay.textContent = '...';
    }
    
    fetch('<?= ASSETS_URL ?>/cart/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `product_id=${productId}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(element => {
                element.textContent = data.cart_count;
            });
            
            const quantityDisplays = document.querySelectorAll(`[data-product-id="${productId}"].quantity-display, [data-product-id="${productId}"].quantity-display-main`);
            quantityDisplays.forEach(display => {
                display.textContent = data.item_quantity;
            });
            
            document.cookie = `cart_count=${data.cart_count}; path=/; max-age=86400`;
            document.cookie = `cart_total=${data.cart_total}; path=/; max-age=86400`;
            
            if (data.item_quantity === 0) {
                removeCartItemFromDOM(productId);
            }

            refreshTotalsFromResponse(data);
        } else {
            if (quantityDisplay) {
                quantityDisplay.textContent = quantityDisplay.getAttribute('data-original-quantity') || '1';
            }
            showMessage('Failed to update cart: ' + data.message, 'error');
        }
    })
    .catch(() => {
        if (quantityDisplay) {
            quantityDisplay.textContent = quantityDisplay.getAttribute('data-original-quantity') || '1';
        }
        showMessage('Error updating cart. Please try again.', 'error');
    });
}

// Remove cart item (no confirmation)
function removeCartItem(cartItemId, productId) {
    fetch('<?= ASSETS_URL ?>/cart/remove', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `cart_item_id=${cartItemId}&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(element => {
                element.textContent = data.cart_count;
            });
            
            document.cookie = `cart_count=${data.cart_count}; path=/; max-age=86400`;
            document.cookie = `cart_total=${data.cart_total}; path=/; max-age=86400`;
            
            removeCartItemFromDOM(productId);

            refreshTotalsFromResponse(data);
            
            if (data.cart_count === 0) {
                location.reload();
            }
        } else {
            showMessage('Failed to remove item: ' + data.message, 'error');
        }
    })
    .catch(() => {
        showMessage('Error removing item. Please try again.', 'error');
    });
}

// Remove cart item from DOM
function removeCartItemFromDOM(productId) {
    const selectors = [
        `.cart-item[data-product-id="${productId}"]`,
        `.cart-modal-item[data-product-id="${productId}"]`
    ];
    
    selectors.forEach(selector => {
        const element = document.querySelector(selector);
        if (element) {
            element.remove();
        }
    });
}

// Optimized select all functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const selectedCountElement = document.getElementById('selectedCount');
    
    // Update selected count display
    function updateSelectedCount() {
        const checkedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
        const totalCount = itemCheckboxes.length;
        
        if (selectedCountElement) {
            selectedCountElement.textContent = `${checkedCount} selected`;
        }
        
        // Update select all checkbox state
        if (selectAllCheckbox) {
            if (checkedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === totalCount) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }
    }
    
    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
    }
    
    // Individual checkbox change
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const cartItem = this.closest('.cart-item');
            if (this.checked) {
                cartItem.classList.add('selected');
            } else {
                cartItem.classList.remove('selected');
            }
            updateSelectedCount();
        });
    });
    
    // Initialize count and selected state on page load
    itemCheckboxes.forEach(checkbox => {
        const cartItem = checkbox.closest('.cart-item');
        if (checkbox.checked) {
            cartItem.classList.add('selected');
        }
    });
    updateSelectedCount();
});

// Cart Modal Functions
function openCartModal() {
    document.getElementById('cartModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeCartModal() {
    document.getElementById('cartModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('cartModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCartModal();
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>