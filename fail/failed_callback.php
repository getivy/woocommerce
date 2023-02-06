<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
global $woocommerce;
$woocommerce->cart->empty_cart();
?>
<p>
    <?php _e('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'woocommerce'); ?>
</p>
<?php
?>