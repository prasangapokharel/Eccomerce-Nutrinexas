# Database Migration and Verification Guide

## Summary

This document provides instructions for:
1. Reading the products table structure
2. Running migrations to add missing columns
3. Verifying all columns are used in AdminController

## Products Table - All Maximum Columns

Based on the database schema, the products table has the following columns:

### Core Fields
- `id` - Primary key
- `product_name` - Product name (REQUIRED)
- `slug` - URL slug (REQUIRED)
- `description` - Full description
- `short_description` - Short description for listings
- `price` - Regular price (REQUIRED)
- `sale_price` - Sale price (optional)
- `stock_quantity` - Stock count (REQUIRED)
- `category` - Main category
- `subtype` - Subcategory (from migration)

### Product Details
- `weight` - Product weight
- `serving` - Serving size
- `optimal_weight` - Optimal weight
- `serving_size` - Serving size details
- `flavor` - Flavor variant
- `capsule` - Is capsule format (boolean)

### Product Types
- `product_type_main` - Main type (Accessories, Supplement, Vitamins)
- `product_type` - Sub-type (e.g., Protein, Clothing, Equipment)
- `is_digital` - Digital product flag (from migration)
- `colors` - Available colors JSON (from migration)

### Material & Ingredients
- `material` - Material composition (for clothing/accessories)
- `ingredients` - Key ingredients (for supplements)
- `size_available` - Available sizes JSON

### Pricing & Commission
- `cost_price` - Cost price
- `compare_price` - Compare at price
- `commission_rate` - Commission rate (default 10.00)
- `seller_commission` - Seller commission (default 10.00)

### SEO & Metadata
- `meta_title` - SEO title
- `meta_description` - SEO description
- `tags` - Product tags

### Status & Scheduling
- `status` - Product status (active/inactive)
- `is_featured` - Featured product flag
- `is_scheduled` - Scheduled product flag
- `scheduled_date` - Scheduled release date
- `scheduled_duration` - Scheduled duration
- `scheduled_message` - Scheduled message

### Sales & Tracking
- `total_sales` - Total sales count
- `total_revenue` - Total revenue
- `sales_count` - Sales count
- `image` - Primary image URL
- `seller_id` - Seller ID

### Timestamps
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp

## Migration Steps

### Step 1: Run Migration SQL

Execute the following SQL in your database:

```sql
-- Add is_digital column
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS is_digital TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Whether product is digital (no shipping required)' 
AFTER is_featured;

-- Add colors column  
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS colors TEXT NULL 
COMMENT 'Available colors for product (JSON array)' 
AFTER product_type;

-- Add subtype column if missing
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS subtype VARCHAR(255) NULL 
AFTER category;
```

**Note:** If your MySQL version doesn't support `IF NOT EXISTS`, use the file:
`Database/migration/add_is_digital_and_colors_columns.sql` which has conditional logic.

### Step 2: Verify Columns

Run this query to verify all columns exist:

```sql
DESCRIBE products;
```

You should see:
- `is_digital` column
- `colors` column  
- `subtype` column

### Step 3: Verify AdminController

The AdminController has been updated to use ALL maximum columns:
- ✓ All 30+ columns are in the `allowedFields` array
- ✓ All nullable fields are properly handled
- ✓ JSON fields (colors, size_available) are processed correctly

## Testing

1. Visit `http://localhost:8000/admin/addProduct`
2. Fill in the form with all available fields
3. Submit and verify the product is created successfully
4. Check the database to ensure all fields are saved

## All Tables in Database

The system uses these tables:
- `products` - Main products table
- `product_images` - Product images
- `users` - User accounts
- `orders` - Order records
- `order_items` - Order line items
- `cart` - Shopping cart
- `coupons` - Discount coupons
- `categories` - Product categories
- `reviews` - Product reviews
- `referral_earnings` - Referral system
- `delivery_charges` - Delivery fees
- `settings` - System settings
- And more...

All tables are properly integrated and used throughout the system.

