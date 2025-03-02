<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CMBIRD_Email_Purchase_Order extends WC_Email {

	public function __construct() {
		$this->id = 'wc_email_purchase_order';
		$this->title = __( 'Purchase Order', 'commercebird' );
		$this->description = __( 'This email is sent when a new purchase order is created.', 'commercebird' );
		$this->heading = __( 'New Purchase Order', 'commercebird' );
		$this->subject = __( 'Purchase Order {order_number}', 'commercebird' );

		// Use WooCommerce's default "Customer Invoice / Pending Payment" template
		$this->template_html = 'emails/customer-invoice.php';
		$this->template_plain = 'emails/plain/customer-invoice.php';
		$this->template_base = WC()->plugin_path() . '/templates/';

		$this->placeholders = array(
			'{order_number}' => '',
		);

		// Trigger when a purchase order is placed
		add_action( 'cmbird_trigger_purchase_order_email', array( $this, 'trigger' ), 10, 2 );

		parent::__construct();
	}

	public function trigger( $order_id, $order = false ) {
		if ( ! $order ) {
			$order = wc_get_order( $order_id );
		}

		if ( $order->get_type() !== 'shop_purchase' ) {
			return;
		}

		$this->placeholders['{order_number}'] = 'PO-' . $order->get_id();
		$this->object = $order;
		$this->recipient = get_option( 'admin_email' );

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order' => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text' => false,
				'email' => $this,
			),
			'',
			WC()->plugin_path() . '/templates/'
		);
	}

	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order' => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text' => true,
				'email' => $this,
			),
			'',
			WC()->plugin_path() . '/templates/'
		);
	}
}