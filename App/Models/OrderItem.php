<?php
namespace App\Models;

use App\Core\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'id';

    /**
     * Get all items for an order
     *
     * @param int $orderId
     * @return array
     */
    public function getByOrderId($orderId)
    {
        $sql = "SELECT oi.*, p.product_name, p.image, p.price as original_price, p.sale_price,
                       p.is_digital, p.product_type_main, p.product_type, p.colors
                FROM {$this->table} oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        return $this->db->query($sql)->bind([$orderId])->all();
    }

    /**
     * Get order items by order_id and seller_id
     *
     * @param int $orderId
     * @param int $sellerId
     * @return array
     */
    public function getByOrderIdAndSellerId($orderId, $sellerId)
    {
        $sql = "SELECT oi.*, p.product_name, p.image, p.price as original_price, p.sale_price,
                       p.is_digital, p.product_type_main, p.product_type, p.colors,
                       pi.image_url as product_image
                FROM {$this->table} oi
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE oi.order_id = ? AND oi.seller_id = ?";
        return $this->db->query($sql)->bind([$orderId, $sellerId])->all();
    }

    /**
     * Create a new order item
     *
     * @param array $data
     * @return int|false
     */
    public function createOrderItem($data)
    {
        return $this->create($data);
    }

    /**
     * Get best selling products
     *
     * @param int $limit
     * @return array
     */
    public function getBestSellingProducts($limit = 4)
    {
        $sql = "SELECT p.*, SUM(oi.quantity) as total_sold 
                FROM {$this->table} oi
                JOIN products p ON oi.product_id = p.id
                GROUP BY oi.product_id
                ORDER BY total_sold DESC
                LIMIT ?";
        return $this->db->query($sql)->bind([$limit])->all();
    }

    /**
     * Get total sales for a product
     *
     * @param int $productId
     * @return int
     */
    public function getTotalSalesForProduct($productId)
    {
        $sql = "SELECT SUM(quantity) as total_sold 
                FROM {$this->table} 
                WHERE product_id = ?";
        $result = $this->db->query($sql)->bind([$productId])->single();
        return $result ? (int)$result['total_sold'] : 0;
    }

    /**
     * Get total revenue
     *
     * @return float
     */
    public function getTotalRevenue()
    {
        $sql = "SELECT SUM(total) as total_revenue FROM {$this->table}";
        $result = $this->db->query($sql)->single();
        return $result ? (float)$result['total_revenue'] : 0;
    }

    /**
     * Get subtotal for an order
     *
     * @param int $orderId
     * @return float
     */
    public function getSubtotalByOrderId($orderId)
    {
        $sql = "SELECT COALESCE(SUM(total), 0) as subtotal FROM {$this->table} WHERE order_id = ?";
        $result = $this->db->query($sql, [$orderId])->single();
        return $result ? (float)$result['subtotal'] : 0;
    }

    /**
     * Delete all items for an order
     *
     * @param int $orderId
     * @return bool
     */
    public function deleteByOrderId($orderId)
    {
        $sql = "DELETE FROM {$this->table} WHERE order_id = ?";
        return $this->db->query($sql)->bind([$orderId])->execute();
    }

}
