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
        var express_data = {
            "action": "express_function"
        };

        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: ajax.url,
            data: express_data,
            success: function (response) {
                console.log(response);
                window.startIvyCheckout(response.redirectUrl, 'popup')
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            }
        });
    }

    function startNormalCheckout() {

        var obj = {};
          
            var form_data = jQuery('form.checkout').serializeArray();
                jQuery.each(form_data, function (key, input) {
                obj[input.name] = input.value;
            });
        
            var normal_data = {
                "action": "normal_function",
                "data": obj
            };

            jQuery.ajax({
                type: "post",
                datatype: "json",
                url: ajax.url,
                data: normal_data,
            
                success: function (response) {
                    console.log(response);
                    window.startIvyCheckout(response, 'popup')
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
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