/**
 * Splash Screen JavaScript
 * Displays splash screen image to fit entire screen
 */

class SplashScreen {
    constructor() {
        this.splashElement = null;
        this.isVisible = false;
        this.init();
    }

    init() {
        // Create splash screen element
        this.createSplashElement();
        
        // Show splash screen on page load
        this.show();
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            this.hide();
        }, 3000);
        
        // Allow manual skip
        this.addSkipButton();
        
        // Handle window resize
        window.addEventListener('resize', () => {
            this.adjustImageSize();
        });
    }

    createSplashElement() {
        // Create splash container
        this.splashElement = document.createElement('div');
        this.splashElement.id = 'splash-screen';
        this.splashElement.className = 'splash-screen';
        
        // Create splash content
        const splashContent = document.createElement('div');
        splashContent.className = 'splash-content';
        
        // Create image element
        const splashImage = document.createElement('img');
        splashImage.src = '/images/splash/1.png';
        splashImage.alt = 'PAYDAY JHATKA - NutriNexas';
        splashImage.className = 'splash-image';
        
        // Create skip button
        const skipButton = document.createElement('button');
        skipButton.className = 'splash-skip-btn';
        skipButton.textContent = 'Skip';
        skipButton.addEventListener('click', () => {
            this.hide();
        });
        
        // Create shop now button
        const shopNowButton = document.createElement('button');
        shopNowButton.className = 'splash-shop-now-btn';
        shopNowButton.textContent = 'Shop Now';
        shopNowButton.addEventListener('click', () => {
            this.hide();
            // Redirect to products page or main shop
            window.location.href = '/products';
        });
        
        // Assemble splash screen
        splashContent.appendChild(splashImage);
        splashContent.appendChild(skipButton);
        splashContent.appendChild(shopNowButton);
        this.splashElement.appendChild(splashContent);
        
        // Add to body
        document.body.appendChild(this.splashElement);
        
        // Add CSS styles
        this.addStyles();
    }

    addStyles() {
        const style = document.createElement('style');
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
                transition: none;
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
                transition: none;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .splash-shop-now-btn:hover {
                background: #00a085;
                transform: translateY(-3px) translateX(-50%);
                box-shadow: 0 6px 25px rgba(0, 0, 0, 0.3);
            }

            /* Mobile responsive adjustments */
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

            /* Landscape mode adjustments */
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

            /* Animation for splash screen appearance */
            .splash-screen {
                animation: splashFadeIn 0.5s ease-out;
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

            /* Hide splash screen with animation */
            .splash-screen.hiding {
                animation: splashFadeOut 0.5s ease-in forwards;
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
        `;
        
        document.head.appendChild(style);
    }

    show() {
        if (this.splashElement) {
            this.splashElement.style.display = 'flex';
            this.isVisible = true;
            this.adjustImageSize();
        }
    }

    hide() {
        if (this.splashElement && this.isVisible) {
            this.splashElement.classList.add('hiding');
            
            setTimeout(() => {
                if (this.splashElement && this.splashElement.parentNode) {
                    this.splashElement.parentNode.removeChild(this.splashElement);
                }
                this.isVisible = false;
            }, 500);
        }
    }

    adjustImageSize() {
        if (!this.splashElement) return;
        
        const image = this.splashElement.querySelector('.splash-image');
        if (!image) return;
        
        const container = this.splashElement;
        const containerWidth = container.offsetWidth;
        const containerHeight = container.offsetHeight;
        
        // Calculate aspect ratios
        const imageAspectRatio = image.naturalWidth / image.naturalHeight;
        const containerAspectRatio = containerWidth / containerHeight;
        
        if (containerAspectRatio > imageAspectRatio) {
            // Container is wider than image - fit to height
            image.style.width = 'auto';
            image.style.height = '100%';
        } else {
            // Container is taller than image - fit to width
            image.style.width = '100%';
            image.style.height = 'auto';
        }
    }

    // Method to check if splash screen is visible
    isSplashVisible() {
        return this.isVisible;
    }

    // Method to manually trigger splash screen
    triggerSplash() {
        this.show();
    }
}

// Initialize splash screen when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Create global splash screen instance
    window.splashScreen = new SplashScreen();
    
    // Optional: Add keyboard shortcut to skip (ESC key)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && window.splashScreen && window.splashScreen.isSplashVisible()) {
            window.splashScreen.hide();
        }
    });
});

// Export for module usage if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SplashScreen;
}
