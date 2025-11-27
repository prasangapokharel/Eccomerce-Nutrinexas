<?php
/**
 * Product Filter Sidebar Component
 * 
 * Reusable filter sidebar for products listing pages
 * Uses Tailwind theme classes for consistent styling
 */
?>

<div class="hidden lg:block bg-neutral-50 w-full max-w-[280px] border-r border-neutral-200 shrink-0 px-6 sm:px-8 py-6">
  <div class="flex items-center border-b border-neutral-300 pb-2 mb-6">
    <h3 class="text-foreground text-lg font-semibold">Filter</h3>
    <button type="button" class="text-sm text-error font-semibold ml-auto cursor-pointer hover:text-error-dark">Clear all</button>
  </div>

  <div class="filter-options space-y-6">
    <!-- Price Range -->
    <div>
      <div class="flex items-center gap-2 justify-between cursor-pointer">
        <h4 class="text-foreground text-base font-semibold">Price</h4>
      </div>
      <div class="relative mt-4">
        <div class="h-1.5 bg-neutral-300 relative">
          <div id="activeTrack" class="absolute h-1.5 bg-primary rounded-full w-9/12"></div>
        </div>
        <input type="range" id="minRange" min="0" max="1000" value="0" class="absolute top-0 w-full h-1.5 bg-transparent appearance-none cursor-pointer 
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

        <input type="range" id="maxRange" min="0" max="1000" value="750" class="absolute top-0 w-full h-1.5 bg-transparent appearance-none cursor-pointer 
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
          <span id="minPrice">रु0</span>
          <span id="maxPrice">रु1000</span>
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
          <div
            class="flex px-3 py-2 rounded-sm border border-neutral-300 bg-neutral-50 focus-within:bg-white overflow-hidden">
            <input type="text" placeholder="Search category"
              class="input w-full text-sm" />
            <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <ul class="mt-6 space-y-4">
            <li class="flex items-center gap-3">
              <input id="filter-cat-1" type="checkbox" class="w-4 h-4 cursor-pointer accent-primary" />
              <label for="filter-cat-1" class="text-neutral-600 font-medium text-sm cursor-pointer">Supplements</label>
            </li>
            <li class="flex items-center gap-3">
              <input id="filter-cat-2" type="checkbox" class="w-4 h-4 cursor-pointer accent-primary" />
              <label for="filter-cat-2" class="text-neutral-600 font-medium text-sm cursor-pointer">Vitamins</label>
            </li>
            <li class="flex items-center gap-3">
              <input id="filter-cat-3" type="checkbox" class="w-4 h-4 cursor-pointer accent-primary" />
              <label for="filter-cat-3" class="text-neutral-600 font-medium text-sm cursor-pointer">Protein</label>
            </li>
            <li class="flex items-center gap-3">
              <input id="filter-cat-4" type="checkbox" class="w-4 h-4 cursor-pointer accent-primary" />
              <label for="filter-cat-4" class="text-neutral-600 font-medium text-sm cursor-pointer">Wellness</label>
            </li>
            <li class="flex items-center gap-3">
              <input id="filter-cat-5" type="checkbox" class="w-4 h-4 cursor-pointer accent-primary" />
              <label for="filter-cat-5" class="text-neutral-600 font-medium text-sm cursor-pointer">Fitness</label>
            </li>
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
          <div
            class="flex px-3 py-2 rounded-sm border border-neutral-300 bg-neutral-50 focus-within:bg-white overflow-hidden">
            <input type="text" placeholder="Search brand"
              class="input w-full text-sm" />
            <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <ul class="mt-6 space-y-4">
            <li class="flex items-center gap-3">
              <input id="filter-brand-1" type="checkbox" class="w-4 h-4 cursor-pointer accent-primary" />
              <label for="filter-brand-1" class="text-neutral-600 font-medium text-sm cursor-pointer">Brand A</label>
            </li>
            <li class="flex items-center gap-3">
              <input id="filter-brand-2" type="checkbox" class="w-4 h-4 cursor-pointer accent-primary" />
              <label for="filter-brand-2" class="text-neutral-600 font-medium text-sm cursor-pointer">Brand B</label>
            </li>
            <li class="flex items-center gap-3">
              <input id="filter-brand-3" type="checkbox" class="w-4 h-4 cursor-pointer accent-primary" />
              <label for="filter-brand-3" class="text-neutral-600 font-medium text-sm cursor-pointer">Brand C</label>
            </li>
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
          <div class="flex flex-wrap gap-3">
            <button type="button"
              class="cursor-pointer border border-neutral-300 hover:border-primary rounded-md text-[13px] text-neutral-600 font-medium py-1 px-1 min-w-14">XS</button>
            <button type="button"
              class="cursor-pointer border border-neutral-300 hover:border-primary rounded-md text-[13px] text-neutral-600 font-medium py-1 px-1 min-w-14">S</button>
            <button type="button"
              class="cursor-pointer border border-neutral-300 hover:border-primary rounded-md text-[13px] text-neutral-600 font-medium py-1 px-1 min-w-14">M</button>
            <button type="button"
              class="cursor-pointer border border-neutral-300 hover:border-primary rounded-md text-[13px] text-neutral-600 font-medium py-1 px-1 min-w-14">L</button>
            <button type="button"
              class="cursor-pointer border border-neutral-300 hover:border-primary rounded-md text-[13px] text-neutral-600 font-medium py-1 px-1 min-w-14">XL</button>
            <button type="button"
              class="cursor-pointer border border-neutral-300 hover:border-primary rounded-md text-[13px] text-neutral-600 font-medium py-1 px-1 min-w-14">XXL</button>
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
          <div class="flex flex-wrap gap-3">
            <button type="button"
              class="cursor-pointer rounded-full text-[13px] text-white font-medium bg-info w-8 h-8 hover:scale-[1.05] transition-all"></button>
            <button type="button"
              class="cursor-pointer rounded-full text-[13px] text-white font-medium bg-primary w-8 h-8 hover:scale-[1.05] transition-all"></button>
            <button type="button"
              class="cursor-pointer rounded-full text-[13px] text-white font-medium bg-accent w-8 h-8 hover:scale-[1.05] transition-all"></button>
            <button type="button"
              class="cursor-pointer rounded-full text-[13px] text-white font-medium bg-warning w-8 h-8 hover:scale-[1.05] transition-all"></button>
            <button type="button"
              class="cursor-pointer rounded-full text-[13px] text-white font-medium bg-error w-8 h-8 hover:scale-[1.05] transition-all"></button>
            <button type="button"
              class="cursor-pointer rounded-full text-[13px] text-white font-medium bg-success w-8 h-8 hover:scale-[1.05] transition-all"></button>
            <button type="button"
              class="cursor-pointer rounded-full text-[13px] text-white font-medium bg-neutral-900 w-8 h-8 hover:scale-[1.05] transition-all"></button>
            <button type="button"
              class="cursor-pointer rounded-full text-[13px] text-white font-medium bg-neutral-700 w-8 h-8 hover:scale-[1.05] transition-all"></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.filter-options .header').forEach(header => {
  const content = header.parentElement.querySelector('.collape-content');
  // add height to expanded element
  if (!content.classList.contains('h-0')) {
    const fullHeight = `h-[${content.scrollHeight}px]`;
    content.classList.add(fullHeight);
  }

  header.addEventListener('click', () => {
    const arrow = header.querySelector('.arrow');
    if (content.classList.contains('h-0')) {
      // Expand
      const fullHeight = `h-[${content.scrollHeight}px]`;
      content.classList.add(fullHeight);
      content.classList.remove('h-0');
    } else {
      // Collapse
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
</script>
