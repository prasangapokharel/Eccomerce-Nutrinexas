<?php

namespace App\Controllers\Ads;

use App\Config\BannerSlotConfig;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Banner;
use App\Models\Ad;
use App\Models\AdType;
use App\Models\AdCost;
use Exception;

class BannerController extends Controller
{
    private $bannerModel;
    private $adModel;
    private $adTypeModel;
    private $adCostModel;
    private array $tierCostCache = [];
    private ?array $bannerTypeCache = null;

    public function __construct()
    {
        parent::__construct();
        $this->bannerModel = new Banner();
        $this->adModel = new Ad();
        $this->adTypeModel = new AdType();
        $this->adCostModel = new AdCost();
    }

    /**
     * Admin: List all banners (External Banner Ads)
     */
    public function adminIndex()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        try {
            $bannerType = $this->getBannerType();
            if (!$bannerType) {
                $this->setFlash('error', 'Banner ad type not found');
                $this->redirect('admin');
                return;
            }

            $banners = $this->adModel->getDb()->query(
                "SELECT a.*, at.name as ad_type_name, ac.cost_amount as slot_price,
                        s.company_name, s.name as seller_name
                 FROM ads a
                 INNER JOIN ads_types at ON a.ads_type_id = at.id
                 LEFT JOIN ads_costs ac ON a.ads_cost_id = ac.id
                 LEFT JOIN sellers s ON a.seller_id = s.id
                 WHERE at.name = 'banner_external'
                 ORDER BY a.created_at DESC
                 LIMIT ? OFFSET ?",
                [$limit, $offset]
            )->all();

            $slotMeta = BannerSlotConfig::getSlots();
            usort($banners, function ($a, $b) use ($slotMeta) {
                $priorityA = $slotMeta[$a['slot_key']]['priority'] ?? 999;
                $priorityB = $slotMeta[$b['slot_key']]['priority'] ?? 999;
                if ($priorityA === $priorityB) {
                    return strtotime($b['updated_at']) <=> strtotime($a['updated_at']);
                }
                return $priorityA <=> $priorityB;
            });

            $totalBanners = $this->adModel->getDb()->query(
                "SELECT COUNT(*) as total FROM ads a
                 INNER JOIN ads_types at ON a.ads_type_id = at.id
                 WHERE at.name = 'banner_external'"
            )->single();

            $totalPages = ceil(($totalBanners['total'] ?? 0) / $limit);

            $this->view('admin/banners/index', [
                'banners' => $banners,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalBanners' => $totalBanners['total'] ?? 0,
                'slotMeta' => $slotMeta,
                'title' => 'Manage Banner Ads'
            ]);
        } catch (Exception $e) {
            error_log('Banner index error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to load banners');
            $this->redirect('admin');
        }
    }

    /**
     * Admin: Create new banner (External Banner Ad)
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $errors = [];
                $data = $_POST;

                // Validation
                if (empty($data['banner_image'])) {
                    $errors['banner_image'] = 'Banner image URL is required';
                }

                if (!empty($data['banner_link']) && !filter_var($data['banner_link'], FILTER_VALIDATE_URL)) {
                    $errors['banner_link'] = 'Invalid URL format';
                }

                if (empty($data['start_date'])) {
                    $errors['start_date'] = 'Start date is required';
                }

                // Auto-calculate end_date from start_date + 7 days (1 week)
                $startDate = !empty($data['start_date']) ? $data['start_date'] : null;
                $endDate = null;
                if ($startDate) {
                    $endDate = date('Y-m-d', strtotime($startDate . ' +7 days'));
                }

                $slotKey = $data['slot_key'] ?? '';
                $slot = BannerSlotConfig::getSlot($slotKey);
                if (!$slot) {
                    $errors['slot_key'] = 'Please select a valid slot';
                }

                if (empty($errors)) {
                    $bannerType = $this->getBannerType();
                    if (!$bannerType) {
                        $errors['general'] = 'Banner ad type not found';
                    } else {
                        try {
                            $costId = $this->getCostIdForTier($slot['tier']);
                        } catch (Exception $e) {
                            $errors['slot_key'] = $e->getMessage();
                        }

                        if (empty($errors)) {
                            $sellerId = !empty($data['seller_id']) ? (int)$data['seller_id'] : 0;
                            if ($sellerId === 0) {
                                $seller = $this->adModel->getDb()->query("SELECT id FROM sellers LIMIT 1")->single();
                                $sellerId = $seller ? $seller['id'] : 0;
                            }

                            $adData = [
                                'seller_id' => $sellerId,
                                'ads_type_id' => $bannerType['id'],
                                'banner_image' => trim($data['banner_image']),
                                'banner_link' => !empty($data['banner_link']) ? trim($data['banner_link']) : null,
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'ads_cost_id' => $costId,
                                'slot_key' => $slotKey,
                                'tier' => $slot['tier'],
                                'status' => $data['status'] ?? 'active',
                                'notes' => !empty($data['notes']) ? trim($data['notes']) : null
                            ];

                            $adId = $this->adModel->create($adData);
                            if ($adId) {
                                $this->setFlash('success', 'Banner ad created successfully');
                                $this->redirect('admin/banners');
                                return;
                            }

                            $errors['general'] = 'Failed to create banner ad';
                        }
                    }
                }

                $this->view('admin/banners/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'slotOptions' => BannerSlotConfig::getSlotsGroupedByTier(),
                    'title' => 'Create Banner Ad'
                ]);
            } catch (Exception $e) {
                error_log('Banner create error: ' . $e->getMessage());
                $this->setFlash('error', 'An error occurred while creating the banner');
                $this->redirect('admin/banners');
            }
        } else {
            $this->view('admin/banners/create', [
                'slotOptions' => BannerSlotConfig::getSlotsGroupedByTier(),
                'title' => 'Create Banner Ad'
            ]);
        }
    }

    /**
     * Admin: Edit banner ad
     */
    public function edit($id)
    {
        try {
            $banner = $this->adModel->find($id);
            
            // Verify it's a banner_external ad
            if (!$banner) {
                $this->setFlash('error', 'Banner ad not found');
                $this->redirect('admin/banners');
                return;
            }

            $bannerType = $this->adTypeModel->find($banner['ads_type_id']);
            if (!$bannerType || $bannerType['name'] !== 'banner_external') {
                $this->setFlash('error', 'Invalid banner ad');
                $this->redirect('admin/banners');
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $errors = [];
                $data = $_POST;

                // Validation
                if (empty($data['banner_image'])) {
                    $errors['banner_image'] = 'Banner image URL is required';
                }

                if (!empty($data['banner_link']) && !filter_var($data['banner_link'], FILTER_VALIDATE_URL)) {
                    $errors['banner_link'] = 'Invalid URL format';
                }

                if (empty($data['start_date'])) {
                    $errors['start_date'] = 'Start date is required';
                }

                // Auto-calculate end_date from start_date + 7 days (1 week)
                $startDate = !empty($data['start_date']) ? $data['start_date'] : null;
                $endDate = null;
                if ($startDate) {
                    $endDate = date('Y-m-d', strtotime($startDate . ' +7 days'));
                }

                $slotKey = $data['slot_key'] ?? $banner['slot_key'] ?? '';
                $slot = BannerSlotConfig::getSlot($slotKey);
                if (!$slot) {
                    $errors['slot_key'] = 'Please select a valid slot';
                }

                if (empty($errors)) {
                    try {
                        $costId = $this->getCostIdForTier($slot['tier']);
                    } catch (Exception $e) {
                        $errors['slot_key'] = $e->getMessage();
                    }
                }

                if (empty($errors)) {
                    $updateData = [
                        'banner_image' => trim($data['banner_image']),
                        'banner_link' => !empty($data['banner_link']) ? trim($data['banner_link']) : null,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'ads_cost_id' => $costId,
                        'slot_key' => $slotKey,
                        'tier' => $slot['tier'],
                        'status' => $data['status'] ?? 'active',
                        'notes' => !empty($data['notes']) ? trim($data['notes']) : null
                    ];

                    $this->adModel->getDb()->query(
                        "UPDATE ads SET banner_image = ?, banner_link = ?, start_date = ?, end_date = ?, ads_cost_id = ?, slot_key = ?, tier = ?, status = ?, notes = ?, updated_at = NOW() WHERE id = ?",
                        [
                            $updateData['banner_image'],
                            $updateData['banner_link'],
                            $updateData['start_date'],
                            $updateData['end_date'],
                            $updateData['ads_cost_id'],
                            $updateData['slot_key'],
                            $updateData['tier'],
                            $updateData['status'],
                            $updateData['notes'],
                            $id
                        ]
                    )->execute();

                    $this->setFlash('success', 'Banner ad updated successfully');
                    $this->redirect('admin/banners');
                } else {
                    $this->view('admin/banners/edit', [
                        'banner' => $banner,
                        'errors' => $errors,
                        'data' => $data,
                        'slotOptions' => BannerSlotConfig::getSlotsGroupedByTier(),
                        'title' => 'Edit Banner Ad'
                    ]);
                }
            } else {
                $this->view('admin/banners/edit', [
                    'banner' => $banner,
                    'slotOptions' => BannerSlotConfig::getSlotsGroupedByTier(),
                    'title' => 'Edit Banner Ad'
                ]);
            }
        } catch (Exception $e) {
            error_log('Banner edit error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while editing the banner');
            $this->redirect('admin/banners');
        }
    }

    /**
     * Admin: Delete banner ad
     */
    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $banner = $this->adModel->find($id);

                if (!$banner) {
                    $this->jsonResponse(['success' => false, 'message' => 'Banner ad not found'], 404);
                    return;
                }

                if ($this->adModel->delete($id)) {
                    $this->jsonResponse(['success' => true, 'message' => 'Banner ad deleted successfully']);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to delete banner ad'], 500);
                }
            } catch (Exception $e) {
                error_log('Banner delete error: ' . $e->getMessage());
                $this->jsonResponse(['success' => false, 'message' => 'An error occurred while deleting the banner ad'], 500);
            }
        }
    }

    /**
     * Admin: Toggle banner ad status
     */
    public function toggleStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $banner = $this->adModel->find($id);

                if (!$banner) {
                    $this->jsonResponse(['success' => false, 'message' => 'Banner ad not found'], 404);
                    return;
                }

                $newStatus = $banner['status'] === 'active' ? 'inactive' : 'active';

                if ($newStatus === 'active') {
                    $this->adModel->getDb()->query(
                        "UPDATE ads SET status = ?, auto_paused = 0, updated_at = NOW() WHERE id = ?",
                        [$newStatus, $id]
                    )->execute();
                } else {
                    $this->adModel->getDb()->query(
                        "UPDATE ads SET status = ?, updated_at = NOW() WHERE id = ?",
                        [$newStatus, $id]
                    )->execute();
                }

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Banner ad status updated successfully',
                    'new_status' => $newStatus
                ]);
            } catch (Exception $e) {
                error_log('Banner toggle error: ' . $e->getMessage());
                $this->jsonResponse(['success' => false, 'message' => 'An error occurred while updating the banner ad'], 500);
            }
        }
    }

    /**
     * Admin: Bulk delete banners
     */
    public function bulkDelete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                $ids = $input['ids'] ?? [];

                if (empty($ids) || !is_array($ids)) {
                    $this->jsonResponse(['success' => false, 'message' => 'No banners selected'], 400);
                    return;
                }

                // Get banner type for condition
                $bannerType = $this->adTypeModel->findByName('banner_external');
                if (!$bannerType) {
                    $this->jsonResponse(['success' => false, 'message' => 'Banner type not found'], 404);
                    return;
                }

                // Use BulkActionService
                $bulkService = new \App\Services\BulkActionService();
                $result = $bulkService->bulkDelete(
                    \App\Models\Ad::class,
                    $ids,
                    ['ads_type_id' => $bannerType['id']] // Only delete banner_external ads
                );

                if ($result['success']) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => $result['message'],
                        'deleted_count' => $result['count']
                    ]);
                } else {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => $result['message']
                    ], 400);
                }
            } catch (Exception $e) {
                error_log('Banner bulk delete error: ' . $e->getMessage());
                error_log('Banner bulk delete trace: ' . $e->getTraceAsString());
                $this->jsonResponse([
                    'success' => false, 
                    'message' => 'An error occurred while deleting banners: ' . $e->getMessage()
                ], 500);
            }
        }
    }

    private function getBannerType(): ?array
    {
        if ($this->bannerTypeCache === null) {
            $this->bannerTypeCache = $this->adTypeModel->findByName('banner_external');
        }
        return $this->bannerTypeCache;
    }

    private function getCostIdForTier(string $tier): int
    {
        if (isset($this->tierCostCache[$tier])) {
            return $this->tierCostCache[$tier];
        }

        $bannerType = $this->getBannerType();
        if (!$bannerType) {
            throw new Exception('Banner ad type not found');
        }

        $cost = $this->adCostModel->getByAdTypeAndTier($bannerType['id'], $tier);
        if (!$cost) {
            throw new Exception('Pricing not configured for ' . strtoupper($tier));
        }

        $this->tierCostCache[$tier] = (int) $cost['id'];
        return $this->tierCostCache[$tier];
    }

    /**
     * Track banner click
     */
    public function trackClick($id)
    {
        try {
            $this->bannerModel->incrementClicks($id);
            $banner = $this->bannerModel->getBannerById($id);
            
            if ($banner && !empty($banner['link_url'])) {
                $this->redirect($banner['link_url']);
            } else {
                $this->redirect('');
            }
        } catch (Exception $e) {
            error_log('Banner click tracking error: ' . $e->getMessage());
            $this->redirect('');
        }
    }

    /**
     * Track banner view (AJAX)
     */
    public function trackView($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->bannerModel->incrementViews($id);
                $this->jsonResponse(['success' => true]);
            } catch (Exception $e) {
                error_log('Banner view tracking error: ' . $e->getMessage());
                $this->jsonResponse(['success' => false]);
            }
        }
    }

    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

