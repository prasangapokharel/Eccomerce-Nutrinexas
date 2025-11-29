(function () {
    'use strict';

    let cachedLoader = null;

    function getLoaderElement() {
        if (cachedLoader && document.body.contains(cachedLoader)) {
            return cachedLoader;
        }
        cachedLoader = document.getElementById('global-page-loader');
        return cachedLoader;
    }

    function show() {
        const el = getLoaderElement();
        if (!el) return;
        el.style.display = 'flex';
    }

    function hide() {
        const el = getLoaderElement();
        if (!el) return;
        el.style.display = 'none';
    }

    // Expose minimal API
    window.AppLoader = {
        show,
        hide
    };

    // Hide loader automatically once page fully loaded
    window.addEventListener('load', function () {
        hide();
    });
})();