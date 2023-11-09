<?php

namespace RMS\Admin;

defined( 'RMS_PLUGIN_NAME' ) || exit();

final class Template {
	const NAME = 'wooventory-app';
	private static  $instance = null;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );
	}

	public static function instance(): Template {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function menu(): void {
		add_menu_page(
			__( 'Wooventory', 'wooventory' ),
			__( 'Wooventory', 'wooventory' ),
			'manage_options',
			self::NAME,
			function () {
				wp_enqueue_style( self::NAME );
				wp_enqueue_style( self::NAME . '-notify', 'https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css', array(), RMS_VERSION );
				wp_enqueue_script( self::NAME );
				add_filter( 'script_loader_tag', array( $this, 'add_module' ), 10, 3 );
				printf( '<div id="%s">Loading...</div>', self::NAME );
			},
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMHB4IiBoZWlnaHQ9IjIwcHgiIHZpZXdCb3g9IjAgMCAzMDAgMzAwIiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAzMDAgMzAwIj4NCjxwYXRoIGZpbGw9IiNGQUIzMkEiIG9wYWNpdHk9IjEuMDAwMDAwIiBzdHJva2U9Im5vbmUiIA0KCWQ9Ig0KTTI1OC44MjcxMTgsMTk2Ljk4MDU0NSANCglDMjU3LjU0NzI0MSwyMTIuMTUxNDc0IDI0Ny41MTI0NjYsMjE5Ljg3MzI5MSAyMzUuMDE3MTA1LDIyNi4xNjA0NzcgDQoJQzIyMS4wNjMyMzIsMjMzLjE4MTUzNCAyMDcuNTc4NzIwLDI0MS4xMzMzNDcgMTkzLjg2NDcwMCwyNDguNjMzOTU3IA0KCUMxODUuNjU0NjE3LDI1My4xMjQyODMgMTc3LjM2MDM5NywyNTcuNDYxNjA5IDE2OS4xNzM5NjUsMjYxLjk5NDA4MCANCglDMTY0Ljc5MDA4NSwyNjQuNDIxMjY1IDE1OC43OTQ5OTgsMjYxLjQzMzM1MCAxNTguODU1NDM4LDI1NS4zODIxNzIgDQoJQzE1OS4xNjgwMzAsMjI0LjA4NTkwNyAxNTguOTU4MTE1LDE5Mi43ODQ2MzcgMTU5LjExMTI1MiwxNjEuNDg2MDA4IA0KCUMxNTkuMTIwMTAyLDE1OS42NzkxMzggMTYwLjIzMTkwMywxNTcuMDI2NjExIDE2MS42NTcxNTAsMTU2LjIwOTI0NCANCglDMTc2LjA1NjAxNSwxNDcuOTUxNTM4IDE5MC42NTc3NDUsMTQwLjA0ODc5OCAyMDUuMTM4NjU3LDEzMS45MzI0MDQgDQoJQzIxMy40NDA0MzAsMTI3LjI3OTM1OCAyMjEuNjA0ODc0LDEyMi4zODE5OTYgMjI5Ljg2ODQ4NCwxMTcuNjU5OTczIA0KCUMyMzUuNjI1MjkwLDExNC4zNzAzODQgMjQxLjQzOTY5NywxMTEuMTgxMjU5IDI0Ny4yMzg5ODMsMTA3Ljk2NjM4NSANCglDMjUzLjYwNDM0MCwxMDQuNDM3Njk4IDI1Ni4yNjM1ODAsMTA1LjQyMjQzMiAyNTguNjk5MjE5LDExMy4wMDc4NTggDQoJQzI1OC4yOTQ2NzgsMTE0LjcyNjQ2MyAyNTguMDI2NTIwLDExNS43MDgwMzEgMjU4LjAyNTU3NCwxMTYuNjg5ODU3IA0KCUMyNTguMDAwNzYzLDE0Mi4wMDQ2ODQgMjU3Ljk5MDI2NSwxNjcuMzE5NTUwIDI1OC4wMzcyOTIsMTkyLjYzNDMwOCANCglDMjU4LjA0MDAwOSwxOTQuMDgzNTQyIDI1OC41NTIwOTQsMTk1LjUzMTgxNSAyNTguODI3MTE4LDE5Ni45ODA1NDUgDQp6Ii8+DQo8cGF0aCBmaWxsPSIjRjUzMDYyIiBvcGFjaXR5PSIxLjAwMDAwMCIgc3Ryb2tlPSJub25lIiANCglkPSINCk00Ny44NDcxMjIsMTExLjk4OTYxNiANCglDNDguODY0OTE4LDEwOC4wOTkwOTggNTIuNDI2NzY5LDEwNS44OTAzMzUgNTYuMjAyMzQzLDEwNy43NjY3OTIgDQoJQzY0LjQyOTA0NywxMTEuODU1NDY5IDcyLjM1MjYxNSwxMTYuNTUwNTY4IDgwLjQzOTExMCwxMjAuOTI1Njc0IA0KCUM5Mi4yNzI1MjIsMTI3LjMyODAxMSAxMDQuMTc0MDA0LDEzMy42MDUxMDMgMTE1Ljk4MjEyNCwxNDAuMDUzNDIxIA0KCUMxMjQuODA4MDA2LDE0NC44NzMxNTQgMTMzLjUxNTgwOCwxNDkuOTA4NjMwIDE0Mi4zMTA1MzIsMTU0Ljc4NjEzMyANCglDMTQ1LjQ2NDgyOCwxNTYuNTM1NDkyIDE0Ni4wNjQwNTYsMTU5LjIwMTk1MCAxNDYuMDUzODc5LDE2Mi41NzUzMzMgDQoJQzE0NS45NjE0NzIsMTkzLjIwODY0OSAxNDUuNzEyNzk5LDIyMy44NDY4MzIgMTQ2LjE4Nzk0MywyNTQuNDczMDUzIA0KCUMxNDYuMzE1ODcyLDI2Mi43MTg1OTcgMTQxLjM4NDI3NywyNjMuNjY0NjEyIDEzNS4xNzY5ODcsMjYwLjkxMzk3MSANCglDMTI4LjA3MzgyMiwyNTcuNzY2Mzg4IDEyMS40MDA4OTQsMjUzLjY0MTg5MSAxMTQuNTY1NTUyLDI0OS44OTcxMTAgDQoJQzEwMC42NDgwNTYsMjQyLjI3MjMwOCA4Ni42MTc2MTUsMjM0Ljg0MDU0NiA3Mi44OTQ2NjEsMjI2Ljg3OTQyNSANCglDNjYuMTkxNjY2LDIyMi45OTA4MTQgNTguMTEwNTU4LDIyMC42NDQ4MjEgNTMuOTQ2NjE3LDIxMy4zNjYxMzUgDQoJQzUxLjI5MTEwNywyMDguNzI0MjEzIDQ5LjQ2NTkzNSwyMDMuNjA3Mjg1IDQ3LjQyNzA3MSwxOTcuOTcxNzcxIA0KCUM0Ny43MTU0MzksMTk2LjU5MzAwMiA0Ny45NTkxMDYsMTk1Ljk0NTY3OSA0Ny45NTg1NTcsMTk1LjI5ODU2OSANCglDNDcuOTM0OTk4LDE2Ny41Mjg5MDAgNDcuODg5NzMyLDEzOS43NTkyNjIgNDcuODQ3MTIyLDExMS45ODk2MTYgDQp6Ii8+DQo8cGF0aCBmaWxsPSIjMzM4MkY1IiBvcGFjaXR5PSIxLjAwMDAwMCIgc3Ryb2tlPSJub25lIiANCglkPSINCk0xMzIuOTk5MDY5LDEzNi45MzIzNzMgDQoJQzExNi4yMzc3MDksMTI3LjY4OTQwMCA5OS40ODAxMjUsMTE4LjQzOTU2OCA4Mi43MTI4NzUsMTA5LjIwNzI5OCANCglDNzcuMDYwODI5LDEwNi4wOTUyMDcgNzEuMjA5MzA1LDEwMy4yOTkyNjMgNjUuNzk5NDYxLDk5LjgxNDY3NCANCglDNjMuNDIxODQ0LDk4LjI4MzIwMyA2MC42ODQxMTYsOTUuNTI5MTIxIDYwLjQwOTE3Miw5My4wNzI3ODQgDQoJQzYwLjE4NDk5NCw5MS4wNzAwMTUgNjIuOTE1MjUzLDg3Ljg1NDY3NSA2NS4xMzgyOTgsODYuNjA0NDE2IA0KCUM3OC41NDg0MDksNzkuMDYyNDg1IDkyLjIyOTY0NSw3Mi4wMDQ4NTIgMTA1LjczOTkxNCw2NC42MzgwMTYgDQoJQzExNS4wNDUzMzQsNTkuNTYzOTg0IDEyNC4zNjE4NDcsNTQuNDgyOTAzIDEzMy40MDU3NjIsNDguOTY2MjE3IA0KCUMxMzkuNTM5MjE1LDQ1LjIyNDg4MCAxNDUuODUyOTIxLDQzLjIzNTUwOCAxNTMuMjk3OTc0LDQ0LjQ4MDUzMCANCglDMTUzLjQxMDA0OSw0Ny4yMzU4MjUgMTUzLjA3NTU5Miw0OS41NDM1MzcgMTUzLjA2NTEwOSw1MS44NTI3MjYgDQoJQzE1Mi45NTQ4NjUsNzYuMTQ5NjczIDE1Mi44OTYzNjIsMTAwLjQ0Njg1NCAxNTIuNDYzMjI2LDEyNC44MDg2MjQgDQoJQzE0Ni45ODc0ODgsMTI3LjQ3NzMyNSAxNDEuNzkyNDE5LDEyOS45NDA4MjYgMTM2LjgwMzg2NCwxMzIuNzY4NjE2IA0KCUMxMzUuMjQ4NTk2LDEzMy42NTAyMzggMTM0LjI1MTYxNywxMzUuNTE2NzI0IDEzMi45OTkwNjksMTM2LjkzMjM3MyANCnoiLz4NCjxwYXRoIGZpbGw9IiMyQjZFQkYiIG9wYWNpdHk9IjEuMDAwMDAwIiBzdHJva2U9Im5vbmUiIA0KCWQ9Ig0KTTE1Mi44MjM1NzgsMTI0Ljc0Mzk3MyANCglDMTUyLjg5NjM2MiwxMDAuNDQ2ODU0IDE1Mi45NTQ4NjUsNzYuMTQ5NjczIDE1My4wNjUxMDksNTEuODUyNzI2IA0KCUMxNTMuMDc1NTkyLDQ5LjU0MzUzNyAxNTMuNDEwMDQ5LDQ3LjIzNTgyNSAxNTMuNzQ0NDAwLDQ0LjU1MzUyOCANCglDMTYzLjQ1MjAxMSw0Mi4yMzkxMjQgMTcwLjUzNDY5OCw0OC4wMjcxNDUgMTc4LjEwNjE4Niw1Mi4wNzg1MTggDQoJQzE5Mi4zMTQwMTEsNTkuNjgwODkzIDIwNi40Mzk4MDQsNjcuNDM4MjAyIDIyMC41NDQwMDYsNzUuMjMxOTQxIA0KCUMyMjguMDAxNjk0LDc5LjM1Mjk0MyAyMzUuNDA5NzQ0LDgzLjU3Mzk1MiAyNDIuNzA2NzI2LDg3Ljk3MTI3NSANCglDMjQ2LjMxNTQ2MCw5MC4xNDU5NjYgMjQ1LjkzOTgzNSw5NS45NjA5NTMgMjQyLjM2NDMzNCw5Ny45OTk2MTkgDQoJQzIyNi45MDc2NjksMTA2LjgxMjcwNiAyMTEuNTIyMjMyLDExNS43NTIzNzMgMTk2LjAwMDM2NiwxMjQuNDQ4NzE1IA0KCUMxODguNzE5NzcyLDEyOC41Mjc3NzEgMTgxLjE4ODc4MiwxMzIuMTU5ODgyIDE3My4yMjY5NTksMTM1Ljc1NzA5NSANCglDMTY2LjA2Mjk0MywxMzEuOTI3NzY1IDE1OS40NDMyNjgsMTI4LjMzNTg2MSAxNTIuODIzNTc4LDEyNC43NDM5NzMgDQp6Ii8+DQo8cGF0aCBmaWxsPSIjMUI1Mzk3IiBvcGFjaXR5PSIxLjAwMDAwMCIgc3Ryb2tlPSJub25lIiANCglkPSINCk0xNTIuNDYzMjI2LDEyNC44MDg2MjQgDQoJQzE1OS40NDMyNjgsMTI4LjMzNTg2MSAxNjYuMDYyOTQzLDEzMS45Mjc3NjUgMTcyLjg1NzAyNSwxMzUuODE5MTIyIA0KCUMxNjYuNTYzOTk1LDEzOS45NjE1MDIgMTYwLjEzODA2MiwxNDMuODc4NzM4IDE1My41NzIzNDIsMTQ3LjU0NTY3MCANCglDMTUyLjgyMDUyNiwxNDcuOTY1NTQ2IDE1MS4xODI4MzEsMTQ3LjE4MTY0MSAxNTAuMTMzNTE0LDE0Ni42MjI1NTkgDQoJQzE0NC41MzIwMjgsMTQzLjYzODA0NiAxMzguOTg3MDE1LDE0MC41NDc1MzEgMTMzLjIxMDc3MCwxMzcuMjEzMDEzIA0KCUMxMzQuMjUxNjE3LDEzNS41MTY3MjQgMTM1LjI0ODU5NiwxMzMuNjUwMjM4IDEzNi44MDM4NjQsMTMyLjc2ODYxNiANCglDMTQxLjc5MjQxOSwxMjkuOTQwODI2IDE0Ni45ODc0ODgsMTI3LjQ3NzMyNSAxNTIuNDYzMjI2LDEyNC44MDg2MjQgDQp6Ii8+DQo8L3N2Zz4=',
			29
		);
	}

	/**
	 * It will add module attribute to script tag.
	 *
	 * @param string $tag of script.
	 * @param string $id  of script.
	 *
	 * @return string
	 */
	public function add_module( string $tag, string $id ): string {
		if ( self::NAME === $id ) {
			$tag = str_replace( '<script ', '<script type="module" ', $tag );
		}

		return $tag;
	}

	/**
	 * It loads scripts based on plugin's mode, dev or prod.
	 *
	 * @return void
	 */
	public function scripts(): void {
		global $wp_roles;
		if ( true ) {
			wp_register_style( self::NAME, 'http://localhost:5000/src/main.css', array(), RMS_VERSION );
			wp_register_script( self::NAME, 'http://localhost:5000/src/main.js', array(), RMS_VERSION, true );
		}
		wp_register_style( self::NAME, RMS_DIR_URL . 'admin/assets/dist/index.css', array(), RMS_VERSION );
		wp_register_script( self::NAME, RMS_DIR_URL . 'admin/assets/dist/index.js', array(), RMS_VERSION, true );
		wp_add_inline_style( self::NAME, '#wpcontent, .auto-fold #wpcontent{padding-left: 0px} #wpcontent .notice, #wpcontent #message{display: none} input[type=checkbox]:checked::before{content:unset}' );
		wp_localize_script(
			self::NAME,
			'zoho_inventory_admin',
			array(
				'security_token' => wp_create_nonce( self::NAME ),
				'redirect_uri'   => admin_url( 'admin.php?page=wooventory-app' ),
				'url'            => admin_url( 'admin-ajax.php' ),
				'wc_tax_enabled' => wc_tax_enabled(),
				'roles'          => $wp_roles->get_names(),
				'b2b_enabled'    => class_exists( 'Addify_B2B_Plugin' ),
			),
		);
	}
}
