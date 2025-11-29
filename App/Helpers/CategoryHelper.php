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
        'Protein' => 'Protein',
        'Clean Protein' => 'Clean Protein',
        'Cycle' => 'Cycle',
        'Equipments' => 'Equipments',
        'Digital' => 'Digital Products'
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
        'Protein' => [
            'Whey Protein' => 'Whey Protein',
            'Isolate Protein' => 'Isolate Protein',
            'Mass Gainer' => 'Mass Gainer',
            'Vegan Protein' => 'Vegan Protein',
            'Casein Protein' => 'Casein Protein'
        ],
        'Clean Protein' => [
            'Whey Protein' => 'Whey Protein',
            'Plant Protein' => 'Plant Protein',
            'Casein Protein' => 'Casein Protein',
            'Organic Protein' => 'Organic Protein'
        ],
        'Cycle' => [
            'Bulking Cycle' => 'Bulking Cycle',
            'Cutting Cycle' => 'Cutting Cycle',
            'Maintenance Cycle' => 'Maintenance Cycle',
            'PCT' => 'PCT'
        ],
        'Equipments' => [
            'Gym Equipment' => 'Gym Equipment',
            'Resistance Bands' => 'Resistance Bands',
            'Weights' => 'Weights',
            'Machines' => 'Machines',
            'Accessories' => 'Accessories'
        ],
        'Digital' => [
            'E-books' => 'E-books',
            'Meal Plans' => 'Meal Plans',
            'Training Programs' => 'Training Programs',
            'Coaching Sessions' => 'Coaching Sessions',
            'Software' => 'Software'
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
            if (in_array($subtype, $subtypes, true)) {
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