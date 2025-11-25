# 🎉 Splash Screen Implementation Guide

## Overview
This splash screen system displays your PAYDAY JHATKA image (`/images/splash/1.png`) to fit the entire screen perfectly on all devices.

## 📁 Files Created

1. **`splash-screen.js`** - Main splash screen class with full functionality
2. **`splash-integration.js`** - Simple integration script for easy implementation
3. **`splash-demo.html`** - Demo page to test the splash screen
4. **`README-splash.md`** - This documentation file

## 🚀 Quick Start

### Option 1: Simple Integration (Recommended)
```html
<!-- Add this to your HTML head or before closing body tag -->
<script src="/js/splash-integration.js"></script>
<script>
    // Initialize splash screen with default settings
    initSplashScreen();
</script>
```

### Option 2: Full Class Implementation
```html
<!-- Add this to your HTML head or before closing body tag -->
<script src="/js/splash-screen.js"></script>
<!-- The splash screen will automatically show on page load -->
```

## ⚙️ Configuration Options

### Basic Configuration
```javascript
initSplashScreen({
    autoShow: true,           // Show automatically on page load
    autoHideDelay: 3000,      // Hide after 3 seconds
    imagePath: '/images/splash/1.png',  // Path to your splash image
    skipButtonText: 'Skip',   // Text for skip button
    shopNowButtonText: 'Shop Now',  // Text for shop now button
    shopNowUrl: '/products'   // URL to redirect to when shop now is clicked
});
```

### Advanced Configuration with Callbacks
```javascript
initSplashScreen({
    autoShow: true,
    autoHideDelay: 5000,  // 5 seconds
    onShow: function() {
        console.log('Splash screen is now visible');
        // Add any custom logic when splash shows
    },
    onHide: function() {
        console.log('Splash screen is now hidden');
        // Add any custom logic when splash hides
    }
});
```

## 🎯 Manual Control

### Show Splash Screen
```javascript
showSplashScreen();
```

### Hide Splash Screen
```javascript
hideSplashScreen();
```

### Check Status
```javascript
// Check if splash screen is currently visible
const splashElement = document.getElementById('splash-screen');
if (splashElement && splashElement.style.display !== 'none') {
    console.log('Splash screen is visible');
}
```

## 📱 Features

### ✨ Core Features
- **Full Screen Coverage**: Image fits entire screen perfectly
- **Responsive Design**: Works on all device sizes and orientations
- **Auto-hide**: Disappears after configurable delay
- **Skip Button**: Top-right corner for manual skip
- **Shop Now Button**: Bottom center for action
- **Smooth Animations**: Fade in/out effects

### 🎮 User Controls
- **Skip Button**: Click to dismiss immediately
- **Shop Now Button**: Click to go to products page
- **Keyboard Shortcut**: Press ESC key to skip
- **Touch Friendly**: Optimized for mobile devices

### 🔧 Technical Features
- **Image Optimization**: Automatically adjusts image size to fit screen
- **Aspect Ratio Handling**: Maintains image proportions
- **Z-index Management**: Ensures splash appears above all content
- **Event System**: Customizable callbacks for show/hide events

## 🎨 Customization

### Custom Image
```javascript
initSplashScreen({
    imagePath: '/images/your-custom-splash.png'
});
```

### Custom Button Text
```javascript
initSplashScreen({
    skipButtonText: 'Close',
    shopNowButtonText: 'Get Started'
});
```

### Custom Redirect URL
```javascript
initSplashScreen({
    shopNowUrl: '/home'  // Redirect to home page instead
});
```

### Custom Timing
```javascript
initSplashScreen({
    autoHideDelay: 10000  // Show for 10 seconds
});
```

## 📱 Mobile Optimization

### Responsive Design
- Automatically adjusts for different screen sizes
- Touch-friendly button sizes
- Optimized for both portrait and landscape modes
- Handles mobile browser quirks

### Performance
- Lightweight implementation
- Smooth animations
- Efficient image loading
- Minimal memory footprint

## 🔧 Integration Examples

### For Homepage
```html
<script src="/js/splash-integration.js"></script>
<script>
    // Show splash screen on homepage
    initSplashScreen({
        autoShow: true,
        autoHideDelay: 4000,
        shopNowUrl: '/products'
    });
</script>
```


### For Product Pages
```html
<script src="/js/splash-integration.js"></script>
<script>
    // Show splash screen on product pages
    initSplashScreen({
        autoShow: true,
        autoHideDelay: 3000,
        shopNowUrl: '/cart'
    });
</script>
```

### Conditional Display
```html
<script src="/js/splash-integration.js"></script>
<script>
    // Only show splash screen for new visitors
    if (!localStorage.getItem('splashShown')) {
        initSplashScreen({
            autoShow: true,
            autoHideDelay: 3000
        });
        localStorage.setItem('splashShown', 'true');
    }
</script>
```

## 🐛 Troubleshooting

### Common Issues

1. **Image Not Loading**
   - Check if `/images/splash/1.png` exists
   - Verify file permissions
   - Check browser console for errors

2. **Splash Screen Not Showing**
   - Ensure script is loaded after DOM is ready
   - Check if `autoShow` is set to `true`
   - Verify no CSS conflicts

3. **Buttons Not Working**
   - Check if JavaScript is enabled
   - Verify no JavaScript errors in console
   - Ensure proper event handling

### Debug Mode
```javascript
// Enable debug logging
initSplashScreen({
    autoShow: true,
    onShow: function() {
        console.log('Splash screen shown successfully');
    },
    onHide: function() {
        console.log('Splash screen hidden successfully');
    }
});
```

## 📋 Browser Support

- ✅ Chrome (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)
- ✅ Older browsers (IE11+ with polyfills)

## 🎯 Best Practices

1. **Image Size**: Use optimized images (recommended: 1920x1080 or similar)
2. **Loading**: Load splash screen after main content is ready
3. **Timing**: Don't show splash screen for too long (3-5 seconds recommended)
4. **Accessibility**: Ensure skip button is easily accessible
5. **Performance**: Test on various devices and connection speeds

## 🚀 Advanced Usage

### Custom Events
```javascript
// Listen for splash screen events
document.addEventListener('splashShown', function() {
    console.log('Splash screen event triggered');
});

// Custom splash screen behavior
initSplashScreen({
    onShow: function() {
        // Pause background music
        // Show loading indicators
        // Track analytics
    },
    onHide: function() {
        // Resume background music
        // Hide loading indicators
        // Track user engagement
    }
});
```

### Multiple Splash Screens
```javascript
// Show different splash screens for different pages
const pageType = window.location.pathname.includes('products') ? 'product' : 'home';

if (pageType === 'product') {
    initSplashScreen({
        imagePath: '/images/splash/product-splash.png',
        shopNowUrl: '/cart'
    });
} else {
    initSplashScreen({
        imagePath: '/images/splash/home-splash.png',
        shopNowUrl: '/products'
    });
}
```

## 📞 Support

If you encounter any issues or need customization:
1. Check the browser console for errors
2. Verify all files are properly loaded
3. Test with the demo page (`splash-demo.html`)
4. Check image file paths and permissions

---

**Happy Splashing! 🎉**
