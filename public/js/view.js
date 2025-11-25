document.addEventListener('DOMContentLoaded', function () {
    // Image gallery functionality
    const thumbnails = document.querySelectorAll('.product-thumbnail');
    const mainImage = document.getElementById('mainProductImage');

    thumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener('click', function () {
            const imageUrl = this.dataset.imageUrl;
            mainImage.src = imageUrl;

            // Update active thumbnail
            thumbnails.forEach((t) => {
                t.classList.remove('border-primary');
                t.classList.add('border-gray-200', 'hover:border-primary');
            });
            this.classList.remove('border-gray-200', 'hover:border-primary');
            this.classList.add('border-primary');
        });
    });

    // Quantity controls
    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.getElementById('decrease-qty');
    const increaseBtn = document.getElementById('increase-qty');
    const maxQuantity = parseInt(quantityInput.getAttribute('max'));

    decreaseBtn.addEventListener('click', function () {
        const currentValue = parseInt(quantityInput.value);
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    });

    increaseBtn.addEventListener('click', function () {
        const currentValue = parseInt(quantityInput.value);
        if (currentValue < maxQuantity) {
            quantityInput.value = currentValue + 1;
        }
    });

    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const tabName = this.dataset.tab;

            // Remove active class from all buttons
            tabButtons.forEach((btn) => {
                btn.classList.remove('active', 'border-primary', 'text-primary');
                btn.classList.add('border-transparent', 'text-gray-500');
            });

            // Add active class to clicked button
            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('active', 'border-primary', 'text-primary');

            // Hide all tab contents
            tabContents.forEach((content) => {
                content.classList.add('hidden');
            });

            // Show selected tab content
            const targetTab = document.getElementById(`${tabName}-tab`);
            if (targetTab) {
                targetTab.classList.remove('hidden');
            }
        });
    });

    // Copy URL functionality
    const copyBtn = document.getElementById('copy-url-btn');
    const productUrl = document.getElementById('product-url');
    const copySuccess = document.getElementById('copy-success');

    copyBtn.addEventListener('click', function () {
        productUrl.select();
        productUrl.setSelectionRange(0, 99999); // For mobile devices

        try {
            document.execCommand('copy');
            copySuccess.classList.remove('hidden');
            setTimeout(() => {
                copySuccess.classList.add('hidden');
            }, 3000);
        } catch (err) {
            console.error('Failed to copy URL:', err);
            showMessage('error', 'Error', 'Failed to copy URL. Please try again.');
        }
    });

    // Image zoom functionality
    mainImage.addEventListener('click', function () {
        // Create modal for image zoom
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4';
        modal.innerHTML = `
            <div class="relative max-w-4xl max-h-full">
                <img src="${this.src}" alt="${this.alt}" class="max-w-full max-h-full object-contain">
                <button class="absolute top-4 right-4 text-white hover:text-gray-300 text-2xl font-bold">×</button>
            </div>
        `;

        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';

        // Close modal functionality
        const closeBtn = modal.querySelector('button');
        const closeModal = () => {
            document.body.removeChild(modal);
            document.body.style.overflow = 'auto';
        };

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Close on escape key
        document.addEventListener(
            'keydown',
            function (e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            },
            { once: true }
        );
    });

    // Message modal functions
    function showMessage(type, title, message) {
        const modal = document.getElementById('messageModal');
        const icon = document.getElementById('messageIcon');
        const titleEl = document.getElementById('messageTitle');
        const messageEl = document.getElementById('messageText');
        const btn = document.getElementById('messageModalBtn');

        // Set icon and colors based on type
        if (type === 'success') {
            icon.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100';
            icon.innerHTML = '<svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
            btn.className = 'w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500';
        } else {
            icon.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100';
            icon.innerHTML = '<svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
            btn.className = 'w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
        }

        titleEl.textContent = title;
        messageEl.textContent = message;

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    window.closeMessageModal = function () {
        const modal = document.getElementById('messageModal');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    // Close modal when clicking backdrop
    document.getElementById('messageModalBackdrop')?.addEventListener('click', closeMessageModal);
});