<?php

add_action('wp_footer', 'checkout_billing_email_js_ajax');
function checkout_billing_email_js_ajax()
{
    // Only on Checkout
    if (is_checkout() && !is_wc_endpoint_url()):
        ?>
        <script type="text/javascript">
            jQuery(function ($) {
                if (typeof wc_checkout_params === 'undefined')
                    return false;

                $(document.body).on("click", "#ajax-order-btn", function (evt) {
                    evt.preventDefault();

                    $.ajax({
                        type: "post",
                        // dataType : "json",
                        url: wc_checkout_params.ajax_url,
                        contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                        enctype: 'multipart/form-data',
                        data: {
                            'action': 'ajax_order',
                            'fields': $('form.checkout').serializeArray(),
                            'user_id': <?php echo get_current_user_id(); ?>,
                        },
                        success: function (result) {
                            console.log(result);
                            window.startIvyCheckout(result.redirectUrl, 'popup');// For testing (to be removed)
                        },
                        error: function (error) {
                            console.log("chngi ni hoi"); // For testing (to be removed)
                        }
                    });
                });
            });
        </script>
    <?php
    endif;
}

add_action('wp_ajax_ajax_order', 'submited_ajax_order_data');
add_action('wp_ajax_nopriv_ajax_order', 'submited_ajax_order_data');
function submited_ajax_order_data()
{
    if (isset($_POST['fields']) && !empty($_POST['fields'])) {

        $order = new WC_Order();
        $cart = WC()->cart;
        $checkout = WC()->checkout;
        $data = [];

        // Loop through posted data array transmitted via jQuery
        foreach ($_POST['fields'] as $values) {
            // Set each key / value pairs in an array
            $data[$values['name']] = $values['value'];
        }

        $cart_hash = md5(json_encode(wc_clean($cart->get_cart_for_session())) . $cart->total);
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

        // Loop through the data array
        foreach ($data as $key => $value) {
            // Use WC_Order setter methods if they exist
            if (is_callable(array($order, "set_{$key}"))) {
                $order->{"set_{$key}"}($value);

                // Store custom fields prefixed with wither shipping_ or billing_
            } elseif (
                (0 === stripos($key, 'billing_') || 0 === stripos($key, 'shipping_'))
                && !in_array($key, array('shipping_method', 'shipping_total', 'shipping_tax'))
            ) {
                $order->update_meta_data('_' . $key, $value);
            }
        }

        $order->set_created_via('checkout');
        $order->set_cart_hash($cart_hash);
        $order->set_customer_id(apply_filters('woocommerce_checkout_customer_id', isset($_POST['user_id']) ? $_POST['user_id'] : ''));
        $order->set_currency(get_woocommerce_currency());
        $order->set_prices_include_tax('yes' === get_option('woocommerce_prices_include_tax'));
        $order->set_customer_ip_address(WC_Geolocation::get_ip_address());
        $order->set_customer_user_agent(wc_get_user_agent());
        $order->set_customer_note(isset($data['order_comments']) ? $data['order_comments'] : '');
        $order->set_payment_method(isset($available_gateways[$data['payment_method']]) ? $available_gateways[$data['payment_method']] : $data['payment_method']);
        $order->set_shipping_total($cart->get_shipping_total());
        $order->set_discount_total($cart->get_discount_total());
        $order->set_discount_tax($cart->get_discount_tax());
        $order->set_cart_tax($cart->get_cart_contents_tax() + $cart->get_fee_tax());
        $order->set_shipping_tax($cart->get_shipping_tax());
        $order->set_total($cart->get_total('edit'));

        $checkout->create_order_line_items($order, $cart);
        $checkout->create_order_fee_lines($order, $cart);
        $checkout->create_order_shipping_lines($order, WC()->session->get('chosen_shipping_methods'), WC()->shipping->get_packages());
        $checkout->create_order_tax_lines($order, $cart);
        $checkout->create_order_coupon_lines($order, $cart);

        /**
         * Action hook to adjust order before save.
         * @since 3.0.0
         */
        do_action('woocommerce_checkout_create_order', $order, $data);

        // Save the order.
        $order_id = $order->save();

        do_action('woocommerce_checkout_update_order_meta', $order_id, $data);


        $product = WC()->cart->get_cart();
        $carttotal = WC()->cart->cart_contents_total;
        $current_shipping_cost = WC()->cart->get_cart_shipping_total();
        $currency = get_option('woocommerce_currency');
        $multi_items_array = [];
        global $woocommerce, $post;
        $order = new WC_Order($post->ID);
        $order_data = $order->get_data();
        $order_id = trim(str_replace('#', '', $order->get_order_number()));
        $cart = WC()->session->get('cart');
        // $WC_Cart = new WC_Cart();
        // $cart = $WC_Cart->get_cart_hash();
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
                'shipping' => $current_shipping_cost,
                'total' => $carttotal,
                'currency' => $currency
            ),
            'lineItems' => $multi_items_array,
            'required' => array('phone' => true),
            'plugin' => "m2-1.1.8"
        );
        $url = "https://api.stage.getivy.de/api/service/checkout/session/create";
        $post = json_encode($data); # all data that going to send

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT    5.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Ivy-Api-Key:wc_order_IgR3bm9AQXvvf',
            'content-type: application/json'
        ]);
        $exe = curl_exec($ch);
        $getInfo = curl_getinfo($ch);
        if ($getInfo['http_code'] === 200) {
            print_r($exe);

        }
        if (curl_error($ch)) {
            $output .= "\n" . curl_error($ch);
        }
        curl_close($ch);

    }

}

?>