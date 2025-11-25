<?php

namespace App\Helpers;

class SeoHelper
{
    /**
     * Generate SEO meta tags for different page types
     */
    public static function generateMetaTags($type, $data = [])
    {
        $baseUrl = URLROOT;
        $siteName = 'NutriNexus';
        $defaultDescription = 'Discover premium supplements, health products, and wellness solutions at NutriNexus. Quality products for a healthier lifestyle with fast delivery across Nepal.';
        
        switch ($type) {
            case 'home':
                return [
                    'title' => 'NutriNexus - Premium Supplements & Health Products | Nepal\'s Leading Health Store',
                    'description' => 'Shop premium supplements, vitamins, protein powders, and health products at NutriNexus. Fast delivery across Nepal. Quality guaranteed. Start your wellness journey today!',
                    'keywords' => 'supplements Nepal, health products, vitamins, protein powder, fitness supplements, wellness products, online health store, premium supplements, nutrition, health and wellness',
                    'og_image' => $baseUrl . '/public/images/og-home.jpg'
                ];
                
            case 'product':
                $product = $data['product'] ?? [];
                $productName = $product['name'] ?? 'Product';
                $productDescription = $product['short_description'] ?? $product['description'] ?? '';
                $productPrice = $product['price'] ?? '';
                $productImage = $product['image_url'] ?? $baseUrl . '/public/images/products/default.jpg';
                
                return [
                    'title' => $productName . ' - Premium Quality | NutriNexus',
                    'description' => 'Buy ' . $productName . ' online at NutriNexus. ' . strip_tags($productDescription) . ' Fast delivery across Nepal. Premium quality guaranteed.',
                    'keywords' => strtolower($productName) . ', supplements, health products, ' . ($product['category_name'] ?? 'wellness') . ', premium quality, Nepal delivery',
                    'og_image' => $productImage,
                    'structured_data' => self::generateProductStructuredData($product)
                ];
                
            case 'category':
                $category = $data['category'] ?? [];
                $categoryName = $category['name'] ?? 'Category';
                $categoryDescription = $category['description'] ?? '';
                
                return [
                    'title' => $categoryName . ' - Premium ' . $categoryName . ' Products | NutriNexus',
                    'description' => 'Explore our premium ' . strtolower($categoryName) . ' collection at NutriNexus. ' . strip_tags($categoryDescription) . ' Quality products with fast delivery across Nepal.',
                    'keywords' => strtolower($categoryName) . ', supplements, health products, wellness, premium quality, Nepal delivery, ' . $categoryName . ' collection',
                    'og_image' => $baseUrl . '/public/images/categories/' . ($category['slug'] ?? 'default') . '.jpg'
                ];
                
            case 'blog':
                $post = $data['post'] ?? [];
                $postTitle = $post['title'] ?? 'Blog Post';
                $postExcerpt = $post['excerpt'] ?? $post['content'] ?? '';
                
                return [
                    'title' => $postTitle . ' | NutriNexus Health Blog',
                    'description' => strip_tags($postExcerpt) . ' Read more health and wellness tips on NutriNexus blog.',
                    'keywords' => 'health blog, wellness tips, nutrition advice, health articles, ' . strtolower($postTitle),
                    'og_image' => $baseUrl . '/public/images/blog/' . ($post['featured_image'] ?? 'default.jpg')
                ];
                
            case 'cart':
                return [
                    'title' => 'Shopping Cart - Your Health Products | NutriNexus',
                    'description' => 'Review your selected health products and supplements. Complete your order with fast delivery across Nepal.',
                    'keywords' => 'shopping cart, health products, supplements, checkout, online shopping Nepal',
                    'og_image' => $baseUrl . '/public/images/og-cart.jpg'
                ];
                
            case 'checkout':
                return [
                    'title' => 'Checkout - Complete Your Order | NutriNexus',
                    'description' => 'Complete your health product order with secure checkout. Fast delivery across Nepal. Premium quality guaranteed.',
                    'keywords' => 'checkout, order health products, secure payment, Nepal delivery, supplements order',
                    'og_image' => $baseUrl . '/public/images/og-checkout.jpg'
                ];
                
            case 'user_account':
                return [
                    'title' => 'My Account - Manage Your Health Journey | NutriNexus',
                    'description' => 'Manage your account, track orders, and view your health product history at NutriNexus.',
                    'keywords' => 'my account, order history, health products, user dashboard, NutriNexus account',
                    'og_image' => $baseUrl . '/public/images/og-account.jpg'
                ];
                
            default:
                return [
                    'title' => $data['title'] ?? 'NutriNexus - Premium Supplements & Health Products',
                    'description' => $data['description'] ?? $defaultDescription,
                    'keywords' => $data['keywords'] ?? 'supplements, health products, wellness, vitamins, protein, fitness, nutrition, Nepal, online shopping, premium quality',
                    'og_image' => $data['og_image'] ?? $baseUrl . '/public/images/og-default.jpg'
                ];
        }
    }
    
    /**
     * Generate structured data for products
     */
    private static function generateProductStructuredData($product)
    {
        $baseUrl = URLROOT;
        $productName = $product['name'] ?? 'Product';
        $productDescription = $product['description'] ?? '';
        $productPrice = $product['price'] ?? 0;
        $productImage = $product['image_url'] ?? $baseUrl . '/public/images/products/default.jpg';
        $productSku = $product['sku'] ?? '';
        $productCategory = $product['category_name'] ?? 'Supplements';
        
        return [
            "@context" => "https://schema.org",
            "@type" => "Product",
            "name" => $productName,
            "description" => strip_tags($productDescription),
            "image" => $productImage,
            "sku" => $productSku,
            "category" => $productCategory,
            "brand" => [
                "@type" => "Brand",
                "name" => "NutriNexus"
            ],
            "offers" => [
                "@type" => "Offer",
                "price" => $productPrice,
                "priceCurrency" => "NPR",
                "availability" => "https://schema.org/InStock",
                "seller" => [
                    "@type" => "Organization",
                    "name" => "NutriNexus"
                ]
            ]
        ];
    }
    
    /**
     * Generate breadcrumb structured data
     */
    public static function generateBreadcrumbStructuredData($breadcrumbs)
    {
        $items = [];
        $position = 1;
        
        foreach ($breadcrumbs as $breadcrumb) {
            $items[] = [
                "@type" => "ListItem",
                "position" => $position,
                "name" => $breadcrumb['name'],
                "item" => $breadcrumb['url']
            ];
            $position++;
        }
        
        return [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => $items
        ];
    }
    
    /**
     * Generate FAQ structured data
     */
    public static function generateFAQStructuredData($faqs)
    {
        $items = [];
        
        foreach ($faqs as $faq) {
            $items[] = [
                "@type" => "Question",
                "name" => $faq['question'],
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => $faq['answer']
                ]
            ];
        }
        
        return [
            "@context" => "https://schema.org",
            "@type" => "FAQPage",
            "mainEntity" => $items
        ];
    }
    
    /**
     * Clean and optimize text for SEO
     */
    public static function optimizeText($text, $maxLength = 160)
    {
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim
        $text = trim($text);
        
        // Truncate if too long
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength - 3) . '...';
        }
        
        return $text;
    }
    
    /**
     * Generate sitemap data
     */
    public static function generateSitemapData()
    {
        $baseUrl = URLROOT;
        $sitemap = [];
        
        // Home page
        $sitemap[] = [
            'url' => $baseUrl,
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => '1.0'
        ];
        
        // Categories
        $sitemap[] = [
            'url' => $baseUrl . '/categories',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'weekly',
            'priority' => '0.8'
        ];
        
        // Products page
        $sitemap[] = [
            'url' => $baseUrl . '/products',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => '0.9'
        ];
        
        return $sitemap;
    }
}
