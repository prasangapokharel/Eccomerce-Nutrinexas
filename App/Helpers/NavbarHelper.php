<?php

namespace App\Helpers;

use App\Core\Database;

class NavbarHelper
{
    /**
     * Get all categories with their subcategories (subtypes) from products
     * Includes random product image for each category
     * 
     * @return array
     */
    public static function getCategoriesWithSubcategories()
    {
        $db = Database::getInstance();
        
        // Get all unique category-subtype combinations from active products
        $sql = "SELECT DISTINCT 
                    p.category,
                    p.subtype,
                    COUNT(*) as product_count
                FROM products p
                WHERE p.status = 'active' 
                    AND p.category IS NOT NULL 
                    AND p.category != ''
                GROUP BY p.category, p.subtype
                HAVING p.subtype IS NOT NULL AND p.subtype != ''
                ORDER BY p.category ASC, p.subtype ASC";
        
        $results = $db->query($sql)->all();
        
        // Organize by category
        $categories = [];
        foreach ($results as $row) {
            $category = $row['category'];
            $subtype = $row['subtype'];
            
            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'name' => $category,
                    'slug' => urlencode($category),
                    'subcategories' => [],
                    'image_url' => null
                ];
            }
            
            // Add subcategory if not already added
            $subcategoryExists = false;
            foreach ($categories[$category]['subcategories'] as $existing) {
                if ($existing['name'] === $subtype) {
                    $subcategoryExists = true;
                    break;
                }
            }
            
            if (!$subcategoryExists) {
                $categories[$category]['subcategories'][] = [
                    'name' => $subtype,
                    'slug' => urlencode($subtype),
                    'product_count' => (int)$row['product_count']
                ];
            }
        }
        
        // Also include categories without subcategories
        $sqlAllCategories = "SELECT DISTINCT category
                            FROM products
                            WHERE status = 'active' 
                                AND category IS NOT NULL 
                                AND category != ''
                            ORDER BY category ASC";
        
        $allCategories = $db->query($sqlAllCategories)->all();
        
        foreach ($allCategories as $row) {
            $category = $row['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'name' => $category,
                    'slug' => urlencode($category),
                    'subcategories' => [],
                    'image_url' => null
                ];
            }
        }
        
        // Get random product image for each category
        foreach ($categories as &$category) {
            $category['image_url'] = self::getRandomCategoryImage($category['name']);
        }
        unset($category);
        
        return array_values($categories);
    }
    
    /**
     * Get random product image for a category
     * 
     * @param string $categoryName
     * @return string|null
     */
    private static function getRandomCategoryImage($categoryName)
    {
        $db = Database::getInstance();
        
        // Try to get a random product image from product_images table first
        $sql = "SELECT pi.image_url
                FROM product_images pi
                INNER JOIN products p ON pi.product_id = p.id
                WHERE p.category = ?
                    AND p.status = 'active'
                    AND pi.image_url IS NOT NULL
                    AND pi.image_url != ''
                ORDER BY RAND()
                LIMIT 1";
        
        $result = $db->query($sql, [$categoryName])->single();
        
        if ($result && !empty($result['image_url'])) {
            return $result['image_url'];
        }
        
        // Fallback: try to get product image field
        $sql = "SELECT p.image
                FROM products p
                WHERE p.category = ?
                    AND p.status = 'active'
                    AND p.image IS NOT NULL
                    AND p.image != ''
                ORDER BY RAND()
                LIMIT 1";
        
        $result = $db->query($sql, [$categoryName])->single();
        
        if ($result && !empty($result['image'])) {
            $imageUrl = $result['image'];
            // If it's already a full URL, return it
            if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                return $imageUrl;
            }
            // Otherwise, return relative path (will be handled by View::asset)
            return $imageUrl;
        }
        
        // Final fallback: return null (will use default in view)
        return null;
    }
    
    /**
     * Get subcategories for a specific category
     * 
     * @param string $categoryName
     * @return array
     */
    public static function getSubcategoriesForCategory($categoryName)
    {
        $db = Database::getInstance();
        
        $sql = "SELECT DISTINCT subtype as name, COUNT(*) as product_count
                FROM products
                WHERE status = 'active' 
                    AND category = ?
                    AND subtype IS NOT NULL 
                    AND subtype != ''
                GROUP BY subtype
                ORDER BY subtype ASC";
        
        $results = $db->query($sql, [$categoryName])->all();
        
        $subcategories = [];
        foreach ($results as $row) {
            $subcategories[] = [
                'name' => $row['name'],
                'slug' => urlencode($row['name']),
                'product_count' => (int)$row['product_count']
            ];
        }
        
        return $subcategories;
    }
}

