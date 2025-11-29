<?php
/**
 * App-style auto swipe slider
 */

if (!isset($sliders) || !is_array($sliders) || count($sliders) === 0) {
    $sliders = [[
        'title' => 'Limited time offer',
        'subtitle' => '20% Off All Proteins',
        'description' => 'Clean nutrition, same day dispatch.',
        'image_url' => \App\Core\View::asset('images/banners/default-hero.jpg'),
        'link_url' => \App\Core\View::url('products')
    ]];
}

$normalizeMedia = static function ($url) {
    if (empty($url)) {
        return \App\Core\View::asset('images/banners/default-hero.jpg');
    }

    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }

    if (strpos($url, 'uploads/') === 0) {
        return \App\Core\View::asset($url);
    }

    if ($url[0] === '/') {
        return \App\Core\View::asset(ltrim($url, '/'));
    }

return \App\Core\View::asset('uploads/images/' . $url);
};

$preparedSlides = [];
foreach ($sliders as $index => $slider) {
    $imageUrl = $normalizeMedia($slider['image_url'] ?? '');
    $altText = trim(($slider['subtitle'] ?? '') . ' ' . ($slider['title'] ?? '')) ?: 'Hero Banner';
    $preparedSlides[] = [
        'image' => $imageUrl,
        'alt' => $altText,
        'link' => !empty($slider['link_url']) ? $slider['link_url'] : null,
    ];
}

foreach ($preparedSlides as $slideMeta) {
    echo '<link rel="preload" as="image" href="' . htmlspecialchars($slideMeta['image']) . '" />';
}
?>

<div class="app-hero" style="background: transparent !important;">
    <div class="app-hero__viewport" id="app-hero">
        <?php foreach ($preparedSlides as $index => $slide): ?>
            <?php $imageTag = '<img src="' . htmlspecialchars($slide['image']) . '" alt="' . htmlspecialchars($slide['alt']) . '" loading="' . ($index === 0 ? 'eager' : 'lazy') . '" decoding="async" fetchpriority="' . ($index === 0 ? 'high' : 'low') . '">'; ?>
            <article class="app-hero__card<?= $index === 0 ? ' is-active' : '' ?>">
                <?php if (!empty($slide['link'])): ?>
                    <a href="<?= htmlspecialchars($slide['link']) ?>" class="app-hero__media-link" aria-label="<?= htmlspecialchars($slide['alt']) ?>">
                        <?= $imageTag ?>
                    </a>
                <?php else: ?>
                    <div class="app-hero__media"><?= $imageTag ?></div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (count($preparedSlides) > 1): ?>
        <div class="app-hero__dots" id="app-hero-dots">
            <?php foreach ($preparedSlides as $index => $_): ?>
                <button type="button"
                        class="app-hero__dot<?= $index === 0 ? ' is-active' : '' ?>"
                        aria-label="Go to slide <?= $index + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
(function() {
    'use strict';
    
    // Reset slider position on page load/visibility change
    function resetSliderPosition() {
        const viewport = document.getElementById('app-hero');
        if (viewport) {
            // Force reset to first slide
            viewport.scrollLeft = 0;
        }
    }
    
    // Reset on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', resetSliderPosition);
    } else {
        resetSliderPosition();
    }
    
    // Reset when page becomes visible (returning from another page)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            setTimeout(resetSliderPosition, 100);
        }
    });
    
    // Reset on pageshow (back/forward navigation)
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            setTimeout(resetSliderPosition, 100);
        }
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    const viewport = document.getElementById('app-hero');
    if (!viewport) {
        return;
    }

    // Reset scroll position immediately
    viewport.scrollLeft = 0;

    const cards = Array.from(viewport.querySelectorAll('.app-hero__card'));
    const dots = Array.from(document.querySelectorAll('#app-hero-dots .app-hero__dot'));
    const autoDelay = 4500;
    let activeIndex = 0;
    let autoTimer = null;
    let scrollTimeout = null;

    function setActive(index, shouldScroll = true) {
        if (!cards.length) {
            return;
        }

        activeIndex = index % cards.length;
        if (activeIndex < 0) {
            activeIndex = cards.length - 1;
        }

        cards.forEach((card, idx) => card.classList.toggle('is-active', idx === activeIndex));
        dots.forEach((dot, idx) => dot.classList.toggle('is-active', idx === activeIndex));

        if (shouldScroll) {
            isScrolling = true;
            const target = cards[activeIndex];
            viewport.scrollTo({
                left: target.offsetLeft - parseInt(getComputedStyle(viewport).paddingLeft || '0', 10),
                behavior: 'smooth'
            });
            setTimeout(() => { isScrolling = false; }, 500);
        }
    }

    function nextSlide() {
        setActive(activeIndex + 1);
    }

    function startAuto() {
        if (cards.length <= 1) {
            return;
        }
        stopAuto();
        autoTimer = setInterval(nextSlide, autoDelay);
    }

    function stopAuto() {
        if (autoTimer) {
            clearInterval(autoTimer);
            autoTimer = null;
        }
    }

    function resumeAuto() {
        stopAuto();
        autoTimer = setTimeout(startAuto, autoDelay);
    }

    let isScrolling = false;
    let isUserScrolling = false;

    viewport.addEventListener('scroll', () => {
        // Prevent infinite scroll loops
        if (isScrolling) {
            return;
        }
        
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }
        scrollTimeout = setTimeout(() => {
            let closestIndex = activeIndex;
            let minDistance = Number.MAX_VALUE;
            cards.forEach((card, index) => {
                const distance = Math.abs(card.offsetLeft - viewport.scrollLeft);
                if (distance < minDistance) {
                    closestIndex = index;
                    minDistance = distance;
                }
            });
            
            // Only update if index actually changed and user is scrolling
            if (closestIndex !== activeIndex && isUserScrolling) {
                isScrolling = true;
            setActive(closestIndex, false);
                setTimeout(() => { isScrolling = false; }, 100);
            }
        }, 150);
    }, { passive: true });

    ['touchstart', 'mousedown', 'wheel'].forEach(evt => {
        viewport.addEventListener(evt, () => {
            isUserScrolling = true;
            stopAuto();
        }, { passive: true });
    });

    ['touchend', 'mouseup'].forEach(evt => {
        viewport.addEventListener(evt, () => {
            setTimeout(() => {
                isUserScrolling = false;
                resumeAuto();
            }, 300);
        }, { passive: true });
    });

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            stopAuto();
            setActive(index);
            resumeAuto();
        });
    });

    // Ensure we start at slide 0
    viewport.scrollLeft = 0;
    setActive(0, false);
    
    // Small delay to ensure layout is ready
    setTimeout(() => {
        viewport.scrollLeft = 0;
        startAuto();
    }, 100);
});
</script>

<style>
.app-hero {
    -webkit-overflow-scrolling: touch;
    background: transparent !important;
    margin: 10px;
}

.app-hero__viewport {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    padding: 0.5rem 0.25rem;
    scroll-behavior: smooth;
    background: transparent !important;
}

.app-hero__viewport::-webkit-scrollbar {
    display: none;
}

.app-hero__card {
    flex: 0 0 92%;
    max-width: 92%;
    position: relative;
    border-radius: 18px;
    overflow: hidden;
    min-height: 140px;
    scroll-snap-align: start;
    background: #ffffff;
    color: inherit;
    transition: transform 0.4s ease, box-shadow 0.4s ease;
    width: 92%;
}

.app-hero__card.is-active {
    transform: translateY(-3px);
}

.app-hero__media,
.app-hero__media-link {
    display: block;
    position: relative;
    width: 100%;
    height: 100%;
    background: #ffffff;
}

.app-hero__media img,
.app-hero__media-link img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    background: #ffffff;
}

.app-hero__dots {
    display: flex;
    justify-content: center;
    gap: 0.4rem;
    margin-top: 0.7rem;
}

.app-hero__dot {
    width: 6px;
    height: 6px;
    border-radius: 999px;
    border: none;
    transition: width 0.25s ease, background 0.25s ease;
}

.app-hero__dot.is-active {
    width: 18px;
}

@media (min-width: 640px) {
    .app-hero__card {
        flex-basis: 60%;
        max-width: 60%;
        min-height: 230px;
    }
}

@media (min-width: 1024px) {
    .app-hero__card {
        flex-basis: 46%;
        max-width: 46%;
        min-height: 260px;
    }
}
</style>
