/**
 * Splash Screen Integration Script
 * Easy way to add splash screen to any page
 */

// Simple integration function
function initSplashScreen(options = {}) {
    const defaultOptions = {
        autoShow: true,
        autoHideDelay: 3000,
        imagePath: '/images/splash/1.png',
        skipButtonText: 'Skip',
        shopNowButtonText: 'Shop Now',
        shopNowUrl: '/products',
        onShow: null,
        onHide: null
    };
    
    const config = { ...defaultOptions, ...options };
    
    // Create splash screen HTML
    const splashHTML = `
        <div id="splash-screen" class="splash-screen" style="display: none;">
            <div class="splash-content">
                <img src="${config.imagePath}" alt="Splash Screen" class="splash-image">
                <button class="splash-skip-btn" onclick="hideSplashScreen()">${config.skipButtonText}</button>
                <button class="splash-shop-now-btn" onclick="shopNowAction()">${config.shopNowButtonText}</button>
            </div>
        </div>
    `;
    
    // Add splash screen to page
    document.body.insertAdjacentHTML('beforeend', splashHTML);
    
    // Add styles
    addSplashStyles();
    
    // Show splash screen if autoShow is enabled
    if (config.autoShow) {
        setTimeout(() => {
            showSplashScreen();
            if (config.onShow) config.onShow();
        }, 100);
        
        // Auto-hide after delay
        setTimeout(() => {
            hideSplashScreen();
            if (config.onHide) config.onHide();
        }, config.autoHideDelay);
    }
    
    // Store config for later use
    window.splashConfig = config;
}

// Show splash screen
function showSplashScreen() {
    const splash = document.getElementById('splash-screen');
    if (splash) {
        splash.style.display = 'flex';
        splash.classList.add('splash-fade-in');
    }
}

// Hide splash screen
function hideSplashScreen() {
    const splash = document.getElementById('splash-screen');
    if (splash) {
        splash.classList.add('splash-fade-out');
        setTimeout(() => {
            if (splash.parentNode) {
                splash.parentNode.removeChild(splash);
            }
        }, 500);
    }
}

// Shop now action
function shopNowAction() {
    const config = window.splashConfig || {};
    hideSplashScreen();
    if (config.shopNowUrl) {
        window.location.href = config.shopNowUrl;
    }
}

// Add splash screen styles
function addSplashStyles() {
    if (document.getElementById('splash-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'splash-styles';
    style.textContent = `
        .splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .splash-content {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .splash-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            max-width: 100vw;
            max-height: 100vh;
        }

        .splash-skip-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .splash-skip-btn:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .splash-shop-now-btn {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            background: #00b894;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .splash-shop-now-btn:hover {
            background: #00a085;
            transform: translateY(-3px) translateX(-50%);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.3);
        }

        /* Animations */
        .splash-fade-in {
            animation: splashFadeIn 0.5s ease-out;
        }

        .splash-fade-out {
            animation: splashFadeOut 0.5s ease-in forwards;
        }

        @keyframes splashFadeIn {
            from {
                opacity: 0;
                transform: scale(1.05);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes splashFadeOut {
            from {
                opacity: 1;
                transform: scale(1);
            }
            to {
                opacity: 0;
                transform: scale(0.95);
            }
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .splash-skip-btn {
                top: 15px;
                right: 15px;
                padding: 6px 12px;
                font-size: 12px;
            }

            .splash-shop-now-btn {
                bottom: 30px;
                padding: 12px 30px;
                font-size: 16px;
            }
        }

        /* Landscape mode */
        @media (orientation: landscape) and (max-height: 500px) {
            .splash-skip-btn {
                top: 10px;
                right: 15px;
                padding: 6px 12px;
                font-size: 12px;
            }

            .splash-shop-now-btn {
                bottom: 20px;
                padding: 10px 25px;
                font-size: 14px;
            }
        }
    `;
    
    document.head.appendChild(style);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideSplashScreen();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initSplashScreen,
        showSplashScreen,
        hideSplashScreen,
        shopNowAction
    };
}
