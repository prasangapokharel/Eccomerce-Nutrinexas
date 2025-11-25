<?php

namespace App\Helpers;

use App\Models\PaymentGateway;

/**
 * Payment Gateway Helper
 * Handles dynamic payment gateway logo retrieval
 */
class PaymentGatewayHelper
{
    /**
     * Get payment gateway logo dynamically
     *
     * @param string $gatewayName
     * @param string $defaultLogo
     * @return string
     */
    public static function getGatewayLogo($gatewayName, $defaultLogo = null)
    {
        try {
            $gatewayModel = new PaymentGateway();
            
            // Try to find gateway by name (case insensitive)
            $gateway = $gatewayModel->getBySlug(strtolower(str_replace(' ', '-', $gatewayName)));
            
            if (!$gateway) {
                // Try to find by name field
                $gateways = $gatewayModel->all();
                foreach ($gateways as $g) {
                    if (strtolower($g['name']) === strtolower($gatewayName)) {
                        $gateway = $g;
                        break;
                    }
                }
            }
            
            // Return dynamic logo if found and not empty
            if ($gateway && !empty($gateway['logo'])) {
                return $gateway['logo'];
            }
            
            // Return default logo if provided
            if ($defaultLogo) {
                return $defaultLogo;
            }
            
            // Return fallback based on gateway name
            return self::getFallbackLogo($gatewayName);
            
        } catch (\Exception $e) {
            error_log('PaymentGatewayHelper getGatewayLogo error: ' . $e->getMessage());
            return self::getFallbackLogo($gatewayName);
        }
    }
    
    /**
     * Get fallback logo based on gateway name
     *
     * @param string $gatewayName
     * @return string
     */
    private static function getFallbackLogo($gatewayName)
    {
        $gatewayName = strtolower($gatewayName);
        
        $fallbackLogos = [
            'khalti' => ASSETS_URL . '/images/gateways/khalti.svg',
            'esewa' => ASSETS_URL . '/images/gateways/esewa.svg',
            'ime pay' => ASSETS_URL . '/images/gateways/imepay.svg',
            'connect ips' => ASSETS_URL . '/images/gateways/connectips.svg',
            'bank transfer' => ASSETS_URL . '/images/gateways/bank.svg',
            'cod' => ASSETS_URL . '/images/gateways/cod.svg',
            'cash on delivery' => ASSETS_URL . '/images/gateways/cod.svg'
        ];
        
        foreach ($fallbackLogos as $key => $logo) {
            if (strpos($gatewayName, $key) !== false) {
                return $logo;
            }
        }
        
        // Default payment icon
        return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>');
    }
    
    /**
     * Get all active payment gateways with logos
     *
     * @return array
     */
    public static function getActiveGatewaysWithLogos()
    {
        try {
            $gatewayModel = new PaymentGateway();
            $gateways = $gatewayModel->getActiveGateways();
            
            foreach ($gateways as &$gateway) {
                if (empty($gateway['logo'])) {
                    $gateway['logo'] = self::getFallbackLogo($gateway['name']);
                }
            }
            
            return $gateways;
            
        } catch (\Exception $e) {
            error_log('PaymentGatewayHelper getActiveGatewaysWithLogos error: ' . $e->getMessage());
            return [];
        }
    }
}


