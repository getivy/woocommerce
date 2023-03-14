<?php
// Add Ivy button on Product 
add_action('woocommerce_after_add_to_cart_button', 'ivy_button_product_page', 30);
function ivy_button_product_page()
{
    $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
    $enabled = $installed_payment_methods["ivy_payment"]->enabled;
    if ($enabled == 'yes') {
        global $product;
        $id = $product->id;
        $locale = "de";
        $getlocale = get_locale();
        if (strpos($getlocale, 'en') !== false) {
            $locale = "en";
        }   
        $option = $installed_payment_methods["ivy_payment"]->product_page_theme;
        $visibility = $installed_payment_methods["ivy_payment"]->button_show_product;
        $carttotal = WC()->cart->cart_contents_total;
        $theme = 'dark';
        if ($option == 'Yes') {
            $theme = 'light';
        }
        if ($visibility == 'Yes') {
            echo '<button class="ivy-checkout-button" type="button" data-cart-value=' . $carttotal . ' data-cart-value=' . get_option('woocommerce_currency ') . ' data-theme=' . $theme . ' data-locale=' . $locale . ' product-id=' . $id . ' ></button>';
        }
    }
}
// Add Ivy button on cart page
add_action('woocommerce_after_cart_totals', 'ivy_button_cart_page', 10);
function ivy_button_cart_page()
{
    $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
    $option = $installed_payment_methods["ivy_payment"]->cart_page_theme;
    $enabled = $installed_payment_methods["ivy_payment"]->enabled;
    if ($enabled == 'yes') {
        $locale = "de";
        $getlocale = get_locale();
        if (strpos($getlocale, 'en') !== false) {
            $locale = "en";
        }  
        $carttotal = WC()->cart->cart_contents_total;
        $theme = 'dark';
        if ($option == 'Yes') {
            $theme = 'light';
        }
    echo '<button class="ivy-checkout-button cart_button_for_ivy" type="button" data-cart-value=' . $carttotal . '
    data-cart-value=' . get_option('woocommerce_currency') . '
    data-theme=' . $theme . '
    data-locale=' . $locale . ' ></button>';
    }
}
// Add Ivy button on Checkout page
add_action('woocommerce_checkout_before_customer_details', 'ivy_button_checkout_page', 10);
function ivy_button_checkout_page()
{
    $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
    $enabled = $installed_payment_methods["ivy_payment"]->enabled;
    if ($enabled == 'yes') {
        $locale = "de";
        $getlocale = get_locale();
        if (strpos($getlocale, 'en') !== false) {
            $locale = "en";
        }  
        $carttotal = WC()->cart->cart_contents_total;
    echo '<button class="ivy-checkout-button" type="button" data-cart-value=' . $carttotal . '
    data-cart-value=' . get_option('woocommerce_currency') . '
    data-locale=' . $locale . ' ></button>';
    }
}

add_action( 'woocommerce_review_order_after_payment', 'custom_button_on_payment_method_change' );
function custom_button_on_payment_method_change() {
    $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
    $enabled = $installed_payment_methods["ivy_payment"]->enabled;
    if ($enabled == 'yes') {
    echo '<button id="checkout_ivy" style="display:none;" class="checkout_ivy" type="button">Order With Ivy</button>';
    }
  
}

?>