/**
 * @description function for making ajax call.
 * @param {string} action_type - Action for which we call ajax
 */
function zoho_admin_order_ajax(data, nonce) {

    let action_name = 'zoho_admin_order_sync';
    var data = {
      'action': action_name,
      'arg_order_data': data,
      'nonce': nonce
    };

    jQuery.post(ajaxurl, data, function (_data, status) {
      console.log(status);
      if (status === 'success') {
        location.hash = 'synced';
        location.reload();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'Something wrong. Please contact Support',
        });
      }
    });

  }

  window.onload = function () {
    if (location.hash == "#synced") {
      swal("Check Order Notes", {
        icon: "success",
      });
    }
  }

  /**
   * @description function for making ajax call of Product sync.
   * @param {string} action_type - Action for which we call ajax
   */
  function zoho_admin_product_ajax(post_id, nonce) {
    // Perform the AJAX request
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
            action: 'zoho_admin_product_sync',
            post_id: post_id,
            nonce: nonce
        },
        success: function(response) {
            // Handle success
            console.log(response);
            swal({
                icon: 'success',
                title: 'Product sync successful!',
            });
            location.reload();
        },
        error: function(error) {
            // Handle error
            console.log(error);
            location.reload();
        }
    });
}


  /**
   * @description function for making ajax call of Product unmapping.
   * @param {string} productId - product Id
   */
  function zoho_admin_unmap_product_ajax(productId, nonce) {

    let action_name = 'zi_product_unmap_hook';
    var data = {
      'action': action_name,
      'product_id': productId,
      'nonce': nonce
    };

    jQuery.post(ajaxurl, data, function (_data, status) {
      console.log(status);
      if (status === 'success') {
        location.reload();
      } else {
        swal({
          icon: 'error',
          title: 'Oops...',
          text: status,
        });
      }
    });

  }

  /**
   * @description function for making ajax call of Customer unmapping.
   * @param {string} orderId - order Id
   */
   function zoho_admin_customer_unmap(orderId, nonce) {

    let action_name = 'zi_customer_unmap_hook';
    var data = {
      'action': action_name,
      'order_id': orderId,
      'nonce': nonce
    };

    jQuery.post(ajaxurl, data, function (_data, status) {
      console.log(status);
      if (status === 'success') {
        location.reload();
      } else {
        swal({
          icon: 'error',
          title: 'Oops...',
          text: status,
        });
      }
    });

  }

  /**
   * All code related to ReviewRequestNotice
   */
  function cmbirdHideReviewRequestNotice(elem){
    var wrapper = jQuery(elem).closest('div.thpladmin-notice');
    var nonce = wrapper.data("nonce");
    var data = {
      cmbird_security_review_notice: nonce,
      action: 'skip_cmbird_review_request_notice',
    };
    jQuery.post( ajaxurl, data, function(_data, status) {
      console.log(status);

    });
    jQuery(wrapper).hide(50);
  };

  jQuery( document ).on( 'click', '.thpladmin-notice .notice-dismiss', function($) {
    var wrapper = $(this).closest('div.thpladmin-notice');
    var nonce = wrapper.data("cmbird_review_request_notice");
    var data = {
      cmbird_security_review_notice: nonce,
      action: 'dismiss_cmbird_review_request_notice',
    };
    $.post( ajaxurl, data, function() {

    });
  })

  jQuery(document).ready(function($){
    setTimeout(function(){
       $("#cmbird_review_request_notice").fadeIn(500);
    }, 2000);
   });