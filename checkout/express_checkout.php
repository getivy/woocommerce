<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

$order = new WC_Order();
$product = WC()->cart->get_cart();
$carttotal = WC()->cart->cart_contents_total;
$currency = get_option('woocommerce_currency');
$multi_items_array = [];
global $woocommerce, $post;
$order_id = $order->save();
$cart = WC()->session->get('cart');

if ($current_shipping_cost == 'Free!') {
    $current_shipping_cost = 0;
}

foreach ($product as $item => $values) {

    $items['name'] = $values['data']->name;
    $items['referenceId'] = $values['product_id'];
    $items['singleNet'] = $values['data']->price;
    $items['singleVat'] = 123;
    $items['amount'] = $values['data']->price;
    $items['quantity'] = $values['quantity'];
    $items['image'] = "";
    $multi_items_array[] = $items;

}
$data = array(
    'express' => true,
    'referenceId' => $order_id,
    'category' => "5712",
    'price' => array(
        'totalNet' => $carttotal,
        'vat' => 0,
        'shipping' => 50,
        'total' => $carttotal,
        'currency' => $currency
    ),
    'lineItems' => $multi_items_array,
    'required' => array('phone' => true),
);
$url = "https://api.stage.getivy.de/api/service/checkout/session/create";
$post = json_encode($data); # all data that going to send
$installed_payment_methods = WC()->payment_gateways()->payment_gateways();
$ivysandboxkey = $installed_payment_methods["ivy_payment"]->ivyapikey;
$option = $installed_payment_methods["ivy_payment"]->sandbox;
$ivylivekey = $installed_payment_methods["ivy_payment"]->ivyapikeylive;
$ivykey = $ivysandboxkey;
if ($option == "No"){
  $ivykey = $ivylivekey;
}
// Sending Curl Request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT    5.0');
$headers = [
    'content-type: application/json',
    'X-Ivy-Api-Key:' . $ivykey . ''
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$exe = curl_exec($ch);
$getInfo = curl_getinfo($ch);
if ($getInfo['http_code'] === 200) {
    echo $exe;
}
if (curl_error($ch)) {
    $output .= "\n" . curl_error($ch);
}
curl_close($ch);

?>