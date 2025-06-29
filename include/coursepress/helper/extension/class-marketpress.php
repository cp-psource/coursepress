<?php

class CoursePress_Helper_Extension_MarketPress {

	private static $installed = false;

	private static $activated = false;

	private static $base_path = 'marketpress/marketpress.php';

	public static function init() {

		if ( CP_IS_CAMPUS ) {
			return false;
		}

		add_filter( 'coursepress_extensions_plugins', array( __CLASS__, 'add_to_extensions_list' ) );
	}

	public static function add_to_extensions_list( $plugins ) {
		$download_source = 'https://github.com/cp-psource/marketpress/releases/latest';
		$external_url = 'https://cp-psource.github.io/coursepress/';
		$source_message = 'GitHub';
		$is_link = true;
		$base_path = 'marketpress/marketpress.php';

		$plugins[] = array(
			'name' => 'MarketPress',
			'slug' => 'marketpress',
			'base_path' => $base_path,
			'source' => $download_source,
			'source_message' => $source_message,
			'external_url' => $external_url,
			'external' => 'yes',
			'protocol' => 'https',
			'is_link' => $is_link,
		);

		return $plugins;
	}

	public static function installed() {
		$plugin_dir = WP_PLUGIN_DIR . '/' . self::$base_path;
		$plugin_mu_dir = WP_CONTENT_DIR . '/mu-plugins/' . self::$base_path;
		return file_exists( $plugin_dir ) || file_exists( $plugin_mu_dir );
	}

	public static function activated() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		return is_plugin_active( self::$base_path );
	}

	/**
	 * Show MP install/activation notice
	 **/
	public static function mp_notice() {
		/**
		 * check screen
		 */
		$post_type = CoursePress_Data_Course::get_post_type_name();
		$screen = get_current_screen();
		if ( ! isset( $screen->post_type ) || $post_type != $screen->post_type ) {
			return;
		}
		/**
		 * check user meta
		 */
			$user_id = get_current_user_id();
		$show = get_user_option( 'marketpress-run-notice' );
		if ( 'hide' == $show ) {
			return;
        }
        /**
         * Do not show message, when user already use WooCommerce.
         */
        if ( CoursePress_Helper_Integration_WooCommerce::$is_active ) {
            $woocommerce_is_enabled = CoursePress_Core::get_setting( 'woocommerce/enabled', false );
            if ( $woocommerce_is_enabled ) {
                return;
            }
        }
		$message = '';
		if ( ! self::installed() ) {
			$mp_settings_url = add_query_arg( array(
				'post_type' => $post_type,
				'page' => 'coursepress_settings',
				'tab' => 'extensions',
				),
				admin_url( 'edit.php' )
			);
			$message = sprintf( '<strong>%s</strong> ', __( 'Install MarketPress plugin in order to sell courses.', 'cp' ) );
			$message .= sprintf( '<a href="%s">%s</a>', $mp_settings_url, __( 'Install MarketPress', 'cp' ) );
		} elseif ( ! self::activated() ) {
			$mp_link = sprintf( '<a href="%s">%s</a>', admin_url( 'plugins.php' ), __( 'MarketPress', 'cp' ) );
			$message = sprintf( __( 'Activate %s to start selling courses.', 'cp' ), $mp_link );
		} elseif ( self::activated() ) {
			if ( defined( 'MP_VERSION' ) ) {
				if ( version_compare( MP_VERSION, '3.1.2' ) < 0 ) {
					$plugin_url = admin_url( 'plugins.php' );
					$mp = sprintf( '<a href="%s">%s</a>', $plugin_url, '<strong>MarketPress</strong>' );
					$cp = defined( 'CP_IS_PREMIUM' ) && CP_IS_PREMIUM ? '<strong>CoursePress Pro</strong>' : '<strong>CoursePress</strong>';
					$cp = sprintf( '<a href="%s">%s</a>', $plugin_url, $cp );
					$message = __( 'You are using an older version of %s plugin. %s require the latest version for compatilibity.', 'cp' );
					$message .= __( ' Update your %s now!', 'cp' );
					$message = sprintf( $message, $mp, $cp, $mp );
				}
			}
		}

		if ( ! empty( $message ) ) {
			$data = array(
				'dismissible' => true,
				'option-name' => 'marketpress-run-notice',
				'nonce' => wp_create_nonce( 'marketpress-run-notice'.$user_id ),
				'user_id' => $user_id,
			);
			echo CoursePress_Helper_UI::admin_notice( $message, 'warning', 'marketpress-run-notice', $data );
		}
	}
}