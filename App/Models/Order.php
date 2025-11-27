<?php

namespace App\Models;

use App\Core\Model;
use App\Helpers\CacheHelper;
use App\Models\Curior\Curior as CuriorModel;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';

    /**
     * Assign a curior to an order with validation.
     */
    public function assignCuriorToOrder(int $orderId, int $curiorId, bool $autoUpdateStatus = true): bool
    {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid order ID');
        }

        if ($curiorId <= 0) {
            throw new \InvalidArgumentException('Invalid curior ID');
        }

        $order = $this->find($orderId);
        if (!$order) {
            throw new \InvalidArgumentException('Order not found');
        }

        $curiorModel = new CuriorModel();
        $curior = $curiorModel->getById($curiorId);
        if (!$curior) {
            throw new \InvalidArgumentException('Curior not found');
        }

        $updateData = [
            'curior_id' => $curiorId,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($autoUpdateStatus && in_array($order['status'], ['pending', 'processing', 'confirmed'], true)) {
            $updateData['status'] = 'shipped';
        }

        return parent::update($orderId, $updateData);
    }

    /**
     * Get orders assigned to a specific curior
     */
    public function getOrdersByCurior($curiorId)
    {
        $sql = "SELECT o.*,
                        o.customer_name as order_customer_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                        u.email as customer_email,
                        pm.name as payment_method,
                        pg.type as payment_type
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        LEFT JOIN payment_gateways pg ON pm.gateway_id = pg.id
                        WHERE o.curior_id = ? AND o.status IN ('dispatched', 'processing', 'shipped', 'picked_up', 'in_transit', 'ready_for_pickup')
                        ORDER BY o.created_at DESC";
        
        return $this->db->query($sql, [$curiorId])->all();
    }

    /**
     * Get curior statistics
     */
    public function getCuriorStats($curiorId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'dispatched' THEN 1 ELSE 0 END) as dispatched_orders,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
                FROM {$this->table} 
                WHERE curior_id = ?";
        
        return $this->db->query($sql, [$curiorId])->single();
    }

    /**
     * Update order status
     */
    public function updateStatus($orderId, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->query($sql)->bind([$status, $orderId])->execute();
    }

    /**
     * Get orders assigned to a specific staff member
     */
    public function getOrdersByStaff($staffId)
    {
        $sql = "SELECT o.*,
                        o.customer_name as order_customer_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                        u.email as customer_email,
                        pm.name as payment_method,
                        pg.type as payment_type
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        LEFT JOIN payment_gateways pg ON pm.gateway_id = pg.id
                        WHERE o.staff_id = ? AND o.status != 'cancelled'
                        ORDER BY o.created_at DESC";
        
        return $this->db->query($sql, [$staffId])->all();
    }

    /**
     * Get staff statistics
     */
    public function getStaffStats($staffId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN packaged_count = 0 THEN 1 ELSE 0 END) as to_package_orders,
                    SUM(CASE WHEN packaged_count > 0 THEN 1 ELSE 0 END) as packaged_orders,
                    SUM(packaged_count) as total_packaged
                FROM {$this->table} 
                WHERE staff_id = ? AND status != 'cancelled'";
        
        return $this->db->query($sql, [$staffId])->single();
    }

    /**
     * Get orders by seller ID
     * Orders are filtered by seller_id in order_items (since orders can have products from multiple sellers)
     */
    public function getOrdersBySellerId($sellerId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT DISTINCT o.*, u.first_name, u.last_name, u.email as customer_email
                FROM {$this->table} o
                INNER JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE oi.seller_id = ?
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?";
        return $this->db->query($sql, [$sellerId, $limit, $offset])->all();
    }

    /**
     * Get order count by seller
     */
    public function getOrderCountBySeller($sellerId)
    {
        $result = $this->db->query("SELECT COUNT(DISTINCT o.id) as count 
                                    FROM {$this->table} o
                                    INNER JOIN order_items oi ON o.id = oi.order_id
                                    WHERE oi.seller_id = ?", [$sellerId])->single();
        return $result['count'] ?? 0;
    }

    /**
     * Get order with full details including products
     */
    public function getOrderWithDetails($orderId)
    {
        $sql = "SELECT o.*,
                        o.customer_name as order_customer_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                        u.email as customer_email,
                        pm.name as payment_method,
                        pg.type as payment_type
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        LEFT JOIN payment_gateways pg ON pm.gateway_id = pg.id
                        WHERE o.id = ?";
        
        $order = $this->db->query($sql, [$orderId])->single();
        
        if ($order) {
            // Get order items
            $order['items'] = $this->getOrderItems($orderId);
        }
        
        return $order;
    }

    /**
     * Get order items
     */
    public function getOrderItems($orderId)
    {
        $sql = "SELECT oi.*, p.product_name, p.image as product_image, 
                       p.price as original_price, p.sale_price,
                       COALESCE(NULLIF(p.sale_price, 0), p.price) as effective_price
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        
        $items = $this->db->query($sql, [$orderId])->all();
        
        // Update price and total to use sale_price if available
        foreach ($items as &$item) {
            $effectivePrice = !empty($item['sale_price']) && $item['sale_price'] > 0 
                ? $item['sale_price'] 
                : ($item['original_price'] ?? $item['price'] ?? 0);
            
            // Update price and recalculate total based on sale_price
            $item['price'] = $effectivePrice;
            $item['total'] = $effectivePrice * ($item['quantity'] ?? 1);
        }
        
        return $items;
    }

    /**
     * Increment packaged count
     */
    public function incrementPackagedCount($orderId)
    {
        $sql = "UPDATE {$this->table} SET packaged_count = packaged_count + 1, updated_at = NOW() WHERE id = ?";
        return $this->db->query($sql, [$orderId]);
    }

    /**
     * Update order status and package count
     */
    public function updateStatusAndPackageCount($orderId, $status, $packageCount)
    {
        $sql = "UPDATE {$this->table} SET status = ?, packaged_count = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->query($sql, [$status, $packageCount, $orderId]);
    }

    /**
     * Get all orders with optional status filter.
     *
     * @param string|null $status
     * @return array
     */
    public function getAllOrders($status = null)
    {
        $sql = "SELECT o.*,
                        o.customer_name as order_customer_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                        u.email as customer_email,
                        pm.name as payment_method
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id";
        $params = [];

        if ($status) {
            $sql .= " WHERE o.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY o.created_at DESC";

        return $this->db->query($sql)->bind($params)->all();
    }

    /**
     * Get orders by status (alias for getAllOrders with status filter)
     *
     * @param string $status
     * @return array
     */
    public function getOrdersByStatus($status)
    {
        return $this->getAllOrders($status);
    }

    /**
     * Get a single order by ID with user's full name and email.
     *
     * @param int $orderId
     * @return array|false
     */
    public function getOrderById($orderId)
    {
        $sql = "SELECT o.*,
                        o.customer_name as order_customer_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                        u.email as customer_email,
                        u.phone as user_phone,
                        pm.name as payment_method,
                        kp.pidx as khalti_pidx,
                        kp.transaction_id as khalti_transaction_id,
                        ep.reference_id as esewa_reference_id,
                        ep.transaction_id as esewa_transaction_id
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        LEFT JOIN khalti_payments kp ON o.id = kp.order_id
                        LEFT JOIN esewa_payments ep ON o.id = ep.order_id
                        WHERE o.id = ?";
        return $this->db->query($sql)->bind([$orderId])->single();
    }

    /**
     * Get order by invoice number with all details including payment method
     */
    public function getOrderByInvoice($invoice)
    {
        $sql = "SELECT o.*,
                        COALESCE(o.invoice, CONCAT('NTX', LPAD(o.id, 6, '0'))) as invoice,
                        o.customer_name as order_customer_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                        u.email as customer_email,
                        u.phone as user_phone,
                        pm.name as payment_method,
                        kp.pidx as khalti_pidx,
                        kp.transaction_id as khalti_transaction_id,
                        ep.reference_id as esewa_reference_id,
                        ep.transaction_id as esewa_transaction_id
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        LEFT JOIN khalti_payments kp ON o.id = kp.order_id
                        LEFT JOIN esewa_payments ep ON o.id = ep.order_id
                        WHERE o.invoice = ? OR CONCAT('NTX', LPAD(o.id, 6, '0')) = ?";
        return $this->db->query($sql)->bind([$invoice, $invoice])->single();
    }

    /**
     * Create a new order and its items.
     *
     * @param array $orderData
     * @param array $cartItems
     * @return int|bool
     */
    public function createOrder(array $orderData, array $cartItems)
    {
        try {
            error_log('=== ORDER CREATION START ===');
            error_log('Order data: ' . json_encode($orderData));
            error_log('Cart items count: ' . count($cartItems));
            // Start transaction
            $this->db->beginTransaction();
            // Generate invoice number
            $invoice = 'NTX' . date('Ymd') . rand(1000, 9999);

            // Prepare full address
            $fullAddress = $orderData['address_line1'];
            if (!empty($orderData['address_line2'])) {
                $fullAddress .= ', ' . $orderData['address_line2'];
            }
            $fullAddress .= ', ' . $orderData['city'] . ', ' . $orderData['state'] . ' ' . ($orderData['postal_code'] ?? ''); // Added null coalescing for postal_code
            // Insert order - MATCHING ACTUAL DATABASE SCHEMA
            // Use final_amount if provided, otherwise use total_amount
            $finalAmount = $orderData['final_amount'] ?? $orderData['total_amount'] ?? 0;
            
            $sql = "INSERT INTO {$this->table} (
                                invoice, user_id, customer_name, contact_no, payment_method_id,
                                status, address, order_notes, transaction_id, total_amount,
                                tax_amount, discount_amount, delivery_fee, coupon_code, payment_screenshot, created_at, updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $result = $this->db->query($sql)->bind([
                $invoice,
                $orderData['user_id'],
                $orderData['recipient_name'],
                $orderData['phone'],
                $orderData['payment_method_id'],
                'pending',
                $fullAddress,
                $orderData['order_notes'] ?? '',
                $orderData['transaction_id'] ?? '',
                $finalAmount, // Store final amount in total_amount
                $orderData['tax_amount'] ?? 0,
                $orderData['discount_amount'] ?? 0,
                $orderData['delivery_fee'] ?? 0,
                $orderData['coupon_code'] ?? null,
                $orderData['payment_screenshot'] ?? ''
            ])->execute();
            if (!$result) {
                throw new \Exception('Failed to create order');
            }
            $orderId = $this->db->lastInsertId();
            error_log('Order created with ID: ' . $orderId);
            // Insert order items - MATCHING ACTUAL DATABASE SCHEMA (with color, size, and seller_id)
            $itemSql = "INSERT INTO order_items (order_id, product_id, seller_id, selected_color, selected_size, quantity, price, total, invoice)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            foreach ($cartItems as $item) {
                $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
                
                if ($quantity <= 0) {
                    error_log('Skipping item with invalid quantity: ' . json_encode($item));
                    continue;
                }
                
                // Get seller_id from product (from cart item or fetch from database)
                $sellerId = null;
                if (isset($item['seller_id']) && !empty($item['seller_id'])) {
                    $sellerId = (int)$item['seller_id'];
                } else {
                    // Fetch seller_id from product if not in cart item
                    $product = $this->db->query("SELECT seller_id FROM products WHERE id = ?", [$item['product_id']])->single();
                    $sellerId = $product ? (int)($product['seller_id'] ?? null) : null;
                }
                
                // Get selected color and size from cart item
                $selectedColor = isset($item['color']) ? trim($item['color']) : null;
                $selectedSize = isset($item['size']) ? trim($item['size']) : null;
                
                // Prefer product sale_price when valid; otherwise use regular price.
                // Some queries include both cart.price and product.price under the same key; prioritize sale_price explicitly.
                $regularPrice = isset($item['price']) ? (float)$item['price'] : 0.0;
                $salePriceVal = isset($item['sale_price']) ? (float)$item['sale_price'] : 0.0;

                if ($salePriceVal > 0 && ($regularPrice <= 0 || $salePriceVal < $regularPrice)) {
                    $price = $salePriceVal;
                } else {
                    $price = $regularPrice;
                }
                
                if ($price <= 0) {
                    error_log('Skipping item with invalid price: ' . json_encode($item));
                    continue;
                }

                $total = $price * $quantity;
                $itemResult = $this->db->query($itemSql)->bind([
                    $orderId,
                    $item['product_id'],
                    $sellerId,
                    $selectedColor,
                    $selectedSize,
                    $quantity,
                    $price,
                    $total,
                    $invoice
                ])->execute();
                if (!$itemResult) {
                    throw new \Exception('Failed to create order item for product: ' . ($item['product_name'] ?? ('ID ' . $item['product_id'])));
                }
                error_log('Order item created for product: ' . ($item['product_name'] ?? ('ID ' . $item['product_id'])) . ' with seller_id: ' . ($sellerId ?? 'NULL'));
            }
            // Commit transaction
            $this->db->commit();
            error_log('=== ORDER CREATION SUCCESS ===');

            // All post-order operations are async to not block checkout
            $orderModelInstance = $this;
            register_shutdown_function(function() use ($orderId, $orderModelInstance) {
                try {
                    // Try auto-assignment by city
                    $orderModelInstance->autoAssignByCity($orderId);
                } catch (\Exception $e) {
                    error_log('Order: Error in auto-assignment: ' . $e->getMessage());
                }
                
                try {
                    // Create pending referral earning if user was referred
                    $referralService = new \App\Services\ReferralEarningService();
                    $referralService->createPendingReferralEarning($orderId);
                } catch (\Exception $e) {
                    error_log('Order: Error creating pending referral earning: ' . $e->getMessage());
                }
                
                try {
                    // Notify sellers about new order
                    $notificationService = new \App\Services\SellerNotificationService();
                    $notificationService->notifyNewOrder($orderId);
                } catch (\Exception $e) {
                    error_log('Order: Error sending seller notifications: ' . $e->getMessage());
                }
            });

            // Clear cache for order counts
            if (class_exists('App\Helpers\CacheHelper')) {
                $cache = CacheHelper::getInstance();
                $cache->delete($cache->generateKey('order_count', []));
                $cache->delete($cache->generateKey('total_sales', []));
                $cache->delete($cache->generateKey('recent_orders', []));
            }

            return $orderId;
        } catch (\Exception $e) {
            // Rollback transaction only if one is active
            if ($this->db->getPdo()->inTransaction()) {
                $this->db->rollback();
            }
            error_log('=== ORDER CREATION FAILED ===');
            error_log('Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            throw $e;
        }
    }

    /**
     * Update order status.
     *
     * @param int $orderId
     * @param string $status
     * @return bool
     */
    public function updateOrderStatus($orderId, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->query($sql)->bind([$status, $orderId])->execute();
    }

    /**
     * Get recent orders.
     *
     * @param int $limit
     * @return array
     */
    public function getRecentOrders($limit = 5)
    {
        $sql = "SELECT o.*,
                        o.customer_name as order_customer_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                        pm.name as payment_method
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        ORDER BY o.created_at DESC LIMIT ?";
        return $this->db->query($sql)->bind([$limit])->all();
    }

    /**
     * Get total number of orders.
     *
     * @return int
     */
    public function getOrderCount()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->query($sql)->single();
        return $result['count'] ?? 0;
    }

    /**
     * Get count by status
     */
    public function getCountByStatus($status)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = ?";
        $result = $this->db->query($sql)->bind([$status])->single();
        return $result['count'] ?? 0;
    }

    /**
     * Get total sales amount.
     *
     * @return float
     */
    public function getTotalSales()
    {
        $sql = "SELECT SUM(total_amount) as total FROM {$this->table} WHERE status = 'paid'";
        $result = $this->db->query($sql)->single();
        return $result['total'] ?? 0;
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue()
    {
        return $this->getTotalSales();
    }

    /**
     * Get total count
     */
    public function getTotalCount()
    {
        return $this->getOrderCount();
    }

    /**
     * Get all orders with details (alias for getAllOrders)
     */
    public function getAllWithDetails()
    {
        return $this->getAllOrders();
    }

    /**
     * Get orders for a specific user with pagination
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUserOrders($userId, $limit = 10, $offset = 0)
    {
        $sql = "SELECT o.*,
                        o.customer_name as order_customer_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                        u.email as customer_email,
                        pm.name as payment_method
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        WHERE o.user_id = ?
                        ORDER BY o.created_at DESC
                        LIMIT ? OFFSET ?";
        
        return $this->db->query($sql)->bind([$userId, $limit, $offset])->all();
    }

    /**
     * Get total count of orders for a specific user
     *
     * @param int $userId
     * @return int
     */
    public function getUserOrdersCount($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?";
        $result = $this->db->query($sql)->bind([$userId])->single();
        return $result['count'] ?? 0;
    }

    /**
     * Get orders by user ID (alias)
     */
    public function getByUserId($userId)
    {
        return $this->getOrdersByUserId($userId);
    }

    /**
     * Update order
     */
    public function updateOrder($id, $data)
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";

        // Clear cache for this specific order if caching is enabled
        if (class_exists('App\Helpers\CacheHelper')) {
            $cache = CacheHelper::getInstance();
            $cache->delete($cache->generateKey('order', ['id' => $id]));
            $cache->delete($cache->generateKey('recent_orders', [])); // Clear recent orders cache
        }

        return $this->db->query($sql)->bind($values)->execute();
    }

    /**
     * Delete order
     */
    public function deleteOrder($id)
    {
        error_log("Order model: Attempting to delete order ID: $id");
        
        try {
            // First check if order exists
            $checkSql = "SELECT COUNT(*) as count FROM {$this->table} WHERE id = ?";
            $checkResult = $this->db->query($checkSql)->bind([$id])->single();
            error_log("Order model: Order exists check: " . json_encode($checkResult));
            
            if ($checkResult['count'] == 0) {
                error_log("Order model: Order does not exist");
                return false;
            }
            
            // Check if order has items
            $itemSql = "SELECT COUNT(*) as count FROM order_items WHERE order_id = ?";
            $itemResult = $this->db->query($itemSql)->bind([$id])->single();
            error_log("Order model: Order has " . $itemResult['count'] . " items");
            
            // Delete the order (order items will be automatically deleted due to CASCADE)
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            error_log("Order model: SQL: $sql");

            // Clear cache for this specific order if caching is enabled
            if (class_exists('App\Helpers\CacheHelper')) {
                $cache = CacheHelper::getInstance();
                $cache->delete($cache->generateKey('order', ['id' => $id]));
                $cache->delete($cache->generateKey('order_count', []));
                $cache->delete($cache->generateKey('total_sales', []));
                $cache->delete($cache->generateKey('recent_orders', []));
            }

            $result = $this->db->query($sql)->bind([$id])->execute();
            error_log("Order model: Delete result: " . ($result ? 'true' : 'false'));
            
            if (!$result) {
                $errorInfo = $this->db->getPdo()->errorInfo();
                error_log("Order model: Database error: " . json_encode($errorInfo));
            } else {
                // Verify deletion
                $verifySql = "SELECT COUNT(*) as count FROM {$this->table} WHERE id = ?";
                $verifyResult = $this->db->query($verifySql)->bind([$id])->single();
                error_log("Order model: Verification after deletion: " . json_encode($verifyResult));
            }
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("Order model: Exception during deletion: " . $e->getMessage());
            error_log("Order model: Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Get orders with pagination
     */
    public function getOrdersWithPagination($page = 1, $limit = 20, $status = null)
    {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT o.*,
                        o.customer_name as order_customer_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                        pm.name as payment_method
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id";
        $params = [];

        if ($status) {
            $sql .= " WHERE o.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->query($sql)->bind($params)->all();
    }

    /**
     * Search orders
     */
    public function searchOrders($searchTerm, $status = null)
    {
        $sql = "SELECT o.*,
                        o.customer_name as order_customer_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                        u.email as customer_email,
                        pm.name as payment_method
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        WHERE (o.invoice LIKE ? OR o.customer_name LIKE ? OR u.email LIKE ?)";
        $params = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];

        if ($status) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY o.created_at DESC";

        return $this->db->query($sql)->bind($params)->all();
    }

    /**
     * Get monthly sales data
     */
    public function getMonthlySales($year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $sql = "SELECT
                        MONTH(created_at) as month,
                        SUM(total_amount) as total_sales,
                        COUNT(*) as order_count
                        FROM {$this->table}
                        WHERE YEAR(created_at) = ? AND status = 'paid'
                        GROUP BY MONTH(created_at)
                        ORDER BY MONTH(created_at)";

        return $this->db->query($sql)->bind([$year])->all();
    }

    /**
     * Get order statistics
     */
    public function getOrderStatistics()
    {
        $sql = "SELECT
                        status,
                        COUNT(*) as count,
                        SUM(total_amount) as total_amount
                        FROM {$this->table}
                        GROUP BY status";

        return $this->db->query($sql)->all();
    }

    /**
     * Get orders by creation date
     *
     * @param string $date
     * @return array
     */
    public function getOrdersByDate(string $date): array
    {
        $sql = "SELECT o.*, u.id as user_id, u.phone as user_phone
                 FROM {$this->table} o
                 LEFT JOIN users u ON o.user_id = u.id
                 WHERE DATE(o.created_at) >= ?
                 ORDER BY o.created_at DESC";
        return $this->db->query($sql)->bind([$date])->all();
    }

    /**
     * Get the latest product purchased by a user
     *
     * @param int $userId
     * @return array|null
     */
    public function getLatestProductByUser(int $userId): ?array
    {
        $sql = "SELECT p.*
                 FROM {$this->table} o
                 INNER JOIN order_items oi ON o.id = oi.order_id
                 INNER JOIN products p ON oi.product_id = p.id
                 WHERE o.user_id = ?
                 ORDER BY o.created_at DESC
                 LIMIT 1";
        return $this->db->query($sql)->bind([$userId])->single() ?: null;
    }

    /**
     * Get the latest product for a specific order
     *
     * @param int $orderId
     * @return array|null
     */
    public function getLatestProduct(int $orderId): ?array
    {
        $sql = "SELECT p.*
                 FROM order_items oi
                 INNER JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?
                 ORDER BY oi.id DESC
                 LIMIT 1";
        return $this->db->query($sql)->bind([$orderId])->single() ?: null;
    }

    /**
     * Get orders by user ID.
     *
     * @param int $userId
     * @return array
     */
    public function getOrdersByUserId($userId)
    {
        $sql = "SELECT o.*, 
                        COALESCE(o.invoice, CONCAT('NTX', LPAD(o.id, 6, '0'))) as invoice,
                        pm.name as payment_method
                        FROM {$this->table} o
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        WHERE o.user_id = ?
                        ORDER BY o.created_at DESC";

        return $this->db->query($sql)->bind([$userId])->all();
    }

    /**
     * Get order with items (for success page or admin view).
     *
     * @param int $orderId
     * @return array|false
     */
    public function getOrderWithItems($orderId)
    {
        $order = $this->getOrderById($orderId);

        if ($order) {
            // Get order items with product images and selected color/size
            $sql = "SELECT oi.*, p.product_name, p.price as product_price, p.slug as product_slug,
                           pi.image_url as product_image, p.category as product_category,
                           oi.selected_color, oi.selected_size
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    WHERE oi.order_id = ?";

            $items = $this->db->query($sql)->bind([$orderId])->all();
            $order['items'] = $items;
        }

        return $order;
    }


    /**
     * Get recent orders with phone numbers for SMS marketing
     * 
     * @param int $limit Number of recent orders to return
     * @return array
     */
    public function getRecentOrdersWithPhones(int $limit = 50): array
    {
        $sql = "SELECT o.id, o.user_id, o.customer_name, o.contact_no as phone,
                       u.email, u.phone as user_phone
                FROM {$this->table} o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE (o.contact_no IS NOT NULL AND o.contact_no != '') 
                   OR (u.phone IS NOT NULL AND u.phone != '')
                ORDER BY o.created_at DESC
                LIMIT ?";
        
        $orders = $this->db->query($sql)->bind([$limit])->all();
        
        // Process orders to get the best available phone number
        foreach ($orders as &$order) {
            // Prefer contact_no from order, fallback to user phone
            if (empty($order['phone']) && !empty($order['user_phone'])) {
                $order['phone'] = $order['user_phone'];
            } elseif (empty($order['phone'])) {
                $order['phone'] = null;
            }

            // Clean up the array
            unset($order['user_phone']);
        }
        
        // Filter out orders without valid phone numbers
        return array_filter($orders, function($order) {
            return !empty($order['phone']);
        });
    }

    /**
     * Get a specific order for a user
     *
     * @param int $userId
     * @param int $orderId
     * @return array|false
     */
    public function getUserOrder($userId, $orderId)
    {
        $sql = "SELECT o.*,
                        o.customer_name as order_customer_name,
                        CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                        u.email as customer_email,
                        pm.name as payment_method
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        WHERE o.id = ? AND o.user_id = ?";
        
        return $this->db->query($sql)->bind([$orderId, $userId])->single();
    }

    /**
     * Update order with new data
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";

        // Execute the update
        try {
            $result = $this->db->query($sql, $values)->execute();

            // If status changed, trigger referral processing/cancellation as a best-effort step.
            // Use service - it is idempotent and safe (it checks existing earnings).
            if ($result && isset($data['status'])) {
                try {
                    $status = $data['status'];
                    $referralService = new \App\Services\ReferralEarningService();

                    if ($status === 'delivered') {
                        // Transition pending earnings to paid or create direct paid earning
                        $referralService->processReferralEarning($id);
                    } elseif ($status === 'cancelled') {
                        // Cancel any referral earnings for this order
                        $referralService->cancelReferralEarning($id);
                    }
                } catch (\Exception $e) {
                    // Log but do not fail the update
                    error_log('Order::update referral processing error: ' . $e->getMessage());
                }
            }

            return $result;
        } catch (\Exception $e) {
            error_log('Order::update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all orders without staff assignment
     */
    public function getUnassignedOrders()
    {
        $sql = "SELECT o.*,
                        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                        u.email as customer_email,
                        pm.name as payment_method
                        FROM {$this->table} o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        WHERE o.staff_id IS NULL OR o.staff_id = 0
                        ORDER BY o.created_at DESC";
        
        return $this->db->query($sql)->all();
    }

    /**
     * Auto-assign orders based on city or products
     */
    public function autoAssignByCity($orderId)
    {
        // Get order details
        $order = $this->find($orderId);
        if (!$order) {
            return false;
        }

        // Try product-based assignment first
        $staffId = $this->assignByProducts($orderId);
        if ($staffId) {
            return $this->update($orderId, ['staff_id' => $staffId]);
        }

        // Try city-based assignment
        $staffId = $this->assignByCity($orderId);
        if ($staffId) {
            return $this->update($orderId, ['staff_id' => $staffId]);
        }
        
        return false;
    }

    /**
     * Assign order by products
     */
    private function assignByProducts($orderId)
    {
        // Get order items
        $sql = "SELECT product_id FROM order_items WHERE order_id = ?";
        $orderItems = $this->db->query($sql, [$orderId])->all();
        
        if (empty($orderItems)) {
            return false;
        }

        $productIds = array_column($orderItems, 'product_id');
        
        // Find staff assigned to these products
        // Check if assigned_products column exists
        $columnCheck = $this->db->query("SHOW COLUMNS FROM staff LIKE 'assigned_products'")->single();
        
        if (!$columnCheck) {
            // Column doesn't exist, skip product-based assignment
            return false;
        }
        
        $sql = "SELECT id, assigned_products FROM staff WHERE status = 'active'";
        $staffMembers = $this->db->query($sql)->all();
        
        foreach ($staffMembers as $staff) {
            $assignedProducts = json_decode($staff['assigned_products'] ?? '[]', true);
            if (!empty($assignedProducts)) {
                // Check if any order products match staff assigned products
                $hasMatch = !empty(array_intersect($productIds, $assignedProducts));
                if ($hasMatch) {
                    // Check for conflicts
                    if (!$this->hasConflict($staff['id'], $orderId)) {
                        return $staff['id'];
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Assign order by city
     */
    private function assignByCity($orderId)
    {
        $order = $this->find($orderId);
        $address = $order['address'] ?? '';
        $city = $this->extractCityFromAddress($address);
        
        if (!$city) {
            return false;
        }

        // Check if assigned_cities column exists
        $columnCheck = $this->db->query("SHOW COLUMNS FROM staff LIKE 'assigned_cities'")->single();
        
        if (!$columnCheck) {
            // Column doesn't exist, skip city-based assignment
            return false;
        }
        
        // Find staff assigned to this city
        $sql = "SELECT id FROM staff WHERE assigned_cities LIKE ? AND status = 'active'";
        $staffMembers = $this->db->query($sql, ["%{$city}%"])->all();
        
        foreach ($staffMembers as $staff) {
            // Check for conflicts
            if (!$this->hasConflict($staff['id'], $orderId)) {
                return $staff['id'];
            }
        }
        
        return false;
    }

    /**
     * Check for assignment conflicts
     */
    private function hasConflict($staffId, $orderId)
    {
        // Get order details
        $order = $this->find($orderId);
        
        // Check if order is already assigned
        if ($order['staff_id'] && $order['staff_id'] != 0) {
            return true;
        }
        
        // Check staff workload (max 10 pending orders per staff)
        $sql = "SELECT COUNT(*) as pending_count FROM orders WHERE staff_id = ? AND status = 'pending'";
        $result = $this->db->query($sql, [$staffId])->single();
        
        if ($result['pending_count'] >= 10) {
            return true;
        }
        
        return false;
    }

    /**
     * Extract city from address string
     */
    private function extractCityFromAddress($address)
    {
        // Common cities in Nepal
        $cities = [
            'Kathmandu', 'Lalitpur', 'Bhaktapur', 'Pokhara', 'Bharatpur', 
            'Biratnagar', 'Birgunj', 'Dharan', 'Butwal', 'Hetauda',
            'Nepalgunj', 'Itahari', 'Tulsipur', 'Kalaiya', 'Jitpur',
            'Madhyapur Thimi', 'Birendranagar', 'Ghorahi', 'Tikapur', 'Kirtipur'
        ];
        
        foreach ($cities as $city) {
            if (stripos($address, $city) !== false) {
                return $city;
            }
        }
        
        return null;
    }


}
