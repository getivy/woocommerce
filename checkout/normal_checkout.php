<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
$formData = $_POST;
$address = array(
  'first_name' => $formData['billing_first_name'],
  'last_name' => $formData['billing_last_name'],
  'address_1' => $formData['billing_address_1'],
  'address_2' => $formData['billing_address_2'],
  'company' => $formData['billing_company'],
  'city' => $formData['billing_city'],
  'state' => $formData['billing_state'],
  'postcode' => $formData['billing_postcode'],
  'country' => $formData['billing_country'],
  'phone' => $formData['billing_phone'],
  'email' => $formData['billing_email'],
);
$address_contents = json_encode($address);
$product = WC()->cart->get_cart();
$cart = WC()->cart;
$cart->calculate_shipping();
$shipping_total = WC()->cart->get_shipping_total();
$shipping_country[] = $formData['shipping_country'];

foreach (WC()->session->get('shipping_for_package_0')['rates'] as $method_id => $rate) {
  $shipping_method_id = $method_id;
  if (WC()->session->get('chosen_shipping_methods')[0] == $method_id) {
    $rate_label = $rate->label; // The shipping method label name
    $rate_cost_excl_tax = floatval($rate->cost); // The cost excluding tax
    // The taxes cost
    $rate_taxes = 0;
    foreach ($rate->taxes as $rate_tax)
      $rate_taxes += floatval($rate_tax);
    // The cost including tax
    $rate_cost_incl_tax = $rate_cost_excl_tax + $rate_taxes;
    $shipping_label = $rate_label;
    break;
  }
}

global $wpdb;
$custom_cart_session_table_name = $wpdb->prefix . 'custom_cart_sessions';
$session = WC()->session;
$cart_session_id = $session->get_session_cookie();
$user_id = get_current_user_id();
$unique_id = wc_rand_hash();
$cart_session_id = (string) $cart_session_id[3];
$db_cart_session_result = $wpdb->get_results(
  $wpdb->prepare("SELECT cart_session_id FROM $custom_cart_session_table_name WHERE cart_session_id = %s", $cart_session_id)
);
$user_id_result = $wpdb->get_results(
  $wpdb->prepare("SELECT user_id, cart_session_id FROM $custom_cart_session_table_name WHERE user_id = %d", $user_id)
);

$db_user_id = '';
$db_cart_session_id = ''; foreach ($user_id_result as $result) {
  $db_user_id = $result->user_id;
}

foreach ($db_cart_session_result as $result) {
  $db_cart_session_id = $result->cart_session_id;
}

$session_expiry = date('Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS)); // set session expiry to 2 days from now
$cart_contents = json_encode(WC()->cart->get_cart()); // convert cart contents to JSON
if ($db_cart_session_id) {
  $wpdb->update(
    $custom_cart_session_table_name,
    array(
      'cart_contents' => $cart_contents,
      'address_contents' => $address_contents,
      'shipping_title' => $shipping_label,
      'shipping_method' => $shipping_method_id,
      'shipping_price' => $shipping_total,
      'cart_hash_id' => $unique_id,
      'is_express' => 0,
    ),
    array('cart_session_id' => $cart_session_id)
  );
} elseif ($db_user_id) {
  $wpdb->update(
    $custom_cart_session_table_name,
    array(
      'cart_contents' => $cart_contents,
      'cart_session_id' => $cart_session_id,
      'address_contents' => $address_contents,
      'shipping_title' => $shipping_label,
      'shipping_method' => $shipping_method_id,
      'shipping_price' => $shipping_total,
      'cart_hash_id' => $unique_id,
      'is_express' => 0,
    ),
    array('user_id' => $user_id)
  );
} else {
  $wpdb->insert($custom_cart_session_table_name, array(
    'cart_session_id' => $cart_session_id,
    'user_id' => $user_id,
    'session_expiry' => $session_expiry,
    'cart_contents' => $cart_contents,
    'cart_hash_id' => $unique_id,
    'is_express' => 0,
  )
  );
}
global $woocommerce;
$subtotal = WC()->cart->get_subtotal();
$carttotal = $woocommerce->cart->total;
$tax = WC()->cart->tax_total;
$cart_id = WC()->cart->get_cart_hash();
$currency = get_option('woocommerce_currency');
$ivyLineItems = array();
global $woocommerce, $post;
foreach ($product as $item => $values) {
  $items['name'] = $values['data']->name;
  $items['referenceId'] = $values['product_id'];
  $items['singleNet'] = $values['data']->price;
  $items['singleVat'] = 0;
  $items['amount'] = $values['data']->price;
  $items['quantity'] = $values['quantity'];
  $items['image'] = get_the_post_thumbnail_url($values['product_id'], 'thumbnail');
  $ivyLineItems[] = $items;
}

$applied_coupons = $cart->get_applied_coupons();
foreach ($applied_coupons as $coupon_code) {
  $coupon = new WC_Coupon($coupon_code);
  $amount = $coupon->get_amount();
  $lineItem = [
    'name' => $coupon_code,
    'singleNet' => -$amount,
    'singleVat' => 0,
    'amount' => -$amount
  ];
  $ivyLineItems[] = $lineItem;
  $coupon_codes[] = $coupon_code;
  if ($coupon_code) {
    $cart->apply_coupon($coupon_code);
    $carttotal = $cart->get_total('edit');
    $wpdb->update(
      $custom_cart_session_table_name,
      array(
        'coupon_code' => $coupon_codes,
        'cart_total' => $carttotal
      ),
      array('cart_hash_id' => $unique_id)
    );
  }
}

$prefill = ["email" => $formData['billing_email']];
$billingAddress = array(
  'firstName' => $formData['billing_first_name'],
  'LastName' => $formData['billing_last_name'],
  'line1' => $formData['billing_address_1'],
  'city' => $formData['billing_city'],
  'zipCode' => $formData['billing_postcode'],
  'country' => $formData['billing_country'],
);

$shippingMethods[] = [
  'price' => $shipping_total,
  'name' => $shipping_label,
  'countries' => $shipping_country,
  'reference' => $shipping_method_id
];

$price = array(
  'totalNet' => $subtotal,
  'vat' => $tax ? $tax : 0,
  'shipping' => $shipping_total,
  'total' => $carttotal,
  'currency' => $currency
);
$data = [
  'handshake' => true,
  'referenceId' => $unique_id,
  'category' => "5712",
  'price' => $price,
  'lineItems' => $ivyLineItems,
  'shippingMethods' => $shippingMethods,
  'billingAddress' => $billingAddress,
  'prefill' => $prefill,
];


$url = "https://api.stage.getivy.de/api/service/checkout/session/create";
$post = json_encode($data); # all data that going to send
$installed_payment_methods = WC()->payment_gateways()->payment_gateways();
$ivysandboxkey = $installed_payment_methods["ivy_payment"]->ivyapikey;
$option = $installed_payment_methods["ivy_payment"]->sandbox;
$ivylivekey = $installed_payment_methods["ivy_payment"]->ivyapikeylive;
$ivykey = $ivysandboxkey;
if ($option == "No") {
  $ivykey = $ivylivekey;
  $url = "https://api.getivy.de/api/service/checkout/session/create";
}
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