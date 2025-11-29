<?php

namespace App\Controllers\Ads;

use App\Core\Controller;
use App\Services\SponsoredAdsService;

class AdTrackingController extends Controller
{
    private $sponsoredService;

    public function __construct()
    {
        parent::__construct();
        $this->sponsoredService = new SponsoredAdsService();
    }

    /**
     * Log ad click
     * This will also charge for the click if using per-click billing
     */
    public function logClick()
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $adsId = $input['ads_id'] ?? null;
        $ipAddress = $input['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
        
        if ($adsId) {
            // Use SponsoredAdsService which handles billing
            $this->sponsoredService->logAdClick($adsId, $ipAddress);
        }
        
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Log ad reach/view
     * This will also charge for the impression if using real-time billing
     */
    public function logReach()
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $adsId = $input['ads_id'] ?? null;
        $ipAddress = $input['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
        
        if ($adsId) {
            // Use SponsoredAdsService which handles billing
            $this->sponsoredService->logAdView($adsId, $ipAddress);
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
}


