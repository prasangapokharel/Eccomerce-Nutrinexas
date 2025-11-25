<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Cache;
use App\Core\Session;

class Cart extends Model
{
    protected $table = 'cart';
    protected $primaryKey = 'id';
    
    private $cache;
    private $cachePrefix = 'cart_';
    private $cacheTTL = 1800; // 30 minutes

    public function __construct()
    {
        parent::__construct();
        $this->cache = new Cache();
    }

    /**
     * Get cart items by user ID
     *
     * @param int $userId
     * @return array
     */
    public function getByUserId($userId)
    {
        $cacheKey = $this->cachePrefix . 'user_' . $userId;
        return $this->cache->remember($cacheKey, function () use ($userId) {
            $sql = "SELECT c.*, p.product_name, p.price, p.sale_price, p.stock_quantity, p.image,
                           c.selected_color as color, c.selected_size as size
                    FROM {$this->table} c
                    LEFT JOIN products p ON c.product_id = p.id
                    WHERE c.user_id = ?
                    ORDER BY c.created_at DESC";
            return $this->db->query($sql, [$userId])->all();
        }, $this->cacheTTL);
    }

    /**
     * Get cart with products (alias for getByUserId)
     *
     * @param int $userId
     * @return array
     */
    public function getCartWithProducts($userId)
    {
        return $this->getByUserId($userId);
    }

    /**
     * Get cart item by user and product
     *
     * @param int $userId
     * @param int $productId
     * @return array|false
     */
    public function getByUserAndProduct($userId, $productId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND product_id = ?";
        return $this->db->query($sql, [$userId, $productId])->single();
    }

    /**
     * Add item to cart
     *
     * @param array $data
     * @return int|false
     */
    public function addItem($dataOrProductId, $quantity = null, $price = null)
    {
        // Support both legacy 3-arg calls and array payloads
        if (is_array($dataOrProductId)) {
            $data = $dataOrProductId;
        } else {
            // When called with productId, infer user from session
            if (!Session::has('user_id')) {
                // Guest cart handled in middleware/session, skip DB insert
                $productId = (int)$dataOrProductId;
                $qty = (int)($quantity ?? 1);
                $priceVal = (float)($price ?? 0);
                $_SESSION['guest_cart'] = $_SESSION['guest_cart'] ?? [];
                if (!isset($_SESSION['guest_cart'][$productId])) {
                    $_SESSION['guest_cart'][$productId] = [
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'price' => $priceVal,
                        'sale_price' => null
                    ];
                } else {
                    $_SESSION['guest_cart'][$productId]['quantity'] += $qty;
                }
                $_SESSION['cart_count'] = array_sum(array_column($_SESSION['guest_cart'], 'quantity'));
                return true;
            }

            $data = [
                'user_id' => Session::get('user_id'),
                'product_id' => (int)$dataOrProductId,
                'quantity' => (int)($quantity ?? 1),
                'price' => (float)($price ?? 0)
            ];
        }

        // Check if item already exists
        $existing = $this->getByUserAndProduct($data['user_id'], $data['product_id']);
        
        if ($existing) {
            // Update quantity
            return $this->updateQuantity($existing['id'], $existing['quantity'] + ($data['quantity'] ?? 1));
        } else {
            // Create new item (with color and size if provided)
            $sql = "INSERT INTO {$this->table} (user_id, product_id, selected_color, selected_size, quantity, price, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $result = $this->db->query($sql, [
                $data['user_id'],
                $data['product_id'],
                $data['color'] ?? null,
                $data['size'] ?? null,
                $data['quantity'] ?? 1,
                $data['price'] ?? 0
            ])->execute();

            if ($result) {
                $this->invalidateUserCache($data['user_id']);
                return $this->db->lastInsertId();
            }
            return false;
        }
    }

    /**
     * Update cart item quantity
     *
     * @param int $cartId
     * @param int $quantity
     * @return bool
     */
    public function updateQuantity($cartId, $quantity)
    {
        if ($quantity <= 0) {
            return $this->removeItem($cartId);
        }

        $sql = "UPDATE {$this->table} SET quantity = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$quantity, $cartId])->execute();

        if ($result) {
            // Get user_id for cache invalidation
            $item = $this->find($cartId);
            if ($item) {
                $this->invalidateUserCache($item['user_id']);
            }
        }
        return $result;
    }

    /**
     * Remove item from cart
     *
     * @param int $cartId
     * @return bool
     */
    public function removeItem($cartId)
    {
        // Get user_id for cache invalidation before deleting
        $item = $this->find($cartId);
        $userId = $item ? $item['user_id'] : null;
        
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $result = $this->db->query($sql, [$cartId])->execute();

        if ($result && $userId) {
            $this->invalidateUserCache($userId);
        }
        return $result;
    }

    /**
     * Clear user's cart
     *
     * @param int $userId
     * @return bool
     */
    public function clearCart($userId)
    {
        // Handle guest cart (stored in session)
        if (!$userId || $userId == 0) {
            unset($_SESSION['guest_cart']);
            unset($_SESSION['cart_count']);
            return true;
        }
        
        // Handle logged-in user cart (stored in database)
        $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND 1=1";
        $result = $this->db->query($sql, [$userId])->execute();

        if ($result) {
            $this->invalidateUserCache($userId);
        }
        return $result;
    }

    /**
     * Get cart count for user
     *
     * @param int $userId
     * @return int
     */
    public function getCartCount($userId)
    {
        $cacheKey = $this->cachePrefix . 'count_' . $userId;
        return $this->cache->remember($cacheKey, function () use ($userId) {
            $sql = "SELECT COALESCE(SUM(quantity), 0) as count FROM {$this->table} WHERE user_id = ?";
            $result = $this->db->query($sql, [$userId])->single();
            return $result ? (int)$result['count'] : 0;
        }, $this->cacheTTL);
    }

    /**
     * Get cart total for user
     *
     * @param int $userId
     * @return float
     */
    public function getCartTotal($userId)
    {
        $cacheKey = $this->cachePrefix . 'total_' . $userId;
        return $this->cache->remember($cacheKey, function () use ($userId) {
            $sql = "SELECT COALESCE(SUM(
                        CASE 
                            WHEN p.sale_price > 0 AND p.sale_price < p.price THEN c.quantity * p.sale_price
                            ELSE c.quantity * p.price
                        END
                    ), 0) as total
                    FROM {$this->table} c
                    LEFT JOIN products p ON c.product_id = p.id
                    WHERE c.user_id = ? AND 1=1";
            $result = $this->db->query($sql, [$userId])->single();
            return $result ? (float)$result['total'] : 0;
        }, $this->cacheTTL);
    }

    /**
     * Get cart summary for user
     *
     * @param int $userId
     * @return array
     */
    public function getCartSummary($userId)
    {
        $cacheKey = $this->cachePrefix . 'summary_' . $userId;
        return $this->cache->remember($cacheKey, function () use ($userId) {
            $sql = "SELECT 
                        COUNT(*) as item_count,
                        COALESCE(SUM(c.quantity), 0) as total_quantity,
                        COALESCE(SUM(
                            CASE 
                                WHEN p.sale_price > 0 AND p.sale_price < p.price THEN c.quantity * p.sale_price
                                ELSE c.quantity * p.price
                            END
                        ), 0) as total_amount,
                        COALESCE(SUM(
                            CASE 
                                WHEN p.sale_price > 0 AND p.sale_price < p.price THEN c.quantity * (p.price - p.sale_price)
                                ELSE 0
                            END
                        ), 0) as total_savings
                    FROM {$this->table} c
                    LEFT JOIN products p ON c.product_id = p.id
                    WHERE c.user_id = ? AND 1=1";
            
            $result = $this->db->query($sql, [$userId])->single();
            return $result ? [
                'item_count' => (int)$result['item_count'],
                'total_quantity' => (int)$result['total_quantity'],
                'total_amount' => (float)$result['total_amount'],
                'total_savings' => (float)$result['total_savings']
            ] : [
                'item_count' => 0,
                'total_quantity' => 0,
                'total_amount' => 0.0,
                'total_savings' => 0.0
            ];
        }, $this->cacheTTL);
    }

    /**
     * Check if product is in cart
     *
     * @param int $userId
     * @param int $productId
     * @return bool
     */
    public function isInCart($userId, $productId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND product_id = ? AND 1=1";
        $result = $this->db->query($sql, [$userId, $productId])->single();
        return $result && $result['count'] > 0;
    }

    /**
     * Get cart items with product details
     *
     * @param int $userId
     * @return array
     */
    public function getCartItemsWithDetails($userId)
    {
        $cacheKey = $this->cachePrefix . 'items_details_' . $userId;
        return $this->cache->remember($cacheKey, function () use ($userId) {
            $sql = "SELECT 
                        c.*,
                        p.product_name,
                        p.price,
                        p.sale_price,
                        p.stock_quantity,
                        p.image,
                        p.slug,
                        CASE 
                            WHEN p.sale_price > 0 AND p.sale_price < p.price THEN p.sale_price
                            ELSE p.price
                        END as current_price,
                        CASE 
                            WHEN p.sale_price > 0 AND p.sale_price < p.price THEN c.quantity * p.sale_price
                            ELSE c.quantity * p.price
                        END as item_total
                    FROM {$this->table} c
                    LEFT JOIN products p ON c.product_id = p.id
                    WHERE c.user_id = ? AND 1=1
                    ORDER BY c.created_at DESC";
            return $this->db->query($sql, [$userId])->all();
        }, $this->cacheTTL);
    }

    /**
     * Validate cart items (check stock, prices, etc.)
     *
     * @param int $userId
     * @return array
     */
    public function validateCart($userId)
    {
        $items = $this->getCartItemsWithDetails($userId);
        $errors = [];
        $warnings = [];

        foreach ($items as $item) {
            // Check if product still exists
            if (!$item['product_name']) {
                $errors[] = "Product ID {$item['product_id']} no longer exists";
                continue;
            }

            // Check stock availability
            if ($item['stock_quantity'] < $item['quantity']) {
                if ($item['stock_quantity'] <= 0) {
                    $errors[] = "{$item['product_name']} is out of stock";
                } else {
                    $warnings[] = "Only {$item['stock_quantity']} units of {$item['product_name']} available";
                }
            }

            // Check if price has changed
            $expectedPrice = $item['sale_price'] > 0 && $item['sale_price'] < $item['price'] 
                ? $item['sale_price'] 
                : $item['price'];
            
            if ($item['current_price'] != $expectedPrice) {
                $warnings[] = "Price for {$item['product_name']} has been updated";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'items' => $items
        ];
    }

    /**
     * Move cart items to order
     *
     * @param int $userId
     * @param int $orderId
     * @return bool
     */
    public function moveToOrder($userId, $orderId)
    {
        try {
            $this->db->beginTransaction();
            
            // Update cart items status to 'ordered'
            $sql = "UPDATE {$this->table} SET status = 'ordered', order_id = ?, updated_at = NOW() 
                    WHERE user_id = ? AND 1=1";
            $this->db->query($sql, [$orderId, $userId])->execute();
            
            $this->db->commit();
            $this->invalidateUserCache($userId);
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Get abandoned carts
     *
     * @param int $hours
     * @param int $limit
     * @return array
     */
    public function getAbandonedCarts($hours = 24, $limit = 50)
    {
        $sql = "SELECT c.user_id, u.first_name, u.last_name, u.email, 
                       COUNT(c.id) as item_count, 
                       SUM(c.quantity) as total_quantity,
                       MAX(c.updated_at) as last_activity
                FROM {$this->table} c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE 1=1 
                AND c.updated_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY c.user_id
                ORDER BY last_activity DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$hours, $limit])->all();
    }

    /**
     * Clean up old cart items
     *
     * @param int $days
     * @return int
     */
    public function cleanupOldCarts($days = 30)
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE 1=1 
                AND updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $this->db->query($sql, [$days])->execute();
        return $this->db->rowCount();
    }

    /**
     * Find cart item by user and product
     *
     * @param string $userId
     * @param int $productId
     * @return array|null
     */
    public function findByUserAndProduct($userId, $productId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND product_id = ?";
        return $this->db->query($sql, [$userId, $productId])->single();
    }

    /**
     * Compatibility: getItemCount for current user or guest
     *
     * @return int
     */
    public function getItemCount(): int
    {
        if (Session::has('user_id')) {
            return $this->getCartCount(Session::get('user_id'));
        }
        $guestCart = $_SESSION['guest_cart'] ?? [];
        return (int)array_sum(array_column($guestCart, 'quantity'));
    }

    /**
     * Compatibility: getItems keyed by product_id with quantity
     *
     * @return array
     */
    public function getItems(): array
    {
        if (Session::has('user_id')) {
            $userId = Session::get('user_id');
            $rows = $this->getByUserId($userId);
            $items = [];
            foreach ($rows as $row) {
                $items[(int)$row['product_id']] = [
                    'id' => (int)$row['id'],
                    'product_id' => (int)$row['product_id'],
                    'quantity' => (int)$row['quantity']
                ];
            }
            return $items;
        }
        // Guest cart stored in session already keyed by product_id
        return $_SESSION['guest_cart'] ?? [];
    }

    /**
     * Compatibility: updateItem by product_id and action (increase|decrease)
     *
     * @param int $productId
     * @param string $action
     * @return bool
     */
    public function updateItem(int $productId, string $action): bool
    {
        $delta = ($action === 'decrease') ? -1 : 1;

        if (!Session::has('user_id')) {
            $_SESSION['guest_cart'] = $_SESSION['guest_cart'] ?? [];
            if (!isset($_SESSION['guest_cart'][$productId])) {
                return false;
            }
            $newQty = (int)$_SESSION['guest_cart'][$productId]['quantity'] + $delta;
            if ($newQty <= 0) {
                unset($_SESSION['guest_cart'][$productId]);
            } else {
                $_SESSION['guest_cart'][$productId]['quantity'] = $newQty;
            }
            $_SESSION['cart_count'] = array_sum(array_column($_SESSION['guest_cart'], 'quantity'));
            return true;
        }

        $userId = Session::get('user_id');
        $cartItem = $this->findByUserAndProduct($userId, $productId);
        if (!$cartItem) {
            return false;
        }
        $newQty = (int)$cartItem['quantity'] + $delta;
        if ($newQty <= 0) {
            $result = $this->removeItem((int)$cartItem['id']);
        } else {
            $result = $this->updateQuantity((int)$cartItem['id'], $newQty);
        }
        if ($result) {
            $this->invalidateUserCache($userId);
        }
        return (bool)$result;
    }

    /**
     * Compatibility: clear cart for current user or guest
     */
    public function clear(): bool
    {
        if (Session::has('user_id')) {
            return $this->clearCart(Session::get('user_id'));
        }
        $_SESSION['guest_cart'] = [];
        $_SESSION['cart_count'] = 0;
        return true;
    }

    /**
     * Invalidate user-specific cache
     *
     * @param int $userId
     */
    private function invalidateUserCache($userId)
    {
        $this->cache->delete($this->cachePrefix . 'user_' . $userId);
        $this->cache->delete($this->cachePrefix . 'count_' . $userId);
        $this->cache->delete($this->cachePrefix . 'total_' . $userId);
        $this->cache->delete($this->cachePrefix . 'summary_' . $userId);
        $this->cache->delete($this->cachePrefix . 'items_details_' . $userId);
    }
}