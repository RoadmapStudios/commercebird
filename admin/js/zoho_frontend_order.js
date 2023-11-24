jQuery(document).ready(function($) {
    setTimeout(function() {
        // Get the order ID from the URL
        var urlPath = window.location.pathname;
        var parts = urlPath.split("/").filter(Boolean);
        var order_id_with_params = parts[parts.length - 1];
        var order_id = order_id_with_params.split("?")[0];

        let action_name = 'zoho_admin_order_sync';
        var data = {
          'action': action_name,
          'arg_order_data': order_id
        };

        // AJAX request to trigger the PHP function using jQuery.post
        jQuery.post(frontendajax.ajaxurl, data, function(response) {
            console.log(response); // Log the response from the server
        });
    }, 3000); // 3 seconds delay
});