<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
global $woocommerce;
$woocommerce->cart->empty_cart();
?>
