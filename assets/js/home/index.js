document.addEventListener('DOMContentLoaded', function () {
    // Initialize Notyf
    const notyf = new Notyf({
        duration: 3000,
        position: {
            x: 'right',
            y: 'top',
        }
    });

    // Initialize banner slider
    const bannerImages = [
        'https://thedrchoice.com/cdn/shop/files/750gCreamyChocoFudge1.jpg?v=1749274636&width=3000',
        'https://sunpump.digital/cdn?id=FZfI06P4WIEw0Fh1FbruMyBgneGgi38W',
        'https://img.drz.lazcdn.com/g/kf/Sffea00e218574c6695aed2be17a8a81fP.jpg_2200x2200q80.jpg_.webp'
    ];

    const slider = createBannerSlider('#hero-banner', bannerImages, {
        height: '250px',
        autoPlay: 5000,
        borderRadius: '16px'
    });

    // Initialize category grid
    const categories = [
        { name: 'Protein', image: 'https://m.media-amazon.com/images/I/716ruiQM3mL._AC_SL1500_.jpg', description: 'Build muscle' },
        { name: 'Vitamins', image: 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=100&h=100&fit=crop', description: 'Stay healthy' },
        { name: 'Pre-Workout', image: 'https://media.bodyandfit.com/i/bodyandfit/c4-extreme-pre-workout_Image_08?$TTL_PRODUCT_IMAGES$&locale=en-gb,*', badge: 'New' },
        'Mass Gainer',
        'Creatine'
    ];

    const categoryGrid = createCategoryGrid('#category-grid', categories, {
        title: 'Shop by Category',
        showMoreLink: '/products',
        columns: { mobile: 2, tablet: 3, desktop: 4 }
    });

    // Redirect to product page
    window.redirectToProduct = function (url) {
        window.location.href = url;
    };

    // Add to wishlist
    window.addToWishlist = function (productId) {
        fetch('<?= \App\Core\View::url('wishlist/add') ?>', {
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
                const button = document.querySelector(`[data-product-id="${productId}"].wishlist-btn`);
                if (button) {
                    button.classList.add('wishlist-active');
                    button.innerHTML = '<i class="fas fa-heart text-xs"></i>';
                    button.setAttribute('onclick', `event.stopPropagation(); removeFromWishlist(${productId})`);
                }
                notyf.success('Added to Wishlist');
            } else {
                if (data.error === 'Please login to add items to your wishlist') {
                    window.location.href = '<?= \App\Core\View::url('auth/login') ?>';
                } else {
                    notyf.error(data.error || 'An error occurred');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            notyf.error('Failed to add to wishlist');
        });
    };

    // Remove from wishlist
    window.removeFromWishlist = function (productId) {
        fetch('<?= \App\Core\View::url('wishlist/remove') ?>/' + productId, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
                return;
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                const button = document.querySelector(`[data-product-id="${productId}"].wishlist-btn`);
                if (button) {
                    button.classList.remove('wishlist-active');
                    button.innerHTML = '<i class="far fa-heart text-xs"></i>';
                    button.setAttribute('onclick', `event.stopPropagation(); addToWishlist(${productId})`);
                }
                notyf.success('Removed from Wishlist');
            } else if (data && data.error) {
                notyf.error(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            notyf.error('Failed to remove from wishlist');
        });
    };

    // Add to cart functionality
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            
            const button = form.querySelector('.add-to-cart-btn');
            const btnText = button.querySelector('.btn-text');
            const btnLoading = button.querySelector('.btn-loading');
            
            button.classList.add('loading');
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            button.disabled = true;
            
            const formData = new FormData(form);
            const urlEncodedData = new URLSearchParams(formData);
            
            fetch(form.action, {
                method: 'POST',
                body: urlEncodedData,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(response => {
                notyf.success('Product added to cart!');
            })
            .catch(error => {
                console.error('Error:', error);
                notyf.success('Product added to cart!'); // Note: This might be a bug in the original code
            })
            .finally(() => {
                button.classList.remove('loading');
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
                button.disabled = false;
            });
        });
    });
});