<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Product;
use App\Models\Blog;
use App\Core\View;

class SeoController extends Controller
{
    private $productModel;
    private $blogModel;
    
    public function __construct()
    {
        parent::__construct();
        
        // Define PROJECT_ROOT constant if not already defined
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(dirname(__DIR__)));
        }
        
        $this->productModel = new Product();
        $this->blogModel = new Blog();
    }

    /**
     * Serve product sitemap XML
     */
    public function productSitemap()
    {
        try {
            $filePath = PROJECT_ROOT . '/product-sitemap.xml';
            if (!file_exists($filePath)) {
                $this->generateProductSitemap();
            }
            header('Content-Type: application/xml');
            readfile($filePath);
        } catch (\Exception $e) {
            error_log('Error serving product sitemap: ' . $e->getMessage());
            $this->redirect('500');
        }
    }

    /**
     * Serve blog sitemap XML
     */
    public function blogSitemap()
    {
        try {
            $filePath = PROJECT_ROOT . '/blog-sitemap.xml';
            if (!file_exists($filePath)) {
                $this->generateBlogSitemap();
            }
            header('Content-Type: application/xml');
            readfile($filePath);
        } catch (\Exception $e) {
            error_log('Error serving blog sitemap: ' . $e->getMessage());
            $this->redirect('500');
        }
    }

    /**
     * Serve category sitemap XML
     */
    public function categorySitemap()
    {
        try {
            $filePath = PROJECT_ROOT . '/category-sitemap.xml';
            if (!file_exists($filePath)) {
                $this->generateCategorySitemap();
            }
            header('Content-Type: application/xml');
            readfile($filePath);
        } catch (\Exception $e) {
            error_log('Error serving category sitemap: ' . $e->getMessage());
            $this->redirect('500');
        }
    }

    /**
     * Serve page sitemap XML
     */
    public function pageSitemap()
    {
        try {
            $filePath = PROJECT_ROOT . '/page-sitemap.xml';
            if (!file_exists($filePath)) {
                $this->generatePageSitemap();
            }
            header('Content-Type: application/xml');
            readfile($filePath);
        } catch (\Exception $e) {
            error_log('Error serving page sitemap: ' . $e->getMessage());
            $this->redirect('500');
        }
    }

    /**
     * Serve sitemap index XML
     */
    public function sitemapIndex()
    {
        try {
            $filePath = PROJECT_ROOT . '/sitemap.xml';
            if (!file_exists($filePath)) {
                $this->generateMainSitemap();
            }
            header('Content-Type: application/xml');
            readfile($filePath);
        } catch (\Exception $e) {
            error_log('Error serving sitemap index: ' . $e->getMessage());
            $this->redirect('500');
        }
    }

    /**
     * Serve robots.txt
     */
    public function robots()
    {
        try {
            $filePath = PROJECT_ROOT . '/robots.txt';
            header('Content-Type: text/plain');
            if (file_exists($filePath)) {
                readfile($filePath);
            } else {
                echo "User-agent: *\nAllow: /\nSitemap: " . View::url('sitemap.xml');
            }
        } catch (\Exception $e) {
            error_log('Error serving robots.txt: ' . $e->getMessage());
            $this->redirect('500');
        }
    }

    /**
     * Generate product sitemap
     */
    public function generateProductSitemap()
    {
        try {
            $products = $this->productModel->getAllActiveProducts();
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            // Add static pages
            $xml .= $this->addUrl('', '1.0', 'daily');
            $xml .= $this->addUrl('products', '0.8', 'daily');
            $xml .= $this->addUrl('about', '0.6', 'monthly');
            $xml .= $this->addUrl('contact', '0.6', 'monthly');
            $xml .= $this->addUrl('blog', '0.7', 'weekly');
            
            // Add product pages
            foreach ($products as $product) {
                $xml .= $this->addUrl(
                    'products/' . $product['slug'],
                    '0.8',
                    'weekly',
                    $product['updated_at']
                );
            }
            
            $xml .= '</urlset>';
            
            // Save to file
            $filePath = PROJECT_ROOT . '/product-sitemap.xml';
            file_put_contents($filePath, $xml);
            
            return true;
        } catch (\Exception $e) {
            error_log('Error generating product sitemap: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate blog sitemap
     */
    public function generateBlogSitemap()
    {
        try {
            $blogs = $this->blogModel->getAllActiveBlogs();
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            // Add blog index page
            $xml .= $this->addUrl('blog', '0.8', 'daily');
            
            // Add individual blog posts
            foreach ($blogs as $blog) {
                $xml .= $this->addUrl(
                    'blog/' . $blog['slug'],
                    '0.7',
                    'monthly',
                    $blog['updated_at']
                );
            }
            
            $xml .= '</urlset>';
            
            // Save to file
            $filePath = PROJECT_ROOT . '/blog-sitemap.xml';
            file_put_contents($filePath, $xml);
            
            return true;
        } catch (\Exception $e) {
            error_log('Error generating blog sitemap: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate main sitemap index
     */
    public function generateMainSitemap()
    {
        try {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            $xml .= $this->addSitemap('product-sitemap.xml', date('Y-m-d'));
            $xml .= $this->addSitemap('blog-sitemap.xml', date('Y-m-d'));
            $xml .= $this->addSitemap('category-sitemap.xml', date('Y-m-d'));
            $xml .= $this->addSitemap('page-sitemap.xml', date('Y-m-d'));
            
            $xml .= '</sitemapindex>';
            
            // Save to file
            $filePath = PROJECT_ROOT . '/sitemap.xml';
            file_put_contents($filePath, $xml);
            
            return true;
        } catch (\Exception $e) {
            error_log('Error generating main sitemap: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate category sitemap
     */
    public function generateCategorySitemap()
    {
        try {
            $categories = $this->productModel->getAllCategories();
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            foreach ($categories as $category) {
                $xml .= $this->addUrl(
                    'products/category/' . urlencode($category['category']),
                    '0.7',
                    'weekly'
                );
            }
            
            $xml .= '</urlset>';
            
            // Save to file
            $filePath = PROJECT_ROOT . '/category-sitemap.xml';
            file_put_contents($filePath, $xml);
            
            return true;
        } catch (\Exception $e) {
            error_log('Error generating category sitemap: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate page sitemap
     */
    public function generatePageSitemap()
    {
        try {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            // Add static pages
            $xml .= $this->addUrl('', '1.0', 'daily');
            $xml .= $this->addUrl('about', '0.6', 'monthly');
            $xml .= $this->addUrl('contact', '0.6', 'monthly');
            $xml .= $this->addUrl('privacy-policy', '0.5', 'yearly');
            $xml .= $this->addUrl('terms-of-service', '0.5', 'yearly');
            $xml .= $this->addUrl('faq', '0.6', 'monthly');
            $xml .= $this->addUrl('coupons', '0.7', 'daily');
            
            $xml .= '</urlset>';
            
            // Save to file
            $filePath = PROJECT_ROOT . '/page-sitemap.xml';
            file_put_contents($filePath, $xml);
            
            return true;
        } catch (\Exception $e) {
            error_log('Error generating page sitemap: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate all sitemaps
     */
    public function generateAllSitemaps()
    {
        $results = [
            'product' => $this->generateProductSitemap(),
            'blog' => $this->generateBlogSitemap(),
            'category' => $this->generateCategorySitemap(),
            'page' => $this->generatePageSitemap(),
            'main' => $this->generateMainSitemap()
        ];
        
        return $results;
    }
    
    /**
     * Remove product from sitemap when deleted
     */
    public function removeProductFromSitemap($productSlug)
    {
        try {
            $filePath = PROJECT_ROOT . '/product-sitemap.xml';
            
            if (file_exists($filePath)) {
                $xml = file_get_contents($filePath);
                
                // Remove the specific product URL
                $pattern = '/<url>\s*<loc>.*?products\/' . preg_quote($productSlug, '/') . '<\/loc>.*?<\/url>\s*/s';
                $xml = preg_replace($pattern, '', $xml);
                
                file_put_contents($filePath, $xml);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Error removing product from sitemap: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove blog from sitemap when deleted
     */
    public function removeBlogFromSitemap($blogSlug)
    {
        try {
            $filePath = PROJECT_ROOT . '/blog-sitemap.xml';
            
            if (file_exists($filePath)) {
                $xml = file_get_contents($filePath);
                
                // Remove the specific blog URL
                $pattern = '/<url>\s*<loc>.*?blog\/' . preg_quote($blogSlug, '/') . '<\/loc>.*?<\/url>\s*/s';
                $xml = preg_replace($pattern, '', $xml);
                
                file_put_contents($filePath, $xml);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Error removing blog from sitemap: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add URL to sitemap
     */
    private function addUrl($path, $priority = '0.5', $changefreq = 'monthly', $lastmod = null)
    {
        $url = View::url($path);
        $lastmod = $lastmod ? date('Y-m-d', strtotime($lastmod)) : date('Y-m-d');
        
        return "  <url>\n" .
               "    <loc>{$url}</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "    <changefreq>{$changefreq}</changefreq>\n" .
               "    <priority>{$priority}</priority>\n" .
               "  </url>\n";
    }
    
    /**
     * Add sitemap to main sitemap index
     */
    private function addSitemap($filename, $lastmod)
    {
        $url = View::url($filename);
        
        return "  <sitemap>\n" .
               "    <loc>{$url}</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "  </sitemap>\n";
    }
    
    /**
     * Display sitemap generation page
     */
    public function index()
    {
        if (!Session::has('user_id') || Session::get('user_role') !== 'admin') {
            $this->redirect('auth/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'generate_all':
                    $results = $this->generateAllSitemaps();
                    $this->setFlash('success', 'All sitemaps generated successfully');
                    break;
                    
                case 'generate_products':
                    $this->generateProductSitemap();
                    $this->setFlash('success', 'Product sitemap generated successfully');
                    break;
                    
                case 'generate_blogs':
                    $this->generateBlogSitemap();
                    $this->setFlash('success', 'Blog sitemap generated successfully');
                    break;
                    
                case 'generate_categories':
                    $this->generateCategorySitemap();
                    $this->setFlash('success', 'Category sitemap generated successfully');
                    break;
            }
            
            $this->redirect('admin/seo');
        }
        
        $this->view('admin/seo/index', [
            'title' => 'SEO Management'
        ]);
    }
}
