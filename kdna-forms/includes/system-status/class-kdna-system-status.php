<?php

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

/**
 * Class KDNA_System_Status
 *
 * Handles the system status page.
 *
 * @since 2.2
 */
class KDNA_System_Status {

	/**
	 * Determines which system status page to display.
	 *
	 * @since  2.2
	 * @access public
	 */
	public static function system_status_page() {

		$subview = rgget( 'subview' ) ? rgget( 'subview' ) : 'report';

		switch ( $subview ) {
			case 'report':
				KDNA_System_Report::system_report();
				break;
			case 'updates':
				KDNA_Update::updates();
				break;
			default:
				/**
				 * Fires when the settings page view is determined
				 *
				 * Used to add additional pages to the form settings
				 *
				 * @since Unknown
				 *
				 * @param string $subview Used to complete the action name, allowing an additional subview to be detected
				 */
				do_action( "kdnaform_system_status_page_{$subview}" );
		}

	}

	/**
	 * Get System Status page subviews.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @return array
	 */
	public static function get_subviews() {

		// Define default subview.
		$subviews = array(
			10 => array(
				'name'  => 'report',
				'label' => __( 'System Report', 'kdnaforms' ),
			),
		);

		// Add Update subview if user has capabilities.
		if ( current_user_can( 'install_plugins' ) ) {
			$subviews[20] = array(
				'name'  => 'updates',
				'label' => __( 'Updates', 'kdnaforms' ),
			);
		}

		/**
		 * Modify menu items which will appear in the System Status menu.
		 *
		 * @since 2.2
		 * @param array $subviews An array of menu items to be displayed on the System Status page.
		 */
		$subviews = apply_filters( 'kdnaform_system_status_menu', $subviews );

		ksort( $subviews, SORT_NUMERIC );

		return $subviews;

	}

	/**
	 * Get current System Status subview.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @return string
	 */
	public static function get_current_subview() {

		return rgempty( 'subview', $_GET ) ? 'report' : rgget( 'subview' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	}

	/**
	 * Render System Status page header.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @param string $title Page title.
	 *
	 * @uses KDNACommon::display_dismissible_message()
	 * @uses KDNACommon::get_base_url()
	 * @uses KDNACommon::get_browser_class()
	 * @uses KDNACommon::get_remote_message()
	 * @uses GFSystemStatus::get_current_subview()
	 * @uses GFSystemStatus::get_subviews()
	 */
	public static function page_header( $title = '' ) {
	    KDNAForms::admin_header( self::get_subviews(), false );
	}

	/**
	 * Render System Status page footer.
	 *
	 * @since  2.2
	 * @access public
	 */
	public static function page_footer() {
	    KDNAForms::admin_footer();
	}
}
