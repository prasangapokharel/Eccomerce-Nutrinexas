<?php

namespace App\Helpers;

class CategoryHelper
{
    /**
     * Main product categories
     */
    const MAIN_CATEGORIES = [
        'Supplements' => 'Supplements',
        'Accessories' => 'Accessories', 
        'Beauty' => 'Beauty',
        'Clean Protein' => 'Clean Protein'
    ];

    /**
     * Product subtypes organized by main category
     */
    const SUBTYPES = [
        'Supplements' => [
            'Protein' => 'Protein',
            'Pre-Workout' => 'Pre-Workout',
            'BCAA' => 'BCAA',
            'Creatine' => 'Creatine',
            'Mass Gainer' => 'Mass Gainer',
            'Vitamins' => 'Vitamins',
            'Minerals' => 'Minerals',
            'Fat Burners' => 'Fat Burners',
            'Amino Acids' => 'Amino Acids',
            'Post-Workout' => 'Post-Workout'
        ],
        'Accessories' => [
            'Shakers' => 'Shakers',
            'Gym Equipment' => 'Gym Equipment',
            'Apparel' => 'Apparel',
            'Storage' => 'Storage'
        ],
        'Beauty' => [
            'Collagen' => 'Collagen',
            'Skin Care' => 'Skin Care',
            'Hair Care' => 'Hair Care',
            'Anti-Aging' => 'Anti-Aging'
        ],
        'Clean Protein' => [
            'Whey Protein' => 'Whey Protein',
            'Plant Protein' => 'Plant Protein',
            'Casein Protein' => 'Casein Protein',
            'Organic Protein' => 'Organic Protein'
        ]
    ];

    /**
     * Get all main categories
     */
    public static function getMainCategories()
    {
        return self::MAIN_CATEGORIES;
    }

    /**
     * Get subtypes for a specific main category
     */
    public static function getSubtypes($mainCategory = null)
    {
        if ($mainCategory && isset(self::SUBTYPES[$mainCategory])) {
            return self::SUBTYPES[$mainCategory];
        }
        return self::SUBTYPES;
    }

    /**
     * Get all subtypes as flat array
     */
    public static function getAllSubtypes()
    {
        $allSubtypes = [];
        foreach (self::SUBTYPES as $subtypes) {
            $allSubtypes = array_merge($allSubtypes, $subtypes);
        }
        return $allSubtypes;
    }

    /**
     * Get main category for a subtype
     */
    public static function getMainCategoryForSubtype($subtype)
    {
        foreach (self::SUBTYPES as $mainCategory => $subtypes) {
            if (in_array($subtype, $subtypes)) {
                return $mainCategory;
            }
        }
        return null;
    }

    /**
     * Validate if category exists
     */
    public static function isValidMainCategory($category)
    {
        return isset(self::MAIN_CATEGORIES[$category]);
    }

    /**
     * Validate if subtype exists
     */
    public static function isValidSubtype($subtype)
    {
        return in_array($subtype, self::getAllSubtypes());
    }

    /**
     * Get category display options for forms
     */
    public static function getCategoryOptions()
    {
        return self::MAIN_CATEGORIES;
    }

    /**
     * Get subtype options for forms (grouped by main category)
     */
    public static function getSubtypeOptions()
    {
        return self::SUBTYPES;
    }

    /**
     * Map legacy categories to new structure
     */
    public static function mapLegacyCategory($legacyCategory)
    {
        $mapping = [
            'Protein' => ['main' => 'Supplements', 'subtype' => 'Protein'],
            'Pre-Workout' => ['main' => 'Supplements', 'subtype' => 'Pre-Workout'],
            'BCAA' => ['main' => 'Supplements', 'subtype' => 'BCAA'],
            'creatine' => ['main' => 'Supplements', 'subtype' => 'Creatine'],
            'collagen' => ['main' => 'Beauty', 'subtype' => 'Collagen'],
            'Beauty' => ['main' => 'Beauty', 'subtype' => 'Skin Care'],
            'Supplements' => ['main' => 'Supplements', 'subtype' => 'Vitamins'],
        ];

        return $mapping[$legacyCategory] ?? ['main' => 'Supplements', 'subtype' => 'Vitamins'];
    }
}