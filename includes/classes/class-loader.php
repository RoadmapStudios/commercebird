<?php

class CMreviewReminder {


	/**
	 * Plugin activation
	 * @return void
	 */
	public static function activate() {
		self::checkRequirements();
	}

	/**
	 * Check plugin requirements
	 * @return void
	 */
	private static function checkRequirements() {
		delete_option( 'rms-zi-admin-error' );

		//Detect WooCommerce plugin
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			//Load the plugin's translated strings
			// load_plugin_textdomain('cmbird', false, dirname(RMS_BASENAME) . '/languages');

			$error = '<strong>' . sprintf( __( '%1$s %2$s requires WooCommerce Plugin to be installed and activated.', 'cmbird' ), RMS_PLUGIN_NAME, RMS_VERSION ) . '</strong> ' . sprintf( __( 'Please <a href="%1$s" target="_blank">install WooCommerce Plugin</a>.', 'cmbird' ), 'https://wordpress.org/plugins/woocommerce/' );

			update_option( 'rms-zi-admin-error', $error );
		}
	}

	/**
	 * Initialize WordPress hooks
	 * @return void
	 */
	public static function initHooks() {
		//Init
		add_action( 'init', array( CMReviewReminder::class, 'init' ) );

		//Admin init
		add_action( 'admin_init', array( CMReviewReminder::class, 'adminInit' ) );

		//Admin notices
		add_action( 'admin_notices', array( CMReviewReminder::class, 'adminNotices' ) );
		add_action( 'wp_ajax_dismiss_cmbird_review_request_notice', array( CMReviewReminder::class, 'dismiss_cmbird_review_request_notice' ) );
		add_action( 'wp_ajax_skip_cmbird_review_request_notice', array( CMReviewReminder::class, 'skip_cmbird_review_request_notice' ) );

		//Plugins page
		add_filter( 'plugin_row_meta', array( CMReviewReminder::class, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'plugin_action_links_' . RMS_BASENAME, array( CMReviewReminder::class, 'actionLinks' ) );

		//Admin page
		$page = filter_input( INPUT_GET, 'page' );
		if ( ! empty( $page ) && $page == RMS_MENU_SLUG ) {
			add_filter( 'admin_footer_text', array( CMReviewReminder::class, 'adminFooter' ) );
		}
	}

	/**
	 * Init
	 * @return void
	 */
	public static function init() {
		//Load the plugin's translated strings
		// load_plugin_textdomain('cmbird', false, dirname(RMS_BASENAME) . '/languages');
	}

	/**
	 * Admin init
	 * @return void
	 */
	public static function adminInit() {
		// Check plugin requirements
		self::checkRequirements();
	}

	/**
	 * Admin notices
	 * @return void
	 */
	public static function adminNotices() {
		if ( get_option( 'rms-zi-admin-error' ) ) {
			$class = 'notice notice-error';
			$message = get_option( 'rms-zi-admin-error' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
		self::output_review_request_link();
	}

	/**
	 * Place review output
	 * @return void
	 */
	public static function output_review_request_link() {

		$is_dismissed = get_transient( 'cmbird_review_request_notice_dismissed' );
		if ( $is_dismissed ) {
			return;
		}

		$is_skipped = get_transient( 'cmbird_skip_review_request_notice' );
		if ( $is_skipped ) {
			return;
		}

		$cmbird_since = get_option( 'cmbird_since' );
		$now = time();
		if ( ! $cmbird_since ) {
			update_option( 'cmbird_since', $now, 'no' );
		} else {
			$diff_seconds = $now - $cmbird_since;

			if ( $diff_seconds > apply_filters( 'cmbird_show_review_request_notice_after', 10 * DAY_IN_SECONDS ) ) {
				self::render_review_request_notice();
			}
		}
		//If you find this plugin useful please show your support and rate it ★★★★★ on WordPress.org - much appreciated! :)
	}

	public static function render_review_request_notice() {
		$review_url = 'https://commercebird.com/product/commercebird';
		?>
		<div id="cmbird_review_request_notice" class="notice notice-info is-dismissible thpladmin-notice"
			data-nonce="<?php echo wp_create_nonce( 'cmbird_review_request_notice' ); ?>"
			data-action="dismiss_cmbird_review_request_notice" style="display:none">
			<h3>
				Just wanted to say thank you for using commercebird in your store.
			</h3>
			<p>We hope you had a great experience. Please leave us with your feedback to serve best to you and others. Cheers!
			</p>
			<p class="action-row">
				<button type="button" class="button button-primary"
					onclick="window.open('<?php echo $review_url; ?>', '_blank')">Review Now</button>
				<button type="button" class="button" onclick="cmbirdHideReviewRequestNotice(this)">Remind Me Later</button>
				<span class="logo"><a target="_blank" href="https://commercebird.com">
						<img src="
						<?php
						// echo esc_url(THWCFD_ASSETS_URL_ADMIN .'css/logo.svg');
						?>
								" />
					</a></span>

			</p>
		</div>
		<?php
	}

	public static function dismiss_cmbird_review_request_notice() {
		$nonce = isset( $_REQUEST['cmbird_security_review_notice'] ) ? $_REQUEST['cmbird_security_review_notice'] : false;
		if ( ! wp_verify_nonce( $nonce, 'dismiss_cmbird_review_request_notice' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}
		set_transient( 'cmbird_review_request_notice_dismissed', true, apply_filters( 'cmbird_dismissed_review_request_notice_lifespan', 1 * MONTH_IN_SECONDS ) );
	}

	public static function skip_cmbird_review_request_notice() {
		$nonse = isset( $_REQUEST['cmbird_security_review_notice'] ) ? $_REQUEST['cmbird_security_review_notice'] : false;
		if ( ! wp_verify_nonce( $nonse, 'skip_cmbird_review_request_notice' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}
		set_transient( 'cmbird_skip_review_request_notice', true, apply_filters( 'cmbird_skip_review_request_notice_lifespan', 10 * DAY_IN_SECONDS ) );
	}

	/**
	 * Plugins page
	 * @return array
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( $file === RMS_BASENAME ) {
			unset( $links[2] );

			$custom_links = array(
				'documentation' => '<a href="' . RMS_DOCUMENTATION_URL . '" target="_blank">' . __( 'Documentation', 'cmbird' ) . '</a>',
				'visit-plugin-site' => '<a href="' . RMS_PLUGIN_URL . '" target="_blank">' . __( 'Visit plugin site', 'cmbird' ) . '</a>',
			);

			$links = array_merge( $links, $custom_links );
		}

		return $links;
	}

	/**
	 * Plugins page
	 * @return array
	 */
	public static function actionLinks( $links ) {
		$custom_links = array_merge( array( 'settings' => '<a href="' . admin_url( 'admin.php?page=' . RMS_MENU_SLUG ) . '">' . __( 'Settings', 'cmbird' ) . '</a>' ), $links );

		return $custom_links;
	}

	/**
	 * Admin footer
	 * @return void
	 */
	public static function adminFooter() {
		?>
		<p><a href="https://commercebird.com/product/commercebird/" class="arg-review-link" target="_blank">
				<?php printf( __( 'If you like <strong> %s </strong> please leave us a &#9733;&#9733;&#9733;&#9733;&#9733; rating.', 'cmbird' ), RMS_PLUGIN_NAME ); ?>
			</a>
			<?php _e( 'Thank you.', 'cmbird' ); ?>
		</p>
		<?php
	}
}
