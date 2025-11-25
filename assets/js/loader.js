(function () {
    // Set document title
    document.title = 'Loading...';

    // Create and append styles
    const style = document.createElement('style');
    style.textContent = `
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .loader-container {
            width: 60px;
            height: 60px;
            position: relative;
            animation: pulse 1.2s ease-in-out infinite;
        }
        .cart-icon {
            width: 100%;
            height: 100%;
            stroke: #333;
            stroke-width: 1.5;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
    `;
    document.head.appendChild(style);

    // Create loader container
    const loaderContainer = document.createElement('div');
    loaderContainer.className = 'loader-container';
    loaderContainer.id = 'loader';

    // Create SVG element
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.className = 'cart-icon';

    // Create path for cart icon
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    path.setAttribute('d', 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z');

    // Append path to SVG and SVG to container
    svg.appendChild(path);
    loaderContainer.appendChild(svg);
    document.body.appendChild(loaderContainer);

    // Function to hide loader
    window.hideLoader = function () {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'none';
        }
    };

    // Automatically hide loader when page is fully loaded
    window.onload = function () {
        window.hideLoader();
    };
})();