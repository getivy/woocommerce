jQuery(document).ready(function () {
    setTimeout(function() {
        checkPaymentMethod();
      }, 1000);

    jQuery('form.checkout').on('change', 'input[name="payment_method"]', function() {
    checkPaymentMethod();
    });
    jQuery('body').on('click', '.checkout_ivy', function() {
      startNormalCheckout();
    });
    jQuery('.ivy-checkout-button').click(function () {
        var id = jQuery(".ivy-checkout-button").attr("product-id");
        var quantity = jQuery(".qty").val();
        jQuery.post(window.location.href, {
            'quantity': quantity,
            'add-to-cart': id
        }, function (data, status) {
            if (status == "success") {
                expressCheckout();
                
            }
        });
    });
    // Express Checkout Function
    function expressCheckout() {
        var linkUrl = document.location.origin + '/wp-content/plugins/Ivy_Payment/checkout/express_checkout.php';
        console.log(linkUrl);
        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: linkUrl,
            success: function (response) {
                window.startIvyCheckout(response.redirectUrl, 'popup')
            }
        });
    }

    function startNormalCheckout() {
        var linkUrl = document.location.origin + '/wp-content/plugins/Ivy_Payment/checkout/normal_checkout.php';
        var formData = jQuery('form.checkout').serializeArray();
        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: linkUrl,
            data: formData,
            success: function (response) {
                window.startIvyCheckout(response.redirectUrl, 'popup')
            }
        });
    }
    function checkPaymentMethod() {
          
        if (jQuery('#payment_method_ivy_payment').is(':checked')) {
        jQuery('#place_order').hide();
        jQuery('#checkout_ivy').show();
       
        } else {
        jQuery('#place_order').show();
        jQuery('#checkout_ivy').hide();
        }
      }   
    
    jQuery('button[name="update_cart"]').click(function() {
        jQuery(document.body).trigger('update_checkout');
        jQuery(document).ajaxComplete(function() {
          location.reload();
        });
      });

    


      
});