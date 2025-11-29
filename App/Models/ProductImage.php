<?php
namespace App\Models;

use App\Core\Model;

class ProductImage extends Model
{
    protected $table = 'product_images';
    protected $primaryKey = 'id';

    /**
     * Get all images for a product
     *
     * @param int $productId
     * @return array
     */
    public function getByProductId($productId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC";
        return $this->db->query($sql)->bind([$productId])->all();
    }

    /**
     * Get primary image for a product
     *
     * @param int $productId
     * @return array|false
     */
    public function getPrimaryImage($productId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE product_id = ? AND is_primary = 1 LIMIT 1";
        $result = $this->db->query($sql)->bind([$productId])->single();
        
        if (!$result) {
            // If no primary image, get the first image
            $sql = "SELECT * FROM {$this->table} WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1";
            $result = $this->db->query($sql)->bind([$productId])->single();
        }
        
        return $result;
    }
    
    /**
     * Get images for multiple products (batch loading)
     *
     * @param array $productIds
     * @return array
     */
    public function getByProductIds(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        $sql = "SELECT * FROM {$this->table} WHERE product_id IN ($placeholders) ORDER BY product_id, is_primary DESC, sort_order ASC";
        return $this->db->query($sql)->bind($productIds)->all();
    }

    /**
     * Add image to product
     *
     * @param int $productId
     * @param string $imageUrl
     * @param bool $isPrimary
     * @param int $sortOrder
     * @return int|bool
     */
    public function addImage($productId, $imageUrl, $isPrimary = false, $sortOrder = 0)
    {
        // If setting as primary, unset other primary images
        if ($isPrimary) {
            $this->unsetPrimaryImages($productId);
        }

        $data = [
            'product_id' => $productId,
            'image_url' => $imageUrl,
            'is_primary' => $isPrimary ? 1 : 0,
            'sort_order' => $sortOrder,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    /**
     * Update image
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateImage($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // If setting as primary, unset other primary images
        if (isset($data['is_primary']) && $data['is_primary']) {
            $image = $this->find($id);
            if ($image) {
                $this->unsetPrimaryImages($image['product_id']);
            }
        }

        return $this->update($id, $data);
    }

    /**
     * Delete image
     *
     * @param int $id
     * @return bool
     */
    public function deleteImage($id)
    {
        return $this->delete($id);
    }

    /**
     * Delete all images for a product
     *
     * @param int $productId
     * @return bool
     */
    public function deleteByProductId($productId)
    {
        $sql = "DELETE FROM {$this->table} WHERE product_id = ?";
        return $this->db->query($sql)->bind([$productId])->execute();
    }

    /**
     * Set primary image
     *
     * @param int $productId
     * @param int $imageId
     * @return bool
     */
    public function setPrimaryImage($productId, $imageId)
    {
        // First unset all primary images for this product
        $this->unsetPrimaryImages($productId);
        
        // Set the specified image as primary
        return $this->update($imageId, ['is_primary' => 1]);
    }

    /**
     * Unset all primary images for a product
     *
     * @param int $productId
     * @return bool
     */
    private function unsetPrimaryImages($productId)
    {
        $sql = "UPDATE {$this->table} SET is_primary = 0 WHERE product_id = ?";
        return $this->db->query($sql)->bind([$productId])->execute();
    }

    /**
     * Update sort order for images
     *
     * @param array $imageOrders Array of ['id' => sort_order]
     * @return bool
     */
    public function updateSortOrder($imageOrders)
    {
        $success = true;
        
        foreach ($imageOrders as $imageId => $sortOrder) {
            $result = $this->update($imageId, ['sort_order' => $sortOrder]);
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Get image count for a product
     *
     * @param int $productId
     * @return int
     */
    public function getImageCount($productId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE product_id = ?";
        $result = $this->db->query($sql)->bind([$productId])->single();
        return $result ? (int)$result['count'] : 0;
    }
}
