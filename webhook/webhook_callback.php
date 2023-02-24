<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
$header = getallheaders();
$header_value = $header['X-Ivy-Signature'];
$request = file_get_contents("php://input");
$data = json_decode($request);
$cartHashId = $data->payload->referenceId;
global $wpdb;
$custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
$cart_results = $wpdb->get_results($wpdb->prepare("SELECT order_id FROM $custom_cart_session_table_name WHERE cart_hash_id = %s", $cartHashId));
foreach ($cart_results as $cart_result) {
    $orderId = $cart_result->order_id;
 }
$order = wc_get_order($orderId);
$type = $data->type;
if($type === 'order_updated' || $type === 'order_created')
{
  
    if($data->payload->status === 'canceled')
            {
                
                if ( ! $order->has_invoice() ) {
                    $note = 'Order cancelled by admin.';
                    $reason = 'Cancelled by admin.';
                    $refund = false;
                    $cancelled = wc_cancel_order( $orderId, $note, $reason, $refund );
                }

            }
    elseif($data->payload->status === 'paid')
    {
        $order->update_status( 'processing' );

    }
    elseif($data->payload->status === 'waiting_for_payment')
    {
        $order->update_status( 'pending' );
    }
    elseif($data->payload->status === 'refunded')
    {
    
        $refund = wc_create_refund( array(
            'amount' => $order->get_total(),
            'reason' => 'Refund requested by customer.',
            'order_id' => $orderId,
          ) );
        $refund->save();
        $order->add_refund( $refund );
        $order->update_status( 'refunded' );
        $order->save();
    }

}

?>
