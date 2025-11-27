<?php
/**
 * Product Filter Sidebar Component
 * 
 * Reusable filter sidebar for products listing pages
 * Uses Tailwind theme classes for consistent styling
 */
?>

<div class="bg-neutral-50 w-full max-w-[280px] border-r border-neutral-200 shrink-0 px-6 sm:px-8 py-6">
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
        <svg xmlns="http://www.w3.org/2000/svg"
          class="arrow w-[14px] h-[14px] fill-foreground transition-all duration-300 -rotate-90"
          viewBox="0 0 492.004 492.004">
          <path
            d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z"
            data-original="#000000" />
        </svg>
      </div>
      <div class="collape-content overflow-hidden transition-all duration-300">
        <div class="mt-4">
          <div
            class="flex px-3 py-2 rounded-sm border border-neutral-300 bg-neutral-50 focus-within:bg-white overflow-hidden">
            <input type="text" placeholder="Search category"
              class="input w-full text-sm" />
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192.904 192.904" class="w-3 fill-neutral-600">
              <path
                d="m190.707 180.101-47.078-47.077c11.702-14.072 18.752-32.142 18.752-51.831C162.381 36.423 125.959 0 81.191 0 36.422 0 0 36.423 0 81.193c0 44.767 36.422 81.187 81.191 81.187 19.688 0 37.759-7.049 51.831-18.751l47.079 47.078a7.474 7.474 0 0 0 5.303 2.197 7.498 7.498 0 0 0 5.303-12.803zM15 81.193C15 44.694 44.693 15 81.191 15c36.497 0 66.189 29.694 66.189 66.193 0 36.496-29.692 66.187-66.189 66.187C44.693 147.38 15 117.689 15 81.193z">
              </path>
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
        <svg xmlns="http://www.w3.org/2000/svg"
          class="arrow w-[14px] h-[14px] fill-foreground transition-all duration-300 rotate-90"
          viewBox="0 0 492.004 492.004">
          <path
            d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z"
            data-original="#000000" />
        </svg>
      </div>
      <div class="collape-content h-0 overflow-hidden transition-all duration-300">
        <div class="mt-4">
          <div
            class="flex px-3 py-2 rounded-sm border border-neutral-300 bg-neutral-50 focus-within:bg-white overflow-hidden">
            <input type="text" placeholder="Search brand"
              class="input w-full text-sm" />
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192.904 192.904" class="w-3 fill-neutral-600">
              <path
                d="m190.707 180.101-47.078-47.077c11.702-14.072 18.752-32.142 18.752-51.831C162.381 36.423 125.959 0 81.191 0 36.422 0 0 36.423 0 81.193c0 44.767 36.422 81.187 81.191 81.187 19.688 0 37.759-7.049 51.831-18.751l47.079 47.078a7.474 7.474 0 0 0 5.303 2.197 7.498 7.498 0 0 0 5.303-12.803zM15 81.193C15 44.694 44.693 15 81.191 15c36.497 0 66.189 29.694 66.189 66.193 0 36.496-29.692 66.187-66.189 66.187C44.693 147.38 15 117.689 15 81.193z">
              </path>
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
        <svg xmlns="http://www.w3.org/2000/svg"
          class="arrow w-[14px] h-[14px] fill-foreground transition-all duration-300 rotate-90"
          viewBox="0 0 492.004 492.004">
          <path
            d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z"
            data-original="#000000" />
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
        <svg xmlns="http://www.w3.org/2000/svg"
          class="arrow w-[14px] h-[14px] fill-foreground transition-all duration-300 rotate-90"
          viewBox="0 0 492.004 492.004">
          <path
            d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z"
            data-original="#000000" />
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

    arrow.classList.toggle('-rotate-90');
    arrow.classList.toggle('rotate-90');
  });
});
</script>
