<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
error_log("Webhook called");
$header = getallheaders();
$header_value = $header['X-Ivy-Signature'];
$request = file_get_contents("php://input");
error_log(print_r($request,true));
$data = json_decode($request);
error_log(print_r($request,true));
$orderId = $data->payload->referenceId;
$order = wc_get_order($orderId);
$type = $arrData->type;
if($type === 'order_updated' || $type === 'order_created')
{
    if($data->payload->paymentStatus === 'failed' || $data->payload->paymentStatus === 'canceled' || $data->payload->status === 'failed' || $data->payload->status === 'canceled')
            {
                if ( ! $order->has_invoice() ) {
                    $note = 'Order cancelled by admin.';
                    $reason = 'Cancelled by admin.';
                    $refund = false;
                    $cancelled = wc_cancel_order( $orderId, $note, $reason, $refund );
                }
                else{
                    $refund = wc_create_refund( array(
                        'amount' => $order->get_total(),
                        'reason' => 'Refund requested by customer.',
                        'order_id' => $orderId,
                      ) );
                    $refund->save();
                    $order->add_refund( $refund );
                    $order->save();
                }

            }
    elseif($data->payload->paymentStatus === 'paid')
    {
        $order->update_status( 'completed' );
        $order->save();

    }
    else{
        $order->update_status( 'completed' );
        $order->save();
    }
   error_log($type);
}
else{

    error_log("Order Not updated");
}

error_log("Ivy Webhook called after request");
?>