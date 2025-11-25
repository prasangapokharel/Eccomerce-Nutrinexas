<?php

namespace App\Services;

use App\Core\Database;

class SecurityLogService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Log unauthorized access attempt
     * 
     * @param string $eventType Type of event (e.g., 'unauthorized_order_access', 'unauthorized_product_edit')
     * @param int $sellerId Seller ID attempting access
     * @param int|null $resourceId Resource ID (order_id, product_id, etc.)
     * @param string $resourceType Type of resource (order, product, etc.)
     * @param array $metadata Additional metadata
     */
    public function logUnauthorizedAccess($eventType, $sellerId, $resourceId = null, $resourceType = null, $metadata = [])
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $description = "Unauthorized access attempt: Seller #{$sellerId} attempted to access {$resourceType} #{$resourceId}";
        
        $fullMetadata = array_merge($metadata, [
            'seller_id' => $sellerId,
            'resource_id' => $resourceId,
            'resource_type' => $resourceType,
            'request_uri' => $requestUri,
            'request_method' => $requestMethod,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ]);

        // Check if security_events table exists
        $tableExists = $this->db->query(
            "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'security_events'"
        )->single();

        if ($tableExists['count'] > 0) {
            $this->db->query(
                "INSERT INTO security_events 
                 (event_type, severity, description, ip_address, user_id, metadata, created_at) 
                 VALUES (?, 'high', ?, ?, ?, ?, NOW())",
                [
                    $eventType,
                    $description,
                    $ipAddress,
                    $sellerId, // Using seller_id as user_id for seller events
                    json_encode($fullMetadata)
                ]
            )->execute();
        }

        // Also log to error log
        error_log("SECURITY: {$description} | IP: {$ipAddress} | URI: {$requestUri}");
    }

    /**
     * Log ad click fraud attempt
     * 
     * @param int $adId Ad ID
     * @param string $ipAddress IP address
     * @param array $fraudData Fraud detection data
     */
    public function logAdFraudAttempt($adId, $ipAddress, $fraudData)
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $description = "Ad click fraud detected: Ad #{$adId} | IP: {$ipAddress} | Score: {$fraudData['fraud_score']}";
        
        $metadata = [
            'ad_id' => $adId,
            'ip_address' => $ipAddress,
            'fraud_score' => $fraudData['fraud_score'] ?? 0,
            'indicators' => $fraudData['indicators'] ?? [],
            'click_count' => $fraudData['click_count'] ?? 0,
            'total_clicks' => $fraudData['total_clicks'] ?? 0,
            'user_agent' => $userAgent
        ];

        $tableExists = $this->db->query(
            "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'security_events'"
        )->single();

        if ($tableExists['count'] > 0) {
            $this->db->query(
                "INSERT INTO security_events 
                 (event_type, severity, description, ip_address, metadata, created_at) 
                 VALUES (?, 'high', ?, ?, ?, NOW())",
                [
                    'ad_click_fraud',
                    $description,
                    $ipAddress,
                    json_encode($metadata)
                ]
            )->execute();
        }

        error_log("AD FRAUD: {$description} | Indicators: " . implode(', ', $fraudData['indicators'] ?? []));
    }
}

