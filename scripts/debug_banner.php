<?php
require __DIR__ . '/../App/Config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Services\BannerAdDisplayService;

$service = new BannerAdDisplayService();
$banner = $service->getBannerForPlacement('tier1', 'homepage');
var_export($banner);
?>
