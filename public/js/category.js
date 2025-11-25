// category.js - Simple Fixed Category Grid Component
class CategoryGrid {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        this.options = {
            categories: options.categories || [],
            columns: 3, // Fixed 3 columns
            imageSize: options.imageSize || '48px',
            baseUrl: options.baseUrl || '/products/category/',
            ...options
        };
        
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.error('Category container not found');
            return;
        }
        
        if (this.options.categories.length === 0) {
            console.warn('No categories provided');
            return;
        }
        
        this.createHTML();
        this.setupStyles();
    }
    
    createHTML() {
        const categoriesHTML = this.options.categories.map((category, index) => {
            const categoryData = typeof category === 'string' ? { name: category } : category;
            const { name, image, url, badge } = categoryData;
            
            return `
                <div class="category-item">
                    <a href="${url || (this.options.baseUrl + encodeURIComponent(name))}" 
                       class="category-link" 
                       data-category="${name}">
                        <div class="category-image-container">
                            <img src="${image || this.getDefaultImage(name)}" 
                                 alt="${name}" 
                                 class="category-image"
                                 loading="lazy"
                                 onerror="this.src='${this.getFallbackImage()}'">
                            ${badge ? `<span class="category-badge">${badge}</span>` : ''}
                        </div>
                        <span class="category-name">${name}</span>
                    </a>
                </div>
            `;
        }).join('');
        
        this.container.innerHTML = `
            <div class="category-grid-wrapper">
                <div class="category-grid">
                    ${categoriesHTML}
                </div>
            </div>
        `;
    }
    
    setupStyles() {
        const styleId = 'category-grid-styles';
        if (!document.querySelector(`#${styleId}`)) {
            const style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                .category-grid-wrapper {
                    border-radius: 12px 12px 12px  12px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    margin-bottom: 12px;
                }
                
                .category-grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 8px;
                    padding: 12px;
                }
                
                .category-item {
                    display: flex;
                    justify-content: center;
                }
                
                .category-link {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-decoration: none;
                    text-align: center;
                    padding: 8px 4px;
                    border-radius: 6px;
                    width: 100%;
                    max-width: 80px;
                }
                
                .category-image-container {
                    position: relative;
                    width: ${this.options.imageSize};
                    height: ${this.options.imageSize};
                    margin-bottom: 6px;
                    background: #f8fafc;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                    flex-shrink: 0;
                }
                
                .category-image {
                    width: 32px;
                    height: 32px;
                    object-fit: cover;
                    border-radius: 4px;
                }
                
                .category-badge {
                    position: absolute;
                    top: -2px;
                    right: -2px;
                    background: #ef4444;
                    color: white;
                    font-size: 8px;
                    font-weight: 600;
                    padding: 1px 4px;
                    border-radius: 6px;
                    border: 1px solid white;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
                }
                
                .category-name {
                    font-size: 8px;
                    font-weight: 500;
                    color: #374151;
                    line-height: 1.2;
                    text-align: center;
                    display: block;
                    word-break: break-word;
                    max-width: 100%;
                }
                
                /* Mobile optimization */
                @media (max-width: 480px) {
                    .category-grid {
                        grid-template-columns: repeat(3, 1fr);
                        gap: 5px;
                        padding: 5px;
                    }
                    
                    .category-link {
                        padding: 6px 2px;
                        max-width: 70px;
                    }
                    
                    .category-image-container {
                        width: 40px;
                        height: 40px;
                        margin-bottom: 4px;
                    }
                    
                    .category-image {
                        width: 28px;
                        height: 28px;
                    }
                    
                    .category-name {
                        font-size: 7px;
                    }
                    
                    .category-badge {
                        font-size: 7px;
                        padding: 1px 3px;
                    }
                }
                
                /* Tablet optimization */
                @media (min-width: 481px) and (max-width: 768px) {
                    .category-grid {
                        gap: 10px;
                        padding: 14px;
                    }
                    
                    .category-link {
                        max-width: 85px;
                    }
                    
                    .category-name {
                        font-size: 9px;
                    }
                }
                
                /* Desktop optimization */
                @media (min-width: 769px) {
                    .category-grid {
                        gap: 12px;
                        padding: 16px;
                    }
                    
                    .category-link {
                        max-width: 90px;
                    }
                    
                    .category-name {
                        font-size: 9px;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    getDefaultImage(categoryName) {
        const defaultImages = {
            'Protein': 'https://images.unsplash.com/photo-1594737625785-a6cbdabd333c?w=50&h=50&fit=crop',
            'Vitamins': 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=50&h=50&fit=crop',
            'Pre-Workout': 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=50&h=50&fit=crop',
            'Mass Gainer': 'https://images.unsplash.com/photo-1594737625785-a6cbdabd333c?w=50&h=50&fit=crop',
            'Creatine': 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=50&h=50&fit=crop',
            'BCAA': 'https://images.unsplash.com/photo-1594737625785-a6cbdabd333c?w=50&h=50&fit=crop',
            'Fat Burner': 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=50&h=50&fit=crop',
            'Multivitamin': 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=50&h=50&fit=crop'
        };
        
        return defaultImages[categoryName] || this.getFallbackImage();
    }
    
    getFallbackImage() {
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xMiAxMkgyOFYyOEgxMlYxMloiIGZpbGw9IiNEMUQ1REIiLz4KPC9zdmc+';
    }
    
    // Public API methods
    updateCategories(newCategories) {
        this.options.categories = newCategories;
        this.createHTML();
    }
    
    addCategory(category) {
        this.options.categories.push(category);
        this.updateCategories(this.options.categories);
    }
    
    removeCategory(categoryName) {
        this.options.categories = this.options.categories.filter(cat => 
            (typeof cat === 'string' ? cat : cat.name) !== categoryName
        );
        this.updateCategories(this.options.categories);
    }
    
    destroy() {
        this.container.innerHTML = '';
        
        // Clean up styles if no other instances
        const otherInstances = document.querySelectorAll('.category-grid-wrapper');
        if (otherInstances.length === 0) {
            const style = document.querySelector('#category-grid-styles');
            if (style) {
                style.remove();
            }
        }
    }
}

// Utility function for easy initialization
function createCategoryGrid(containerId, categories, options = {}) {
    const container = typeof containerId === 'string' ? document.querySelector(containerId) : containerId;
    
    if (!container) {
        console.error(`Category container "${containerId}" not found`);
        return null;
    }
    
    if (!categories || categories.length === 0) {
        console.error('No categories provided');
        return null;
    }
    
    return new CategoryGrid(container, {
        categories: categories,
        ...options
    });
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CategoryGrid, createCategoryGrid };
}

// Auto-initialize with data attributes
document.addEventListener('DOMContentLoaded', () => {
    const categoryContainers = document.querySelectorAll('[data-category-grid]');
    categoryContainers.forEach(container => {
        const categories = container.dataset.categories ? JSON.parse(container.dataset.categories) : [];
        
        if (categories.length > 0) {
            new CategoryGrid(container, { categories });
        }
    });
});

/*
USAGE EXAMPLES:

1. Basic HTML Setup:
<div id="category-grid"></div>

2. JavaScript Initialization:
const categories = [
    { name: 'Protein', image: 'protein.jpg' },
    { name: 'Vitamins', image: 'vitamins.jpg' },
    { name: 'Pre-Workout', image: 'preworkout.jpg', badge: 'New' },
    'Mass Gainer', // Simple string format
    'Creatine',
    'BCAA'
];

const categoryGrid = createCategoryGrid('#category-grid', categories);

3. HTML Data Attributes (Auto-initialize):
<div data-category-grid
     data-categories='[{"name":"Protein","image":"protein.jpg"},{"name":"Vitamins","image":"vitamins.jpg"}]'>
</div>

4. Dynamic Updates:
categoryGrid.addCategory({ name: 'New Category', image: 'new.jpg' });
categoryGrid.removeCategory('Old Category');
categoryGrid.updateCategories(newCategoriesArray);
*/