<?php
/**
 * Product Filter Sidebar Component
 * 
 * Reusable filter sidebar for products listing pages
 * Uses Tailwind theme classes for consistent styling
 * Features: Hide/show toggle with cookie storage, AJAX filtering, dynamic colors
 */

// Get filter data from controller if available
$filterData = $filterData ?? [
    'categories' => [],
    'brands' => [],
    'sizes' => [],
    'colors' => [],
    'minPrice' => 0,
    'maxPrice' => 10000
];
?>

<div id="productFilterSidebar" class="hidden lg:block bg-neutral-50 w-full max-w-[280px] border-r border-neutral-200 shrink-0 px-6 sm:px-8 py-6 transition-all duration-300">
  <div class="flex items-center justify-between border-b border-neutral-300 pb-2 mb-6">
    <div class="flex items-center gap-2">
      <h3 class="text-foreground text-lg font-semibold">Filter</h3>
      <button type="button" id="toggleFilter" class="p-1 text-neutral-600 hover:text-foreground transition-colors" title="Hide/Show Filter">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <button type="button" id="clearAllFilters" class="text-sm text-error font-semibold cursor-pointer hover:text-error-dark">Clear all</button>
  </div>

  <div id="filterContent" class="filter-options space-y-6">
    <!-- Price Range -->
    <div>
      <div class="flex items-center gap-2 justify-between cursor-pointer">
        <h4 class="text-foreground text-base font-semibold">Price</h4>
      </div>
      <div class="relative mt-4">
        <div class="h-1.5 bg-neutral-300 relative rounded-full">
          <div id="activeTrack" class="absolute h-1.5 bg-primary rounded-full"></div>
        </div>
        <input type="range" id="minRange" min="<?= $filterData['minPrice'] ?>" max="<?= $filterData['maxPrice'] ?>" value="<?= $filterData['minPrice'] ?>" class="absolute top-0 w-full h-1.5 bg-transparent appearance-none cursor-pointer 
              [&::-webkit-slider-thumb]:appearance-none 
              [&::-webkit-slider-thumb]:w-5 
              [&::-webkit-slider-thumb]:h-5 
              [&::-webkit-slider-thumb]:bg-primary 
              [&::-webkit-slider-thumb]:rounded-full 
              [&::-webkit-slider-thumb]:border-2 
              [&::-webkit-slider-thumb]:border-white 
              [&::-webkit-slider-thumb]:shadow-md 
              [&::-webkit-slider-thumb]:cursor-pointer
              [&::-moz-range-thumb]:w-5 
              [&::-moz-range-thumb]:h-5 
              [&::-moz-range-thumb]:bg-primary 
              [&::-moz-range-thumb]:rounded-full 
              [&::-moz-range-thumb]:border-2 
              [&::-moz-range-thumb]:border-white 
              [&::-moz-range-thumb]:shadow-md 
              [&::-moz-range-thumb]:cursor-pointer
              [&::-moz-range-thumb]:border-none" />

        <input type="range" id="maxRange" min="<?= $filterData['minPrice'] ?>" max="<?= $filterData['maxPrice'] ?>" value="<?= $filterData['maxPrice'] ?>" class="absolute top-0 w-full h-1.5 bg-transparent appearance-none cursor-pointer 
              [&::-webkit-slider-thumb]:appearance-none 
              [&::-webkit-slider-thumb]:w-5 
              [&::-webkit-slider-thumb]:h-5 
              [&::-webkit-slider-thumb]:bg-primary 
              [&::-webkit-slider-thumb]:rounded-full 
              [&::-webkit-slider-thumb]:border-2 
              [&::-webkit-slider-thumb]:border-white 
              [&::-webkit-slider-thumb]:shadow-md 
              [&::-webkit-slider-thumb]:cursor-pointer
              [&::-moz-range-thumb]:w-5 
              [&::-moz-range-thumb]:h-5 
              [&::-moz-range-thumb]:bg-primary 
              [&::-moz-range-thumb]:rounded-full 
              [&::-moz-range-thumb]:border-2 
              [&::-moz-range-thumb]:border-white 
              [&::-moz-range-thumb]:shadow-md 
              [&::-moz-range-thumb]:cursor-pointer
              [&::-moz-range-thumb]:border-none" />

        <div class="flex justify-between text-neutral-600 font-medium text-sm mt-4">
          <span id="minPrice">रु<span id="minPriceValue"><?= $filterData['minPrice'] ?></span></span>
          <span id="maxPrice">रु<span id="maxPriceValue"><?= $filterData['maxPrice'] ?></span></span>
        </div>
      </div>
    </div>

    <!-- Category -->
    <div>
      <div class="header flex items-center gap-2 justify-between cursor-pointer">
        <h4 class="text-foreground text-base font-semibold">Category</h4>
        <svg class="arrow w-4 h-4 text-foreground transition-transform duration-300 -rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
      <div class="collape-content overflow-hidden transition-all duration-300">
        <div class="mt-4">
          <div class="flex items-center px-3 py-2 rounded-sm border border-neutral-300 bg-neutral-50 focus-within:bg-white overflow-hidden">
            <input type="text" id="categorySearch" placeholder="Search category"
              class="input w-full text-sm border-0 bg-transparent focus:ring-0" />
            <svg class="w-4 h-4 text-neutral-600 flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <ul id="categoryList" class="mt-6 space-y-4">
            <?php foreach ($filterData['categories'] as $index => $category): ?>
            <li class="flex items-center gap-3 category-item" data-category="<?= htmlspecialchars($category) ?>">
              <input id="filter-cat-<?= $index ?>" type="checkbox" name="categories[]" value="<?= htmlspecialchars($category) ?>" class="filter-checkbox w-4 h-4 cursor-pointer accent-primary" />
              <label for="filter-cat-<?= $index ?>" class="text-neutral-600 font-medium text-sm cursor-pointer"><?= htmlspecialchars($category) ?></label>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- Brand -->
    <div>
      <div class="header flex items-center gap-2 justify-between cursor-pointer">
        <h4 class="text-foreground text-base font-semibold">Brand</h4>
        <svg class="arrow w-4 h-4 text-foreground transition-transform duration-300 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
      <div class="collape-content h-0 overflow-hidden transition-all duration-300">
        <div class="mt-4">
          <div class="flex items-center px-3 py-2 rounded-sm border border-neutral-300 bg-neutral-50 focus-within:bg-white overflow-hidden">
            <input type="text" id="brandSearch" placeholder="Search brand"
              class="input w-full text-sm border-0 bg-transparent focus:ring-0" />
            <svg class="w-4 h-4 text-neutral-600 flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <ul id="brandList" class="mt-6 space-y-4">
            <?php foreach ($filterData['brands'] as $index => $brand): ?>
            <li class="flex items-center gap-3 brand-item" data-brand="<?= htmlspecialchars($brand) ?>">
              <input id="filter-brand-<?= $index ?>" type="checkbox" name="brands[]" value="<?= htmlspecialchars($brand) ?>" class="filter-checkbox w-4 h-4 cursor-pointer accent-primary" />
              <label for="filter-brand-<?= $index ?>" class="text-neutral-600 font-medium text-sm cursor-pointer"><?= htmlspecialchars($brand) ?></label>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- Size -->
    <div>
      <div class="header flex items-center gap-2 justify-between cursor-pointer">
        <h4 class="text-foreground text-base font-semibold">Size</h4>
        <svg class="arrow w-4 h-4 text-foreground transition-transform duration-300 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
      <div class="collape-content h-0 overflow-hidden transition-all duration-300">
        <div class="mt-4">
          <div class="flex flex-wrap gap-3" id="sizeButtons">
            <?php foreach ($filterData['sizes'] as $size): ?>
            <button type="button" data-size="<?= htmlspecialchars($size) ?>"
              class="size-btn cursor-pointer border border-neutral-300 hover:border-primary rounded-md text-[13px] text-neutral-600 font-medium py-1 px-1 min-w-14"><?= htmlspecialchars($size) ?></button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Color -->
    <div>
      <div class="header flex items-center gap-2 justify-between cursor-pointer">
        <h4 class="text-foreground text-base font-semibold">Color</h4>
        <svg class="arrow w-4 h-4 text-foreground transition-transform duration-300 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>

      <div class="collape-content h-0 overflow-hidden transition-all duration-300">
        <div class="mt-4">
          <div class="flex flex-wrap gap-3" id="colorButtons">
            <?php foreach ($filterData['colors'] as $color): ?>
              <?php
              $isHex = preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color);
              $colorClass = $isHex ? '' : 'bg-' . strtolower($color) . '-500';
              ?>
            <button type="button" data-color="<?= htmlspecialchars($color) ?>"
              class="color-btn cursor-pointer rounded-full w-8 h-8 hover:scale-[1.05] transition-all <?= $isHex ? '' : $colorClass ?>"
              <?= $isHex ? 'style="background-color: ' . htmlspecialchars($color) . '"' : '' ?>>
            </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  'use strict';
  
  // Cookie helper functions
  function setCookie(name, value, days = 365) {
    const expires = new Date();
    expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
  }
  
  function getCookie(name) {
    const nameEQ = name + '=';
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) === ' ') c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  }
  
  // Filter visibility toggle
  const filterSidebar = document.getElementById('productFilterSidebar');
  const filterContent = document.getElementById('filterContent');
  const toggleFilterBtn = document.getElementById('toggleFilter');
  
  // Load filter visibility state from cookie
  const filterVisible = getCookie('filterVisible') !== 'false';
  if (!filterVisible) {
    filterContent.style.display = 'none';
    toggleFilterBtn.querySelector('svg path').setAttribute('d', 'M4 6h16M4 12h16M4 18h16');
  }
  
  toggleFilterBtn.addEventListener('click', function() {
    const isVisible = filterContent.style.display !== 'none';
    filterContent.style.display = isVisible ? 'none' : 'block';
    setCookie('filterVisible', !isVisible);
    
    // Update icon
    const icon = toggleFilterBtn.querySelector('svg path');
    if (isVisible) {
      icon.setAttribute('d', 'M4 6h16M4 12h16M4 18h16'); // Hamburger
    } else {
      icon.setAttribute('d', 'M6 18L18 6M6 6l12 12'); // X
    }
  });
  
  // Collapsible sections
  document.querySelectorAll('.filter-options .header').forEach(header => {
    const content = header.parentElement.querySelector('.collape-content');
    if (!content.classList.contains('h-0')) {
      const fullHeight = `h-[${content.scrollHeight}px]`;
      content.classList.add(fullHeight);
    }

    header.addEventListener('click', () => {
      const arrow = header.querySelector('.arrow');
      if (content.classList.contains('h-0')) {
        const fullHeight = `h-[${content.scrollHeight}px]`;
        content.classList.add(fullHeight);
        content.classList.remove('h-0');
      } else {
        const fullHeight = `h-[${content.scrollHeight}px]`;
        content.classList.remove(fullHeight);
        content.classList.add('h-0');
      }

      if (arrow) {
        arrow.classList.toggle('-rotate-90');
        arrow.classList.toggle('rotate-90');
      }
    });
  });
  
  // Price range slider
  const minRange = document.getElementById('minRange');
  const maxRange = document.getElementById('maxRange');
  const activeTrack = document.getElementById('activeTrack');
  const minPriceValue = document.getElementById('minPriceValue');
  const maxPriceValue = document.getElementById('maxPriceValue');
  
  function updatePriceRange() {
    const min = parseInt(minRange.value);
    const max = parseInt(maxRange.value);
    minPriceValue.textContent = min;
    maxPriceValue.textContent = max;
    
    const minPercent = ((min - parseInt(minRange.min)) / (parseInt(minRange.max) - parseInt(minRange.min))) * 100;
    const maxPercent = ((max - parseInt(minRange.min)) / (parseInt(minRange.max) - parseInt(minRange.min))) * 100;
    activeTrack.style.left = minPercent + '%';
    activeTrack.style.width = (maxPercent - minPercent) + '%';
    
    applyFilters();
  }
  
  minRange.addEventListener('input', updatePriceRange);
  maxRange.addEventListener('input', updatePriceRange);
  
  // Search filters
  document.getElementById('categorySearch')?.addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('#categoryList .category-item').forEach(item => {
      const text = item.textContent.toLowerCase();
      item.style.display = text.includes(term) ? 'flex' : 'none';
    });
  });
  
  document.getElementById('brandSearch')?.addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('#brandList .brand-item').forEach(item => {
      const text = item.textContent.toLowerCase();
      item.style.display = text.includes(term) ? 'flex' : 'none';
    });
  });
  
  // Filter checkboxes and buttons
  let filterTimeout;
  document.querySelectorAll('.filter-checkbox, .size-btn, .color-btn').forEach(element => {
    element.addEventListener('change', applyFilters);
    element.addEventListener('click', function(e) {
      if (e.target.classList.contains('size-btn') || e.target.classList.contains('color-btn')) {
        e.target.classList.toggle('border-primary');
        e.target.classList.toggle('bg-primary/10');
        applyFilters();
      }
    });
  });
  
  // Clear all filters
  document.getElementById('clearAllFilters')?.addEventListener('click', function() {
    document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
    document.querySelectorAll('.size-btn, .color-btn').forEach(btn => {
      btn.classList.remove('border-primary', 'bg-primary/10');
    });
    minRange.value = minRange.min;
    maxRange.value = maxRange.max;
    updatePriceRange();
    applyFilters();
  });
  
  // AJAX Filter Application
  function applyFilters() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
      const filters = {
        min_price: minRange.value,
        max_price: maxRange.value,
        categories: Array.from(document.querySelectorAll('.filter-checkbox[name="categories[]"]:checked')).map(cb => cb.value),
        brands: Array.from(document.querySelectorAll('.filter-checkbox[name="brands[]"]:checked')).map(cb => cb.value),
        sizes: Array.from(document.querySelectorAll('.size-btn.border-primary')).map(btn => btn.dataset.size),
        colors: Array.from(document.querySelectorAll('.color-btn.border-primary')).map(btn => btn.dataset.color)
      };
      
      const params = new URLSearchParams();
      Object.keys(filters).forEach(key => {
        if (filters[key].length > 0) {
          if (Array.isArray(filters[key])) {
            filters[key].forEach(val => params.append(key + '[]', val));
          } else {
            params.append(key, filters[key]);
          }
        }
      });
      
      // Preserve existing sort and page params
      const urlParams = new URLSearchParams(window.location.search);
      const sort = urlParams.get('sort');
      const page = urlParams.get('page');
      if (sort) params.set('sort', sort);
      if (page) params.set('page', page);
      
      // Show loading state
      const productsContainer = document.querySelector('.grid.grid-cols-2');
      if (productsContainer) {
        productsContainer.style.opacity = '0.5';
        productsContainer.style.pointerEvents = 'none';
      }
      
      // AJAX request
      fetch('<?= \App\Core\View::url('products/filter') ?>?' + params.toString(), {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (productsContainer) {
          productsContainer.style.opacity = '1';
          productsContainer.style.pointerEvents = 'auto';
        }
        
        if (data.success && data.html) {
          if (productsContainer) {
            productsContainer.innerHTML = data.html;
            // Reinitialize wishlist buttons if needed
            if (typeof initializeWishlistFromCache === 'function') {
              initializeWishlistFromCache();
            }
            if (typeof addToWishlist !== 'undefined' && typeof removeFromWishlist !== 'undefined') {
              // Wishlist functions already defined
            }
          }
          
          // Update URL without reload
          window.history.pushState({}, '', '<?= \App\Core\View::url('products') ?>?' + params.toString());
        }
      })
      .catch(error => {
        console.error('Filter error:', error);
        if (productsContainer) {
          productsContainer.style.opacity = '1';
          productsContainer.style.pointerEvents = 'auto';
        }
      });
    }, 300);
  }
})();
</script>
