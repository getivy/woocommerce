jQuery(document).ready(function () {
    jQuery('.ivy-checkout-button').click(function () {
        var siteurl = document.location.origin;
        var linkUrl = siteurl + '/wp-content/plugins/Ivy_Payment/checkout/express_checkout.php';
        console.log(linkUrl);
        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: linkUrl,
            success: function (response) {
                console.log(response);
                window.startIvyCheckout(response.redirectUrl, 'popup')
            }
        });
    });
});