<?php
require __DIR__ . '/../App/Config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();
$ads = $db->query("SELECT id, status, approval_status, start_date, end_date, auto_paused, (SELECT cost_amount FROM ads_costs WHERE id = a.ads_cost_id) as bid FROM ads a WHERE ads_type_id = (SELECT id FROM ads_types WHERE name='banner_external' LIMIT 1)")->all();
print_r($ads);
?>
