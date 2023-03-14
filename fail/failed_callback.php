<?php
// require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
function failed_callback(){
    global $woocommerce;
    $woocommerce->cart->empty_cart();
}
?>
