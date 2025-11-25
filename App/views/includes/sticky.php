
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }



        .demo-content {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
            padding: 60px 20px;
        }

        .demo-content h1 {
            color: #1a1a1a;
            margin-bottom: 16px;
            font-size: 2rem;
            font-weight: 700;
        }

        .demo-content p {
            color: #666;
            font-size: 1rem;
        }

        /* Horizontal Floating Elements - Ultra Fast Performance */
        .floating-trigger {
            position: fixed;
            bottom: 24px;
            left: 24px;
            width: 48px;
            height: 48px;
            background: #0A3167;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(10, 49, 103, 0.4);
        }

        .floating-trigger:hover {
            background: #082850;
        }

        .floating-trigger.active {
            background: #C5A572;
            transform: rotate(45deg);
        }

        .trigger-icon {
            width: 20px;
            height: 20px;
            fill: white;
        }

        .floating-bar {
            position: fixed;
            bottom: 24px;
            left: 84px;
            background: rgba(10, 49, 103, 0.95);
            backdrop-filter: blur(8px);
            border-radius: 12px;
            padding: 8px;
            display: flex;
            gap: 4px;
            transform: translateX(-12px) scale(0.95);
            opacity: 0;
            visibility: hidden;
            z-index: 999;
            border: 1px solid rgba(197, 165, 114, 0.2);
        }

        .floating-bar.active {
            transform: translateX(0) scale(1);
            opacity: 1;
            visibility: visible;
        }

        .bar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            min-width: 60px;
            color: #fff;
            position: relative;
        }

        .bar-item:hover {
            background: rgba(197, 165, 114, 0.2);
        }

        .bar-item:active {
            background: rgba(197, 165, 114, 0.3);
        }

        .item-icon {
            width: 18px;
            height: 18px;
            margin-bottom: 4px;
            opacity: 0.9;
        }

        .item-text {
            font-weight: 500;
            font-size: 10px;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            text-align: center;
            font-family: 'Fjalla One', sans-serif;
        }

        .badge {
            position: absolute;
            top: 2px;
            right: 6px;
            background: #C5A572;
            color: white;
            font-size: 8px;
            font-weight: 600;
            padding: 1px 4px;
            border-radius: 6px;
            min-width: 12px;
            text-align: center;
            line-height: 1.2;
            font-family: 'Fjalla One', sans-serif;
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            .floating-trigger {
                bottom: 20px;
                left: 20px;
                width: 44px;
                height: 44px;
            }

            .floating-bar {
                bottom: 20px;
                left: 76px;
                padding: 6px;
            }

            .bar-item {
                min-width: 50px;
                padding: 6px 8px;
            }

            .item-icon {
                width: 16px;
                height: 16px;
            }

            .item-text {
                font-size: 9px;
            }

            .badge {
                font-size: 7px;
                top: 1px;
                right: 4px;
            }
        }

        /* Very small screens */
        @media (max-width: 480px) {
            .floating-bar {
                flex-wrap: wrap;
                max-width: calc(100vw - 110px);
            }
            
            .bar-item {
                min-width: 45px;
            }
        }
    </style>

    <!-- Floating Navigation Menu -->
    <div class="floating-trigger" id="trigger">
        <svg class="trigger-icon" viewBox="0 0 24 24">
            <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
        </svg>
    </div>

    <div class="floating-bar" id="bar">
        <div class="bar-item" onclick="go('profile')">
            <svg class="item-icon" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            <span class="item-text">Profile</span>
        </div>

        <div class="bar-item" onclick="go('cart')">
            <svg class="item-icon" fill="currentColor" viewBox="0 0 24 24">
                <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.42 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
            <span class="item-text">Cart</span>
            <span class="badge" id="cart-badge">0</span>
        </div>

        <div class="bar-item" onclick="go('orders')">
            <svg class="item-icon" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 7h-3V6a4 4 0 0 0-8 0v1H5a1 1 0 0 0-1 1v11a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V8a1 1 0 0 0-1-1zM10 6a2 2 0 0 1 4 0v1h-4V6zm8 13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V9h2v1a1 1 0 0 0 2 0V9h4v1a1 1 0 0 0 2 0V9h2v10z"/>
            </svg>
            <span class="item-text">Orders</span>
        </div>

        <div class="bar-item" onclick="go('invite')">
            <svg class="item-icon" fill="currentColor" viewBox="0 0 24 24">
                <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V8c0-.55-.45-1-1-1s-1 .45-1 1v2H2c-.55 0-1 .45-1 1s.45 1 1 1h2v2c0 .55.45 1 1 1s1-.45 1-1v-2h2c.55 0 1-.45 1-1s-.45-1-1-1H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            <span class="item-text">Invite</span>
        </div>

        <div class="bar-item" onclick="go('earn')">
            <svg class="item-icon" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91 2.28.6 4.18 1.58 4.18 3.91 0 1.82-1.33 2.96-3.12 3.16z"/>
            </svg>
            <span class="item-text">Earn</span>
            <span class="badge">₹50</span>
        </div>
    </div>

    <script>
        const t = document.getElementById('trigger');
        const b = document.getElementById('bar');
        let open = false;

        // Function to get cart count from existing cart system
        function getCartCount() {
            if (typeof CartManager !== 'undefined' && CartManager.getCartCount) {
                return CartManager.getCartCount();
            }
            
            const cartItems = getCookie('cart_items');
            if (cartItems) {
                try {
                    const items = JSON.parse(cartItems);
                    return items.reduce((sum, item) => sum + (item.quantity || 0), 0);
                } catch (e) {
                    return 0;
                }
            }
            return 0;
        }

        // Function to update cart count
        function updateCartCount(count) {
            const badge = document.getElementById('cart-badge');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'block' : 'none';
            }
        }

        // Helper function to get cookie value
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        t.onclick = () => {
            open = !open;
            t.classList.toggle('active', open);
            b.classList.toggle('active', open);
        };

        document.onclick = (e) => {
            if (!t.contains(e.target) && !b.contains(e.target) && open) {
                open = false;
                t.classList.remove('active');
                b.classList.remove('active');
            }
        };

        function go(action) {
            switch(action) {
                case 'profile':
                    window.location.href = '<?= URLROOT ?>/user/profile';
                    break;
                case 'cart':
                    window.location.href = '<?= URLROOT ?>/cart';
                    break;
                case 'orders':
                    window.location.href = '<?= URLROOT ?>/user/orders';
                    break;
                case 'invite':
                    window.location.href = '<?= URLROOT ?>/user/invite';
                    break;
                case 'earn':
                    window.location.href = '<?= URLROOT ?>/user/earn';
                    break;
                default:
                    console.log('Unknown action:', action);
            }
            
            open = false;
            t.classList.remove('active');
            b.classList.remove('active');
        }

        // Initialize cart count when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount(getCartCount());
            
            document.addEventListener('cartUpdated', function(e) {
                updateCartCount(e.detail.count);
            });
            
            let lastCartCount = getCartCount();
            setInterval(() => {
                const currentCount = getCartCount();
                if (currentCount !== lastCartCount) {
                    lastCartCount = currentCount;
                    updateCartCount(currentCount);
                }
            }, 1000);
        });
    </script>
