// banner.js - Optimized Compact Banner Slider
class BannerSlider {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        this.options = {
            autoPlay: options.autoPlay !== false ? (options.autoPlay || 4000) : false,
            images: options.images || [],
            height: options.height || '180px', // Reduced default height
            borderRadius: options.borderRadius || '8px',
            gap: options.gap || '12px',
            showDots: options.showDots !== false,
            animationDuration: options.animationDuration || 500,
            ...options
        };
        
        this.currentIndex = 0;
        this.isAutoPlaying = true;
        this.autoPlayInterval = null;
        this.isTransitioning = false;
        
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.error('Banner container not found');
            return;
        }
        
        if (this.options.images.length === 0) {
            console.warn('No images provided for banner slider');
            return;
        }
        
        this.createHTML();
        this.setupStyles();
        this.setupEvents();
        this.startAutoPlay();
        this.preloadImages();
    }
    
    createHTML() {
        this.container.innerHTML = `
            <div class="banner-slider-wrapper">
                <div class="banner-slider-track">
                    ${this.options.images.map((img, index) => `
                        <div class="banner-slide ${index === 0 ? 'active' : ''}" data-index="${index}">
                            <img src="${img}" alt="Banner ${index + 1}" loading="${index === 0 ? 'eager' : 'lazy'}">
                        </div>
                    `).join('')}
                </div>
                ${this.options.showDots && this.options.images.length > 1 ? `
                    <div class="banner-dots">
                        ${this.options.images.map((_, index) => `
                            <div class="banner-dot ${index === 0 ? 'active' : ''}" data-index="${index}"></div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        `;
        
        this.track = this.container.querySelector('.banner-slider-track');
        this.slides = this.container.querySelectorAll('.banner-slide');
        this.dots = this.container.querySelectorAll('.banner-dot');
    }
    
    setupStyles() {
        const styleId = 'banner-slider-styles';
        if (!document.querySelector(`#${styleId}`)) {
            const style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                .banner-slider-wrapper {
                    position: relative;
                    width: 100%;
                    height: ${this.options.height};
                    overflow: hidden;
                    border-radius: ${this.options.borderRadius};
                    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
                    background: #f8f9fa;
                }
                
                .banner-slider-track {
                    position: relative;
                    width: 100%;
                    height: 100%;
                    touch-action: pan-y;
                }
                
                .banner-slide {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    opacity: 0;
                    transform: translateX(30px);
                    transition: all ${this.options.animationDuration}ms cubic-bezier(0.25, 0.8, 0.25, 1);
                    will-change: transform, opacity;
                }
                
                .banner-slide.active {
                    opacity: 1;
                    transform: translateX(0);
                }
                
                .banner-slide.prev {
                    transform: translateX(-30px);
                }
                
                .banner-slide img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    object-position: center;
                    border-radius: ${this.options.borderRadius};
                    transition: transform 0.3s ease;
                    display: block;
                }
                
                .banner-slide img:hover {
                    transform: scale(1.02);
                }
                
                .banner-dots {
                    position: absolute;
                    bottom: 12px;
                    left: 50%;
                    transform: translateX(-50%);
                    display: flex;
                    gap: 6px;
                    z-index: 10;
                    padding: 4px 8px;
                    background: rgba(0, 0, 0, 0.1);
                    border-radius: 12px;
                    backdrop-filter: blur(8px);
                }
                
                .banner-dot {
                    width: 6px;
                    height: 6px;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    cursor: pointer;
                    transition: all 0.2s ease;
                    border: none;
                    padding: 0;
                }
                
                .banner-dot.active {
                    background: rgba(255, 255, 255, 0.95);
                    transform: scale(1.3);
                }
                
                .banner-dot:hover {
                    background: rgba(255, 255, 255, 0.8);
                    transform: scale(1.2);
                }
                
                /* Responsive adjustments */
                @media (max-width: 768px) {
                    .banner-slider-wrapper {
                        height: ${Math.max(parseInt(this.options.height) * 0.8, 120)}px;
                        border-radius: ${Math.max(parseInt(this.options.borderRadius) * 0.8, 4)}px;
                    }
                    
                    .banner-dots {
                        bottom: 8px;
                        gap: 4px;
                    }
                    
                    .banner-dot {
                        width: 5px;
                        height: 5px;
                    }
                }
                
                @media (max-width: 480px) {
                    .banner-slider-wrapper {
                        height: ${Math.max(parseInt(this.options.height) * 0.7, 100)}px;
                    }
                    
                    .banner-dots {
                        bottom: 6px;
                    }
                }
                
                /* Performance optimizations */
                .banner-slider-wrapper * {
                    box-sizing: border-box;
                }
                
                .banner-slide img {
                    image-rendering: -webkit-optimize-contrast;
                    image-rendering: optimize-contrast;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    setupEvents() {
        // Touch/swipe events
        let startX = 0;
        let startY = 0;
        let moveX = 0;
        let isMoving = false;
        let startTime = 0;
        
        this.track.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            startTime = Date.now();
            isMoving = false;
            this.pauseAutoPlay();
        }, { passive: true });
        
        this.track.addEventListener('touchmove', (e) => {
            if (!startX) return;
            
            moveX = e.touches[0].clientX;
            const moveY = e.touches[0].clientY;
            const diffX = startX - moveX;
            const diffY = startY - moveY;
            
            // Only handle horizontal swipes
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 10) {
                isMoving = true;
                e.preventDefault();
            }
        }, { passive: false });
        
        this.track.addEventListener('touchend', (e) => {
            if (!startX || !isMoving) {
                this.resumeAutoPlay();
                return;
            }
            
            const diffX = startX - moveX;
            const diffTime = Date.now() - startTime;
            const velocity = Math.abs(diffX) / diffTime;
            const threshold = velocity > 0.5 ? 30 : 50;
            
            if (Math.abs(diffX) > threshold) {
                if (diffX > 0) {
                    this.next();
                } else {
                    this.prev();
                }
            }
            
            startX = 0;
            moveX = 0;
            isMoving = false;
            this.resumeAutoPlay();
        }, { passive: true });
        
        // Dot navigation
        if (this.dots.length > 0) {
            this.dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    this.goToSlide(index);
                });
            });
        }
        
        // Pause on hover for desktop
        if (window.matchMedia('(hover: hover)').matches) {
            this.container.addEventListener('mouseenter', () => this.pauseAutoPlay());
            this.container.addEventListener('mouseleave', () => this.resumeAutoPlay());
        }
        
        // Visibility API for performance
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseAutoPlay();
            } else if (this.isAutoPlaying) {
                this.resumeAutoPlay();
            }
        });
        
        // Keyboard navigation
        this.container.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                this.prev();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                this.next();
            }
        });
        
        // Make container focusable for keyboard navigation
        this.container.setAttribute('tabindex', '0');
    }
    
    goToSlide(index) {
        if (index === this.currentIndex || this.isTransitioning) return;
        if (index < 0 || index >= this.slides.length) return;
        
        this.isTransitioning = true;
        
        // Remove all classes
        this.slides.forEach(slide => {
            slide.classList.remove('active', 'prev');
        });
        
        if (this.dots.length > 0) {
            this.dots.forEach(dot => {
                dot.classList.remove('active');
            });
        }
        
        // Set previous slide
        if (this.slides[this.currentIndex]) {
            this.slides[this.currentIndex].classList.add('prev');
        }
        
        // Set new active slide
        this.slides[index].classList.add('active');
        if (this.dots[index]) {
            this.dots[index].classList.add('active');
        }
        
        this.currentIndex = index;
        
        // Reset transition flag
        setTimeout(() => {
            this.isTransitioning = false;
        }, this.options.animationDuration);
    }
    
    next() {
        const nextIndex = (this.currentIndex + 1) % this.slides.length;
        this.goToSlide(nextIndex);
    }
    
    prev() {
        const prevIndex = (this.currentIndex - 1 + this.slides.length) % this.slides.length;
        this.goToSlide(prevIndex);
    }
    
    startAutoPlay() {
        if (!this.isAutoPlaying || this.options.autoPlay === false || this.slides.length <= 1) return;
        
        this.autoPlayInterval = setInterval(() => {
            this.next();
        }, this.options.autoPlay);
    }
    
    pauseAutoPlay() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
            this.autoPlayInterval = null;
        }
    }
    
    resumeAutoPlay() {
        if (this.isAutoPlaying && !this.autoPlayInterval && this.slides.length > 1) {
            this.startAutoPlay();
        }
    }
    
    preloadImages() {
        this.options.images.forEach((src, index) => {
            if (index === 0) return; // First image already loaded
            
            const img = new Image();
            img.src = src;
        });
    }
    
    // Public API methods
    play() {
        this.isAutoPlaying = true;
        this.startAutoPlay();
    }
    
    pause() {
        this.isAutoPlaying = false;
        this.pauseAutoPlay();
    }
    
    getCurrentIndex() {
        return this.currentIndex;
    }
    
    getSlideCount() {
        return this.slides.length;
    }
    
    updateImages(newImages) {
        this.options.images = newImages;
        this.currentIndex = 0;
        this.pauseAutoPlay();
        this.createHTML();
        this.setupEvents();
        this.startAutoPlay();
    }
    
    destroy() {
        this.pauseAutoPlay();
        this.container.innerHTML = '';
        this.container.removeAttribute('tabindex');
        
        // Clean up style if no other instances
        const otherInstances = document.querySelectorAll('.banner-slider-wrapper');
        if (otherInstances.length === 0) {
            const style = document.querySelector('#banner-slider-styles');
            if (style) {
                style.remove();
            }
        }
    }
}

// Utility function for easy initialization
function createBannerSlider(containerId, images, options = {}) {
    const container = typeof containerId === 'string' ? document.querySelector(containerId) : containerId;
    
    if (!container) {
        console.error(`Banner container "${containerId}" not found`);
        return null;
    }
    
    if (!images || images.length === 0) {
        console.error('No images provided for banner slider');
        return null;
    }
    
    return new BannerSlider(container, {
        images: images,
        ...options
    });
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { BannerSlider, createBannerSlider };
}

// Auto-initialize with data attributes
document.addEventListener('DOMContentLoaded', () => {
    const banners = document.querySelectorAll('[data-banner-slider]');
    banners.forEach(banner => {
        const images = banner.dataset.images ? JSON.parse(banner.dataset.images) : [];
        const options = {
            height: banner.dataset.height || '180px',
            autoPlay: banner.dataset.autoplay !== 'false' ? (parseInt(banner.dataset.autoplay) || 4000) : false,
            borderRadius: banner.dataset.borderRadius || '8px',
            showDots: banner.dataset.showDots !== 'false'
        };
        
        if (images.length > 0) {
            new BannerSlider(banner, { images, ...options });
        }
    });
});

/* 
USAGE EXAMPLES:

1. Basic HTML Setup:
<div id="hero-banner"></div>

2. JavaScript Initialization:
const bannerImages = [
    'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=300&fit=crop',
    'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&h=300&fit=crop',
    'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=800&h=300&fit=crop'
];

const slider = createBannerSlider('#hero-banner', bannerImages, {
    height: '180px',
    autoPlay: 4000,
    borderRadius: '8px',
    showDots: true
});

3. HTML Data Attributes (Auto-initialize):
<div data-banner-slider
     data-images='["image1.jpg", "image2.jpg", "image3.jpg"]'
     data-height="200px"
     data-autoplay="5000"
     data-border-radius="12px">
</div>

4. Advanced Usage:
const slider = new BannerSlider('#banner', {
    images: ['img1.jpg', 'img2.jpg'],
    height: '150px',
    autoPlay: 3000,
    borderRadius: '6px',
    showDots: true,
    animationDuration: 600
});

// Control methods
slider.next();
slider.prev();
slider.goToSlide(1);
slider.play();
slider.pause();
slider.destroy();
*/