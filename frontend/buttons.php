<?php
// Add Ivy button on Product page
add_action('woocommerce_after_add_to_cart_button', 'ivy_button_product_page', 30);

function ivy_button_product_page()
{
    global $product;
    $id = $product->id;
    $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
    $option = $installed_payment_methods["ivy_payment"]->product_page_theme;
    $visibility = $installed_payment_methods["ivy_payment"]->button_show_product;
    $carttotal = WC()->cart->cart_contents_total;
    $theme = 'dark';
    if ($option == 'Yes') {
        $theme = 'light';
    }
    if ($visibility == 'Yes') {
        echo '<button class="ivy-checkout-button" type="button" data-cart-value=' . $carttotal . ' data-cart-value=' . get_option('woocommerce_currency ') .  ' data-theme=' . $theme . ' data-locale="de" product-id='.$id.' ></button>';
    }
}
// Add Ivy button on cart page
add_action('woocommerce_after_cart_totals', 'ivy_button_cart_page', 10);
function ivy_button_cart_page()
{
    $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
    $option = $installed_payment_methods["ivy_payment"]->cart_page_theme;
    $carttotal = WC()->cart->cart_contents_total;
    $theme = 'dark';
    if ($option == 'Yes') {
        $theme = 'light';
    }
    echo '<button class="ivy-checkout-button" type="button" data-cart-value=' . $carttotal . '
    data-cart-value=' . get_option('woocommerce_currency') . '
    data-theme=' . $theme . '
    data-locale="de" ></button>';
}
// Add Ivy button on Checkout page
add_action('woocommerce_checkout_before_customer_details', 'ivy_button_checkout_page', 10);
function ivy_button_checkout_page()
{
    $carttotal = WC()->cart->cart_contents_total;
    echo '<button class="ivy-checkout-button" type="button" data-cart-value=' . $carttotal . '
    data-cart-value=' . get_option('woocommerce_currency') . '
    data-locale="de" ></button>';
}
?>