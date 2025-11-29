<!-- Mobile Search Component - Highly Compatible for Mobile -->
<div class="lg:hidden bg-primary px-3 py-2.5 sticky top-0 z-50">
    <div class="flex items-center gap-2.5">
        <a href="<?= URLROOT ?>" class="flex-shrink-0" aria-label="Home">
            <img src="<?= \App\Core\View::asset('images/logo/logo.png') ?>" alt="Nutri Nexas" class="w-9 h-9 rounded-full" loading="lazy" onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'" />
        </a>
        <div class="flex-1 relative min-w-0">
            <form action="<?= \App\Core\View::url('products/search') ?>" method="get" id="mobileSearchForm" class="w-full">
                <input 
                    type="search" 
                    name="q"
                    id="mobileSearchInput"
                    class="w-full border border-white/20 bg-white/10 focus:bg-white/20 rounded-full px-3 py-2 text-white placeholder:text-white/70 outline-none text-sm focus:border-white/40 transition-all"
                    placeholder="Search products..."
                    autocomplete="off"
                    autocapitalize="off"
                    autocorrect="off"
                    spellcheck="false"
                    inputmode="search"
                    aria-label="Search products"
                />
                <input type="hidden" name="sort" value="newest" />
            </form>
            
            <!-- Search Suggestions Dropdown -->
            <div id="mobileSearchSuggestions" class="absolute top-full left-0 right-0 mt-1 bg-white rounded-2xl shadow-xl border border-gray-200 z-50 hidden max-h-[60vh] overflow-hidden">
                <div id="mobileSuggestionsList" class="max-h-[60vh] overflow-y-auto">
                    <!-- Suggestions will be populated here -->
                </div>
            </div>
        </div>
        <a href="<?= \App\Core\View::url('cart') ?>" class="flex-shrink-0 text-white p-1.5 relative" aria-label="Cart">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
            <span class="cart-count absolute -top-1 -right-1 bg-white text-primary rounded-full text-[10px] font-bold w-4 h-4 flex items-center justify-center">0</span>
        </a>
    </div>
</div>

<!-- Mobile Search JavaScript - Highly Compatible -->
<script>
(function() {
    'use strict';
    
    // Mobile Search functionality - Wait for DOM
    function initMobileSearch() {
        const mobileSearchInput = document.getElementById('mobileSearchInput');
        const mobileSearchSuggestions = document.getElementById('mobileSearchSuggestions');
        const mobileSuggestionsList = document.getElementById('mobileSuggestionsList');
        const mobileSearchForm = document.getElementById('mobileSearchForm');
        
        if (!mobileSearchInput || !mobileSearchSuggestions || !mobileSuggestionsList || !mobileSearchForm) {
            return;
        }
        
        let mobileSearchTimeout = null;
        let isSubmitting = false;
        
        // Input event handler with debounce
        mobileSearchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Clear previous timeout
            if (mobileSearchTimeout) {
                clearTimeout(mobileSearchTimeout);
            }
            
            // Hide suggestions if query is too short
            if (query.length < 2) {
                mobileSearchSuggestions.classList.add('hidden');
                return;
            }
            
            // Debounce search requests (300ms)
            mobileSearchTimeout = setTimeout(function() {
                fetchMobileSearchSuggestions(query);
            }, 300);
        });
        
        // Handle Enter key - submit form
        mobileSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                if (!isSubmitting && this.value.trim().length > 0) {
                    isSubmitting = true;
                    mobileSearchSuggestions.classList.add('hidden');
                    mobileSearchForm.submit();
                }
            }
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (mobileSearchInput && mobileSearchSuggestions) {
                if (!mobileSearchInput.contains(e.target) && !mobileSearchSuggestions.contains(e.target)) {
                    mobileSearchSuggestions.classList.add('hidden');
                }
            }
        });
        
        // Handle suggestion clicks
        mobileSuggestionsList.addEventListener('click', function(e) {
            const item = e.target.closest('.mobile-suggestion-item');
            if (item && !isSubmitting) {
                const productName = item.textContent.trim();
                if (productName) {
                    mobileSearchInput.value = productName;
                    mobileSearchSuggestions.classList.add('hidden');
                    isSubmitting = true;
                    mobileSearchForm.submit();
                }
            }
        });
        
        // Fetch search suggestions
        function fetchMobileSearchSuggestions(query) {
            if (!query || query.length < 2) {
                mobileSearchSuggestions.classList.add('hidden');
                return;
            }
            
            const url = '<?= \App\Core\View::url('products/liveSearch') ?>?q=' + encodeURIComponent(query);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                cache: 'no-cache'
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function(data) {
                if (data && data.success && Array.isArray(data.suggestions) && data.suggestions.length > 0) {
                    displayMobileSuggestions(data.suggestions);
                } else {
                    mobileSearchSuggestions.classList.add('hidden');
                }
            })
            .catch(function(error) {
                console.error('Mobile search suggestions error:', error);
                mobileSearchSuggestions.classList.add('hidden');
            });
        }
        
        // Display suggestions
        function displayMobileSuggestions(suggestions) {
            if (!Array.isArray(suggestions) || suggestions.length === 0) {
                mobileSearchSuggestions.classList.add('hidden');
                return;
            }
            
            mobileSuggestionsList.innerHTML = '';
            
            suggestions.forEach(function(suggestion) {
                if (suggestion && suggestion.name) {
                    const item = document.createElement('div');
                    item.className = 'mobile-suggestion-item px-4 py-2.5 hover:bg-gray-100 active:bg-gray-200 cursor-pointer text-sm text-gray-700 border-b border-gray-100 last:border-b-0';
                    item.textContent = suggestion.name;
                    item.setAttribute('role', 'button');
                    item.setAttribute('tabindex', '0');
                    mobileSuggestionsList.appendChild(item);
                }
            });
            
            mobileSearchSuggestions.classList.remove('hidden');
        }
        
        // Reset submitting flag when form is submitted
        mobileSearchForm.addEventListener('submit', function() {
            isSubmitting = false;
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileSearch);
    } else {
        initMobileSearch();
    }
})();
</script>
