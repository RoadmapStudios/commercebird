/**
 * @description function for making ajax call.
 * @param {string} action_type - Action for which we call ajax
 */
function zoho_admin_order_ajax(data) {

    let action_name = 'zoho_admin_order_sync';
    var data = {
      'action': action_name,
      'arg_order_data': data
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
  function zoho_admin_product_ajax(data) {

    let action_name = 'zi_product_sync_class';
    var data = {
      'action': action_name,
      'arg_product_data': data
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
   * @description function for making ajax call of Product unmapping.
   * @param {string} productId - product Id
   */
  function zoho_admin_unmap_product_ajax(productId) {

    let action_name = 'zi_product_unmap_hook';
    var data = {
      'action': action_name,
      'product_id': productId
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
   function zoho_admin_customer_unmap(orderId) {

    let action_name = 'zi_customer_unmap_hook';
    var data = {
      'action': action_name,
      'order_id': orderId
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
    jQuery.post( ajaxurl, data, function() {

    });
    jQuery(wrapper).hide(50);
  };

  jQuery( document ).on( 'click', '.thpladmin-notice .notice-dismiss', function($) {
    var wrapper = $(this).closest('div.thpladmin-notice');
    var nonce = wrapper.data("nonce");
    var action = wrapper.data("action");
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