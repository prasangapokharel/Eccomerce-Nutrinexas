<!-- Cart Drawer/Overlay -->
<div id="cart-drawer" class="fixed inset-0 z-50 hidden">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeCartDrawer()"></div>
    
    <!-- Drawer Content -->
    <div class="fixed right-0 top-0 h-full w-full max-w-md bg-white shadow-xl transform transition-transform duration-300 ease-in-out translate-x-full" id="cart-drawer-content">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Shopping Cart</h3>
            <button onclick="closeCartDrawer()" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4" id="cart-drawer-items">
            <div class="text-center text-gray-500 py-8">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p>Your cart is empty</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="border-t border-gray-200 p-4 bg-gray-50">
            <!-- Cart Summary -->
            <div class="mb-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span>Subtotal:</span>
                    <span id="cart-drawer-subtotal">रु0</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Tax (12%):</span>
                    <span id="cart-drawer-tax">रु0</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Delivery:</span>
                    <span id="cart-drawer-delivery">रु0</span>
                </div>
                <div class="flex justify-between font-semibold text-lg border-t border-gray-300 pt-2">
                    <span>Total:</span>
                    <span id="cart-drawer-total">रु0</span>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="space-y-2">
                <a href="<?= \App\Core\View::url('cart') ?>" class="block w-full clip  bg-primary text-white text-center py-3 px-4 rounded-lg font-semibold hover:bg-primary-dark transition-colors">
                    View Cart
                </a>
                <a href="<?= \App\Core\View::url('checkout') ?>" class="block w-full clip bg-accent hover:bg-accent-dark border border-accent text-white text-center py-3 px-4 rounded-lg font-semibold transition-colors">
                    Checkout Now
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Cart Drawer Functions
function openCartDrawer() {
    document.getElementById('cart-drawer').classList.remove('hidden');
    document.getElementById('cart-drawer-content').classList.remove('translate-x-full');
    document.body.style.overflow = 'hidden';
    loadCartDrawerContent();
}

function closeCartDrawer() {
    document.getElementById('cart-drawer-content').classList.add('translate-x-full');
    setTimeout(() => {
        document.getElementById('cart-drawer').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }, 300);
}

function loadCartDrawerContent() {
    // Fetch cart data via AJAX
    fetch('<?= ASSETS_URL ?>/cart/count', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.count > 0) {
            // Load cart items
            fetch('<?= ASSETS_URL ?>/cart/drawer', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(cartData => {
                updateCartDrawerContent(cartData);
            })
            .catch(error => {
                console.error('Error loading cart drawer:', error);
            });
        } else {
            // Show empty cart
            document.getElementById('cart-drawer-items').innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p>Your cart is empty</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading cart count:', error);
    });
}

function updateCartDrawerContent(cartData) {
    const itemsContainer = document.getElementById('cart-drawer-items');
    
    if (cartData.cartItems && cartData.cartItems.length > 0) {
        let itemsHTML = '';
        cartData.cartItems.forEach(item => {
            itemsHTML += `
                <div class="flex items-center space-x-3 py-3 border-b border-gray-100">
                    <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100">
                        <img src="${item.product.image_url || '<?= \App\Core\View::asset('images/products/default.jpg') ?>'}" 
                             alt="${item.product.product_name}" 
                             class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-medium text-gray-900 truncate">${item.product.product_name}</h4>
                        <p class="text-sm text-gray-500">Qty: ${item.quantity}</p>
                        <p class="text-sm font-semibold text-blue-600">रु${item.subtotal.toFixed(2)}</p>
                    </div>
                    <button onclick="removeFromCartDrawer(${item.product.id})" class="p-1 text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            `;
        });
        itemsContainer.innerHTML = itemsHTML;
    } else {
        itemsContainer.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p>Your cart is empty</p>
            </div>
        `;
    }
    
    // Update totals
    document.getElementById('cart-drawer-subtotal').textContent = `रु${cartData.total || 0}`;
    document.getElementById('cart-drawer-tax').textContent = `रु${cartData.tax || 0}`;
    document.getElementById('cart-drawer-delivery').textContent = `रु${cartData.delivery || 0}`;
    document.getElementById('cart-drawer-total').textContent = `रु${cartData.finalTotal || 0}`;
}

function removeFromCartDrawer(productId) {
    showConfirmationDialog(
        'Remove Item',
        'Are you sure you want to remove this item from your cart?',
        'Remove',
        'Cancel',
        function() {
            fetch('<?= ASSETS_URL ?>/cart/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadCartDrawerContent();
                    updateCartCount();
                }
            })
            .catch(error => {
                console.error('Error removing item:', error);
            });
        }
    );
}

function showConfirmationDialog(title, message, confirmText, cancelText, onConfirm) {
    // Create modal overlay
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    overlay.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl max-w-sm w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">${title}</h3>
                <p class="text-gray-600 mb-6">${message}</p>
                <div class="flex space-x-3">
                    <button class="cancel-btn flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
                        ${cancelText}
                    </button>
                    <button class="confirm-btn flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        ${confirmText}
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
    
    // Add event listeners
    overlay.querySelector('.cancel-btn').onclick = () => {
        document.body.removeChild(overlay);
        document.body.style.overflow = 'auto';
    };
    
    overlay.querySelector('.confirm-btn').onclick = () => {
        document.body.removeChild(overlay);
        document.body.style.overflow = 'auto';
        onConfirm();
    };
    
    // Close on overlay click
    overlay.onclick = (e) => {
        if (e.target === overlay) {
            document.body.removeChild(overlay);
            document.body.style.overflow = 'auto';
        }
    };
}
</script>
