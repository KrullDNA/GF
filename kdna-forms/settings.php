<?php

use KDNA_Forms\KDNA_Forms\Settings\Settings;
// use \KDNA_Forms\KDNA_Forms\License; // Removed - license functionality removed.
// Setup wizard removed.
// use \KDNA_Forms\KDNA_Forms\Setup_Wizard\Endpoints\KDNA_Setup_Wizard_Endpoint_Save_Prefs;
// use KDNA_Forms\KDNA_Forms\TranslationsPress_Updater; // Removed - license functionality removed.

class_exists( 'KDNAForms' ) || die();

/**
 * Class KDNASettings
 *
 * Generates the KDNA Forms settings page
 */
class KDNASettings {

	/**
	 * Stores the current instance of the Settings renderer.
	 *
	 * @since 2.5
	 *
	 * @var false|Settings
	 */
	private static $_settings_renderer = false;

	/**
	 * Settings pages associated with add-ons
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @var array $addon_pages
	 */
	public static $addon_pages = array();

	/**
	 * Used to hold the addon that has been uninstalled.
	 *
	 * @since  2.5
	 *
	 * @var string $uninstalled_addon
	 */
	private static $uninstalled_addon;

	/**
	 * Adds a settings page to the KDNA Forms settings.
	 *
	 * @since  Unknown
	 * @access public
	 * @remove-in 3.0
	 * @uses KDNASettings::$addon_pages
	 *
	 * @param string|array $name      The settings page slug.
	 * @param string|array $handler   The callback function to run for this settings page.
	 * @param string       $icon_path The path to the icon for the settings tab. @deprecated.
	 */
	public static function add_settings_page( $name, $handler, $icon_path = '' ) {

		if ( ! empty( $icon_path ) ) {
			_deprecated_argument( __METHOD__, '2.5', '$icon_path has been deprecated in favor of passing a dashicons string via $name[\'icon\']' );
		}

		$title = '';
		$icon  = 'gform-icon--cog';

		// if name is an array, assume that an array of args is passed.
		if ( is_array( $name ) ) {

			/**
			 * Extracting args.
			 *
			 * @var string       $name
			 * @var string       $title
			 * @var string       $tab_label
			 * @var string|array $handler
			 * @var string       $icon
			 */
			extract(
				wp_parse_args(
					$name, array(
						'name'      => '',
						'title'     => '',
						'tab_label' => '',
						'handler'   => false,
						'icon'      => 'gform-icon--cog',
					)
				)
			);

		}

		if ( ! isset( $tab_label ) || ! $tab_label ) {
			$tab_label = $name;
		}

		/**
		 * Adds additional actions after settings pages are registered.
		 *
		 * @since Unknown
		 *
		 * @param string|array $handler The callback function being run.
		 */
		add_action( 'kdnaform_settings_' . str_replace( ' ', '_', $name ), $handler );
		self::$addon_pages[ $name ] = array( 'name' => $name, 'title' => $title, 'tab_label' => $tab_label, 'icon' => $icon );
	}

	/**
	 * Determines the content displayed on the KDNA Forms settings page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses KDNASettings::get_subview()
	 * @uses KDNASettings::kdnaforms_settings_page()
	 * @uses KDNASettings::settings_uninstall_page()
	 * @uses KDNASettings::page_header()
	 * @uses KDNASettings::page_footer()
	 *
	 * @return void
	 */
	public static function settings_page() {

		$subview = self::get_subview();

		switch ( $subview ) {
			case 'settings':
				self::kdnaforms_settings_page();
				break;
			// Old reCAPTCHA settings removed - use the reCAPTCHA add-on settings instead.
			case 'uninstall':
				self::settings_uninstall_page();
				break;
			default:
				self::page_header();

				/**
				 * Fires in the settings page depending on which page of the settings page you are in (the Subview).
				 *
				 * @since Unknown
				 *
				 * @param mixed $subview The sub-section of the main Form's settings
				 */
				do_action( 'kdnaform_settings_' . str_replace( ' ', '_', $subview ) );
				self::page_footer();
		}
	}

	/**
	 * Displays the KDNA Forms uninstall page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by KDNASettings::settings_page()
	 * @uses    KDNASettings::page_header()
	 * @uses    KDNACommon::current_user_can_any()
	 * @uses    KDNAFormsModel::drop_tables()
	 * @uses    KDNACommon::delete_directory()
	 * @uses    KDNAFormsModel::get_upload_root()
	 * @uses    KDNACommon::current_user_can_any()
	 * @uses    KDNASettings::page_footer()
	 */
	public static function settings_uninstall_page() {

		self::page_header( __( 'Uninstall KDNA Forms', 'kdnaforms' ), '' );
		if ( isset( $_POST['uninstall'] ) ) {

			check_admin_referer( 'kdnaform_uninstall', 'kdnaform_uninstall_nonce' );

			if ( ! KDNACommon::current_user_can_uninstall() ) {
				die( esc_html__( "You don't have adequate permission to uninstall KDNA Forms.", 'kdnaforms' ) );
			}

			/**
			 * Used to perform any cleanup tasks when the uninstall button has been clicked on the Forms > Settings > Uninstall page.
			 *
			 * @since 2.6.9
			 */
			do_action( 'kdnaform_uninstalling' );

			// Background tasks cleanup moved to \KDNA_Forms\KDNA_Forms\Async\KDNA_Background_Process_Service_Provider().

			// Removing cron task
			wp_clear_scheduled_hook( 'kdnaforms_cron' );

			// Dropping all tables
			KDNAFormsModel::drop_tables();

			// Removing options
			delete_option( 'rg_form_version' );
			delete_option( 'rg_gforms_disable_css' );
			delete_option( 'rg_gforms_enable_html5' );
			delete_option( 'rg_gforms_captcha_public_key' );
			delete_option( 'rg_gforms_captcha_private_key' );
			delete_option( 'rg_gforms_captcha_type' );
			delete_option( 'kdna_forms_message' );
			delete_option( 'rg_gforms_currency' );
			delete_option( 'rg_gforms_enable_akismet' );

			delete_option( 'kdna_dismissed_upgrades' );
			delete_option( 'gf_db_version' );
			delete_option( 'gf_previous_db_version' );
			delete_option( 'gf_upgrade_lock' );
			delete_option( 'gf_submissions_block' );
			delete_option( 'kdna_imported_file' );
			delete_option( 'gf_imported_theme_file' );
			delete_option( 'kdna_rest_api_db_version' );

			delete_option( 'kdnaform_api_count' );
			delete_option( 'kdnaform_email_count' );
			delete_option( 'kdnaform_enable_toolbar_menu' );
			delete_option( 'kdnaform_enable_dashboard_widget' );
			delete_option( 'kdnaform_enable_logging' );
			delete_option( 'kdnaform_pending_installation' );
			delete_option( 'kdnaform_enable_noconflict' );
			delete_option( 'kdnaform_enable_background_updates' );
			delete_option( 'kdnaform_sticky_admin_messages' );
			delete_option( 'kdnaform_upgrade_status' );
			delete_option( 'kdnaform_custom_choices' );
			delete_option( 'kdnaform_recaptcha_keys_status' );
			delete_option( 'kdnaform_upload_page_slug' );

			delete_option( 'kdnaformsaddon_kdnaformswebapi_version' );
			delete_option( 'kdnaformsaddon_kdnaformswebapi_settings' );

			// Setup wizard removed.
			// KDNAForms::get_service_container()->get( \KDNA_Forms\KDNA_Forms\Setup_Wizard\KDNA_Setup_Wizard_Service_Provider::SAVE_PREFS_ENDPOINT )->remove_setup_data();

			// License key removal skipped - license functionality removed.

			// Removing kdna forms upload folder
			KDNACommon::delete_directory( KDNAFormsModel::get_upload_root() );

			// Logging module removed.
			// gf_logging()->delete_settings();
			// gf_logging()->delete_log_files();

			delete_option( 'widget_kdnaform_widget' );
			delete_option( 'rg_gforms_default_theme' );
			delete_option( 'rg_form_original_version' );
			delete_option( 'kdnaform_version_info' );

			delete_option( 'gf_telemetry_data' );
			delete_option( 'gf_last_telemetry_run' );

			delete_transient( 'kdna_forms_license' );
			// TranslationsPress_Updater references removed - license functionality removed.

			// Deactivating plugin
			$plugin = 'kdnaforms/kdnaforms.php';
			deactivate_plugins( $plugin );
			update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );

			?>
			<div class="updated fade gf-notice notice-success" role="alert"><?php echo sprintf( esc_html__( 'KDNA Forms has been successfully uninstalled. It can be re-activated from the %splugins page%s.', 'kdnaforms' ), "<a href='plugins.php'>", '</a>' ) ?></div>
			<?php
			return;
		}

		self::uninstall_addon_message();

		?>

		<div class="gform-settings-panel">
			<header class="gform-settings-panel__header">
				<h4 class="gform-settings-panel__title"><?php esc_html_e( 'Uninstall KDNA Forms', 'kdnaforms' ); ?></h4>
			</header>
			<div class="gform-settings-panel__content">
				<p class="alert error">
					<?php esc_html_e('This operation deletes ALL KDNA Forms settings. If you continue, you will NOT be able to retrieve these settings.', 'kdnaforms'); ?>
				</p>
				<form action="" method="post">
					<?php
						if ( KDNACommon::current_user_can_uninstall() ) {

							wp_nonce_field( 'kdnaform_uninstall', 'kdnaform_uninstall_nonce' );

							$uninstall_button = sprintf(
								'<input type="submit" name="uninstall" class="button red" value="%1$s" onclick="return confirm( \'%2$s\' );" onkeypress="return confirm( \'%2$s\' );" />',
								esc_attr__( 'Uninstall KDNA Forms', 'kdnaforms' ),
								esc_js( __( "Warning! ALL KDNA Forms data, including form entries will be deleted. This cannot be undone. 'OK' to delete, 'Cancel' to stop", 'kdnaforms' ) )
							);

							/**
							 * Allows for the modification of the KDNA Forms uninstall button.
							 *
							 * @since Unknown
							 *
							 * @param string $uninstall_button The HTML of the uninstall button.
							 */
							echo apply_filters( 'kdnaform_uninstall_button', $uninstall_button ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						}
					?>
				</form>
			</div>
		</div>
		<?php

		self::uninstall_addons();

		self::page_footer();
	}

	/**
	 * Handles the uninstallation process for addons from the settings page.
	 *
	 * @since  2.5
	 */
	private static function uninstall_addons() {
		$uninstallable_addons = KDNAAddOn::get_registered_addons( true );

		// Display the complete list of addons to install.
		if ( ! rgpost( 'uninstall_addon' ) ) {
			KDNAAddOn::addons_for_uninstall( $uninstallable_addons );
			return;
		}

		// Uninstall the addon and remove it from the list of installed addons on page reload.
		check_admin_referer( 'uninstall', 'kdna_addon_uninstall' );

		foreach ( $uninstallable_addons as $key => $addon ) {
			if ( rgpost( 'addon' ) !== $addon->get_short_title() ) {
				continue;
			}

			unset( $uninstallable_addons[ $key ] );
			$addon->uninstall_addon();
			break;
		}

		KDNAAddOn::addons_for_uninstall( array_values( $uninstallable_addons ) );
	}

	/**
	 * Renders the uninstall message when an addon is uninstalled.
	 *
	 * @since  2.5
	 *
	 */
	private static function uninstall_addon_message() {
		if ( isset( self::$uninstalled_addon ) ) {
			?>
			<div class="alert success"><?php echo sprintf( esc_html__( '%s uninstalled. It can be re-activated from the %splugins page%s.', 'kdnaforms' ), esc_html__( self::$uninstalled_addon ), "<a href='plugins.php'>", '</a>' ) ?></div>
			<?php
		}
	}



	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Displays the main KDNA Forms settings page.
	 *
	 * @since  Unknown
	 * @access public
	 * @global $wpdb
	 */
	public static function kdnaforms_settings_page() {

		if ( ! KDNACommon::ensure_wp_version() ) {
			return;
		}

		self::page_header();

		wp_enqueue_style( 'kdnaform_admin' );

		// Initialize Settings renderer.
		if ( ! self::get_settings_renderer() ) {
			self::initialize_plugin_settings();
		}

		self::get_settings_renderer()->render();

		self::page_footer();

	}

	/**
	* Determine whether Orbital should be the default theme.
	*
 	* @since 2.7.15
	*
	* @return bool
	*/
    public static function is_orbital_default() {
		$theme_option = get_option( 'rg_gforms_default_theme' );

		// Fallback if the option is not set
		if ( ! $theme_option ) {
			$versions = kdna_upgrade()->get_versions();

			// New install or upgrade from version that supports this feature
			if ( version_compare( get_option( 'rg_form_original_version', $versions['version'] ), '2.7.14.2', '>=' ) ) {
				return true;
			}

			// Upgrade from version prior to this feature
			if ( version_compare( $versions['previous_db_version'], '2.7.14.2', '<' ) ) {
				return false;
			}
		}

		if ( 'orbital' == $theme_option ) {
			return true;
		}

		return false;
    }



	/**
	 * Prepare Plugin Settings fields.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	private static function plugin_settings_fields() {
		// License section removed - plugin is now free.

		$fields = array(
			'currency'            => array(
				'id'     => 'section_currency',
				'title'  => esc_html__( 'Default Currency', 'kdnaforms' ),
				'class'  => 'gform-settings-panel--half',
				'fields' => array(
					array(
						'name'          => 'currency',
						'description'   => esc_html__( 'Select the default currency for your forms. This is used for product fields, credit card fields and others.', 'kdnaforms' ),
						'type'          => 'select',
						'choices'       => RGCurrency::get_grouped_currency_options(),
						'enhanced_ui'   => false,
						'after_select'  => self::currency_message_callback(),
						'save_callback' => function( $field, $value ) {
							update_option( 'rg_gforms_currency', $value );

							return $value;
						},
					),
				),
			),
			'logging'             => array(
				'id'          => 'section_enable_logging',
				'title'       => esc_html__( 'Logging', 'kdnaforms' ),
				'description' => esc_html__( 'Enable if you would like logging within KDNA Forms. Logging allows you to easily debug the inner workings of KDNA Forms to solve any possible issues. ', 'kdnaforms' ),
				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'enable_logging',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Logging', 'kdnaforms' ),
						'save_callback' => function( $field, $value ) {
							if ( (bool) $value ) {
								KDNASettings::enable_logging();
							} else {
								KDNASettings::disable_logging();
							}

							return $value;
						},
					),
				),
			),
		);

		$fields['default_theme'] = array(
			'id'     => 'section_default_theme',
			'title'  => esc_html__( 'Default Form Theme', 'kdnaforms' ),
			'class'  => 'gform-settings-panel--half',
			'fields' => array(
					array(
					'name'          => 'default_theme',
					'type'          => 'select',
					'choices'       => array(
						array(
							'label'   => esc_html__( 'KDNA Forms 2.5 Theme', 'kdnaforms' ),
							'value'   => 'gravity-theme',
							'default' => ! self::is_orbital_default(),
						),
						array(
							'label'   => esc_html__( 'Orbital Theme (Recommended)', 'kdnaforms' ),
							'value'   => 'orbital',
							'default' => self::is_orbital_default(),
						),
					),
					'description'   => sprintf(
						'%s&nbsp;<a href="%s" target="_blank">%s<span class="screen-reader-text">%s</span>&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>',
						esc_html__( 'This theme will be used by default everywhere forms are embedded on your site', 'kdnaforms' ),
						'https://docs.kdnaforms.com/block-themes-and-style-settings/',
						esc_html__( 'Learn more about form theme and style settings.', 'kdnaforms' ),
						esc_html__( '(opens in a new tab)', 'kdnaforms' )
					),
					'save_callback' => function( $field, $value ) {
						update_option( 'rg_gforms_default_theme', $value );

						return $value;
					},
				),
			),
		);


        $fields['toolbar'] = array(
				'id'          => 'section_enable_toolbar',
				'title'       => esc_html__( 'Toolbar Menu', 'kdnaforms' ),
				'description' => esc_html__( 'Enable to display the forms menu in the WordPress top toolbar. The forms menu will display the ten forms recently opened in the form editor.', 'kdnaforms' ),
				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'enable_toolbar',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Toolbar Menu', 'kdnaforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'kdnaform_enable_toolbar_menu', (bool) $value );

							return $value;
						},
					),
				),
        );

		$fields['dashboard_widget'] = array(
				'id'          => 'section_enable_dashboard_widget',
				'title'       => esc_html__( 'Dashboard Widget', 'kdnaforms' ),
				'description' => esc_html__( 'Turn on to enable the KDNA Forms dashboard widget. The dashboard widget displays a list of forms and the number of entries each form has.', 'kdnaforms' ),
				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'enable_dashboard_widget',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Dashboard Widget', 'kdnaforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'kdnaform_enable_dashboard_widget', $value );

							return $value;
						},
						'default_value' => self::get_dashboard_widget_default_value(),
					),
				),
		);

        $fields['background_updates'] = array(
				'id'          => 'section_enable_background_updates',
				'title'       => esc_html__( 'Automatic Background Updates', 'kdnaforms' ),
				'description' => esc_html__( 'Enable to allow KDNA Forms to download and install bug fixes and security updates automatically in the background. Requires a valid license key.', 'kdnaforms' ),
				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'enable_background_updates',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Automatic Background Updates', 'kdnaforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'kdnaform_enable_background_updates', (bool) $value );

							return $value;
						},
					),
				),
			);

        $fields['no_conflict_mode'] = array(
				'id'          => 'section_conflict_mode',
				'title'       => esc_html__( 'No Conflict Mode', 'kdnaforms' ),
				'description' => esc_html__( 'Enable to prevent extraneous scripts and styles from being printed on a KDNA Forms admin pages, reducing conflicts with other plugins and themes.', 'kdnaforms' ),
				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'enable_noconflict',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'No Conflict Mode', 'kdnaforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'kdnaform_enable_noconflict', (bool) $value );

							return $value;
						},
					),
				),
			);

        $fields['akismet'] = array(
				'id'          => 'section_enable_akismet',
				'title'       => esc_html__( 'Akismet Integration', 'kdnaforms' ),
				'description' => esc_html__( 'Protect your form entries from spam using Akismet.', 'kdnaforms' ),
				'class'       => 'gform-settings-panel--half',
				'dependency'  => array( 'KDNACommon', 'has_akismet' ),
				'fields'      => array(
					array(
						'name'          => 'enable_akismet',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Akismet Integration', 'kdnaforms' ),
						'default_value' => true,
						'save_callback' => function( $field, $value ) {
							update_option( 'rg_gforms_enable_akismet', (bool) $value );

							return $value;
						},
					),
				),
			);

        $fields['telemetry'] = array(
				'id'            => 'section_enable_telemetry_collection',
				'title'         => esc_html__( 'Data Collection', 'kdnaforms' ),
				'description' => sprintf(
					esc_html__( 'We love improving the form building experience for everyone in our community. By enabling data collection, you can help us learn more about how our customers use KDNA Forms. %1$sLearn more...%2$s','kdnaforms'),
					'<a target="_blank" href="https://docs.kdnaforms.com/about-additional-data-collection/">',
					'<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'kdnaforms' ) . '</span>&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>'
				),
				'class'         => 'gform-settings-panel--half',
				'fields'        => array(
					array(
						'name'          => 'kdna_forms_dataCollection',
						'type'          => 'toggle',
						'default_value' => get_option( 'kdna_forms_dataCollection', 0 ),
						'toggle_label'  => esc_html__( 'Enable Data Collection', 'kdnaforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'kdna_forms_dataCollection', (bool) $value ? 1 : 0 );

							return $value;
						},
					),
				),
			);

		/**
		 * Allows forcing the display of the disable CSS setting.
		 *
		 * @since 2.8
		 *
		 * @param bool $kdnaform_display_disable_css_setting Indicates if the disable CSS setting should be displayed or not.
		 */
		$kdnaform_display_disable_css_setting = apply_filters( 'kdnaform_display_disable_css_setting', (bool) get_option( 'rg_gforms_disable_css' ) );

		if ( $kdnaform_display_disable_css_setting ) {
			$fields['css'] = array(
				'id'          => 'section_default_css',
				'title'       => esc_html__( 'Output Default CSS', 'kdnaforms' ),
				'description' => sprintf(
						esc_html__( 'Enable this option to output the default form CSS. Disable it if you plan to create your own CSS in a child theme. Note: after KDNA Forms 2.8, this setting will no longer appear on the settings page. If you previously had it enabled, you will need to use the %skdnaform_disable_css%s filter to disable it.', 'kdnaforms' ),
						'<a href="https://docs.kdnaforms.com/kdnaform_disable_css/" target="_blank">',
						'<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'kdnaforms' ) . '</span>&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>'
						),

				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'disable_css',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Disable CSS', 'kdnaforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'rg_gforms_disable_css', ! (bool) $value );

							return $value;
						},
					),
				),
			);
		}

		// Setup wizard removed - default to not hiding license.
		$hide_license_option = get_option( 'kdna_forms_hide_license', false );

		// Cast license option to bool.
		if ( $hide_license_option === 'true' ) {
			$hide_license_option = true;
		}

		if ( $hide_license_option === 'false' ) {
			$hide_license_option = false;
		}

		$display_license_details = ! $hide_license_option;

		/**
		 * Allows display of the license details panel to be disabled.
		 *
		 * @since 2.5.17
		 *
		 * @param bool $display_license_details Indicates if the license details panel should be displayed.
		 */
		if ( ! apply_filters( 'kdnaform_settings_display_license_details', $display_license_details ) ) {
			unset( $fields['license_key_details'] );
		}

		/**
		 * Allows the plugin settings fields to be overridden before they are displayed.
		 *
		 * @since 2.5.17
		 *
		 * @param array $fields The plugin settings fields.
		 */
		return array_values( apply_filters( 'kdnaform_plugin_settings_fields', $fields ) );
	}

	public static function license_key_details_callback() {
		// License functionality removed - plugin is now free.
		return '';
	}

	/**
	 * Callback to output any additional markup after the currency select markup.
	 *
	 * @since 2.5
	 *
	 * @return false|string
	 */
	public static function currency_message_callback() {
		// Start output buffer to capture any echoed output.
		ob_start();

		/**
		* Allows third-party code to add a message after the Currency setting markup.
		*
		* @since Unknown
		* @since 2.5 - Moved to currency message callback.
		*
		* @param string The default message.
		*/
		do_action( 'kdnaform_currency_setting_message', '' );

		$output = ob_get_clean();

		// Message was echoed, return it.
		if ( ! empty( $output ) ) {
			return $output;
		}

		return '';
	}

	/**
	 * Render the License Key Field as a callback.
	 *
	 * Callback is used so that the kdnaform_settings_key_field filter can be retained.
	 *
	 * @since 2.5
	 *
	 * @param object $field The Field Object for the rendered input.
	 *
	 * @return string
	 */
	public static function license_key_render_callback( $field ) {
		// License functionality removed - plugin is now free.
		return '';
	}

	/**
	 * Custom validation callback for the License Key Field.
	 *
	 * Callback is used so that we can skip validation if the License Key field is null.
	 *
	 * @since 2.5
	 *
	 * @param object $field The Field Object for the rendered input.
	 * @param mixed  $value The current posted field value.
	 *
	 * @return void
	 */
	public static function license_key_validation_callback( $field, $value ) {
		// License functionality removed - plugin is now free.
	}

	/**
	 * Returns the default value for the dashboard widget setting.
	 *
	 * Sometimes we get a false positive as the default value, so we need to explicitly check if it is set to '1'.
	 *
	 * @since 2.9.8
	 *
	 * @return bool
	 */
	private static function get_dashboard_widget_default_value() {
		$saved_value = get_option( 'kdnaform_enable_dashboard_widget' );

		// get_option() returns false if there is no value set
		if ( false === $saved_value ) {
			return true;
		}

		// the saved value will be either '1' or ''
		return $saved_value;
	}

	/**
	 * Initialize Plugin Settings fields renderer.
	 *
	 * @since 2.5
	 */
	public static function initialize_plugin_settings() {

		require_once( KDNACommon::get_base_path() . '/tooltips.php' );

		$initial_values = array(
			// 'license_key' removed - license functionality removed.
			'default_theme'             => get_option( 'rg_gforms_default_theme', 'gravity-theme' ),
			'currency'                  => KDNACommon::get_currency(),
			'disable_css'               => ! (bool) get_option( 'rg_gforms_disable_css' ),
			'enable_noconflict'         => (bool) get_option( 'kdnaform_enable_noconflict' ),
			'enable_akismet'            => (bool) get_option( 'rg_gforms_enable_akismet', true ),
			'enable_background_updates' => (bool) get_option( 'kdnaform_enable_background_updates' ),
			'enable_toolbar'            => (bool) get_option( 'kdnaform_enable_toolbar_menu' ),
			'enable_logging'            => (bool) get_option( 'kdnaform_enable_logging' ),
		);

		$renderer = new Settings(
			array(
				'fields'            => self::plugin_settings_fields(),
				'header'            => array(
					'icon'  => 'fa fa-gear',
					'title' => esc_html__( 'Settings: General', 'kdnaforms' ),
				),
				'input_name_prefix' => '_kdnaform_setting',
				'capability'        => 'kdnaforms_edit_settings',
				'initial_values'    => $initial_values,
				'save_callback'     => function( $values ) {
					KDNACommon::cache_remote_message();
				},
			)
		);

		self::set_settings_renderer( $renderer );

		// Process save callback.
		if ( self::get_settings_renderer()->is_save_postback() ) {
			self::get_settings_renderer()->process_postback();
		}

	}





	// # reCAPTCHA SETTINGS --------------------------------------------------------------------------------------------

	/**
	 * Display reCAPTCHA Settings page.
	 *
	 * @since 2.5
	 */
	private static function recaptcha_page() {

		if ( ! KDNACommon::ensure_wp_version() ) {
			return;
		}

		self::page_header();

		wp_enqueue_style( 'kdnaform_admin' );

		// Initialize Settings renderer.
		if ( ! self::get_settings_renderer() ) {
			self::initialize_recaptcha_settings();
		}

		self::get_settings_renderer()->render();

		self::page_footer();


	}

	/**
	 * Initialize reCAPTCHA Settings renderer.
	 *
	 * @since 2.5
	 */
	public static function initialize_recaptcha_settings() {

		require_once( KDNACommon::get_base_path() . '/tooltips.php' );

		$renderer = new Settings(
			array(
				'fields'            => array(
					array(
						'id'          => 'recpatcha',
						'title'       => esc_html__( 'reCAPTCHA Settings', 'kdnaforms' ),
						'description' => sprintf(
							'%s <strong>%s</strong> %s <a href="https://www.google.com/recaptcha/admin/create" target="_blank">%s<span class="screen-reader-text">%s</span>&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>',
							esc_html__( 'KDNA Forms integrates with reCAPTCHA, a free CAPTCHA service that uses an advanced risk analysis engine and adaptive challenges to keep automated software from engaging in abusive activities on your site. ', 'kdnaforms' ),
							esc_html__( 'Please note, only v2 keys are supported and checkbox keys are not compatible with invisible reCAPTCHA.', 'kdnaforms' ),
							esc_html__( 'These settings are required only if you decide to use the reCAPTCHA field.', 'kdnaforms' ),
							esc_html__( 'Get your reCAPTCHA Keys.', 'kdnaforms' ),
							esc_html__( '(opens in a new tab)', 'kdnaforms' )
						),
						'class'       => 'gform-settings-panel--full',
						'fields'      => array(
							array(
								'name'              => 'public_key',
								'label'             => esc_html__( 'Site Key', 'kdnaforms' ),
								'tooltip'           => kdnaform_tooltip( 'settings_recaptcha_public', null, true ),
								'type'              => 'text',
								'feedback_callback' => function( $value ) {
									$key_status = get_option( 'kdnaform_recaptcha_keys_status', null );
									return is_null( $key_status ) ? ( rgblank( $value ) ? null : false ) : (bool) $key_status;
								},
							),
							array(
								'name'              => 'private_key',
								'label'             => esc_html__( 'Secret Key', 'kdnaforms' ),
								'tooltip'           => kdnaform_tooltip( 'settings_recaptcha_private', null, true ),
								'type'              => 'text',
								'feedback_callback' => function( $value ) {
									$key_status = get_option( 'kdnaform_recaptcha_keys_status', null );
									return is_null( $key_status ) ? ( rgblank( $value ) ? null : false ) : (bool) $key_status;
								},
							),
							array(
								'name'          => 'type',
								'label'         => esc_html__( 'Type', 'kdnaforms' ),
								'tooltip'       => kdnaform_tooltip( 'settings_recaptcha_type', null, true ),
								'type'          => 'radio',
								'horizontal'    => true,
								'default_value' => 'checkbox',
								'choices'       => array(
									array(
										'label' => esc_html__( 'Checkbox', 'kdnaforms' ),
										'value' => 'checkbox',
									),
									array(
										'label' => esc_html__( 'Invisible', 'kdnaforms' ),
										'value' => 'invisible',
									),
								),
							),
							array(
								'name'     => 'reset',
								'label'    => esc_html__( 'Validate Keys', 'kdnaforms' ),
								'type'     => 'recaptcha_reset',
								'callback' => array( 'KDNASettings', 'settings_field_recaptcha_reset' ),
								'hidden'   => true,
								'validation_callback' => function( $field, $value ) {

									// If reCAPTCHA key is empty, exit.
									if ( rgblank( $value ) ) {
										return;
									}

									$values = KDNASettings::get_settings_renderer()->get_posted_values();

									// Get public, private keys, API response.
									$public_key  = rgar( $values, 'public_key' );
									$private_key = rgar( $values, 'private_key' );
									$response    = rgpost( 'g-recaptcha-response' );

									// If keys and response are provided, verify and save.
									if ( $public_key && $private_key && $response ) {

										// Log public, private keys, API response.
										KDNACommon::log_debug( __METHOD__ . '(): reCAPTCHA Site Key:' . print_r( $public_key, true ) );
										KDNACommon::log_debug( __METHOD__ . '(): reCAPTCHA Secret Key:' . print_r( $private_key, true ) );
										KDNACommon::log_debug( __METHOD__ . '(): reCAPTCHA Response:' . print_r( $response, true ) );

										// Verify response.
										$recaptcha          = new KDNA_Field_CAPTCHA();
										$recaptcha_response = $recaptcha->verify_recaptcha_response( $response, $private_key );

										// Log verification response.
										KDNACommon::log_debug( __METHOD__ . '(): reCAPTCHA verification response:' . print_r( $recaptcha_response, true ) );

										// If response is false, return validation error.
										if ( $recaptcha_response === false ) {
											$field->set_error( __( 'reCAPTCHA keys are invalid.', 'kdnaforms' ) );
										}

										// Save status.
										update_option( 'kdnaform_recaptcha_keys_status', $recaptcha_response );

									} else {

										// Delete existing status.
										delete_option( 'kdnaform_recaptcha_keys_status' );

									}

								}
							),
						),
					),
				),
				'save_button'       => array(
					'messages' => array(
						'save'  => esc_html__( 'Settings updated.', 'kdnaforms' ),
						'error' => __( 'reCAPTCHA keys are invalid.', 'kdnaforms' ),
					),
				),
				'input_name_prefix' => '_kdnaform_setting',
				'capability'        => 'kdnaforms_edit_settings',
				'initial_values'    => array(
					'public_key'  => get_option( 'rg_gforms_captcha_public_key' ),
					'private_key' => get_option( 'rg_gforms_captcha_private_key' ),
					'type'        => get_option( 'rg_gforms_captcha_type' ),
				),
				'save_callback'     => function( $values ) {

					// reCAPTCHA.
					update_option( 'rg_gforms_captcha_public_key', rgar( $values, 'public_key' ) );
					update_option( 'rg_gforms_captcha_private_key', rgar( $values, 'private_key' ) );
					update_option( 'rg_gforms_captcha_type', rgar( $values, 'type' ) );

				},
				'after_fields'      => function() {
					echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
					printf( '<script type="text/javascript" src="%s"></script>', esc_url( KDNACommon::get_base_url() . '/js/plugin_settings.js' ) );
				},
			)
		);

		self::set_settings_renderer( $renderer );

		// Process save callback.
		if ( self::get_settings_renderer()->is_save_postback() ) {
			self::get_settings_renderer()->process_postback();
		}


	}

	/**
	 * Renders a reCAPTCHA verification field.
	 *
	 * @since 2.5
	 *
	 * @param array $props Field properties.
	 * @param bool  $echo  Output the field markup directly.
	 *
	 * @return string
	 */
	public static function settings_field_recaptcha_reset( $props = array(), $echo = true ) {

		// Add setup message.
		$html = sprintf(
			'<p id="gforms_checkbox_recaptcha_message" style="margin-bottom:10px;">%s</p>',
			esc_html__( 'Please complete the reCAPTCHA widget to validate your reCAPTCHA keys:', 'kdnaforms' )
		);

		// Add reCAPTCHA container, reset input.
		$html .= '<div id="recaptcha"></div>';
		$html .= sprintf( '<input type="hidden" name="%s_%s" />', esc_attr( self::get_settings_renderer()->get_input_name_prefix() ), esc_attr( $props['name'] ) );

		return $html;

	}





	// # SETTINGS RENDERER ---------------------------------------------------------------------------------------------

	/**
	 * Gets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @return false|Settings
	 */
	private static function get_settings_renderer() {

		return self::$_settings_renderer;

	}

	/**
	 * Sets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @param Settings $renderer Settings renderer.
	 *
	 * @return bool|WP_Error
	 */
	private static function set_settings_renderer( $renderer ) {

		// Ensure renderer is an instance of Settings
		if ( ! is_a( $renderer, 'KDNA_Forms\KDNA_Forms\Settings\Settings' ) ) {
			return new WP_Error( 'Renderer must be an instance of KDNA_Forms\KDNA_Forms\Settings\Settings.' );
		}

		self::$_settings_renderer = $renderer;

		return true;

	}

	/**
	 * Handles license upgrades from the Settings page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses KDNACommon::get_key()
	 * @uses KDNACommon::post_to_manager()
	 *
	 * @return void
	 */
	public static function upgrade_license() {
		// License functionality removed - plugin is now free.
		exit;
	}

	/**
	 * Outputs the settings page header.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses SCRIPT_DEBUG
	 * @uses KDNASettings::get_subview()
	 * @uses KDNASettings::$addon_pages
	 * @uses KDNACommon::get_browser_class()
	 * @uses KDNACommon::display_dismissible_message()
	 *
	 * @param string $title   Optional. The page title to be used. Defaults to an empty string.
	 * @param string $message Optional. The message to display in the header. Defaults to empty string.
	 *
	 * @return void
	 */
	public static function page_header( $title = '', $message = '' ) {

		// Print admin styles.
		wp_print_styles( array( 'jquery-ui-styles', 'kdnaform_admin', 'kdnaform_settings' ) );

		$current_tab = self::get_subview();

		// Build left side options, always have GF Settings first and Uninstall last, put add-ons in the middle.
		$setting_tabs = array(
			'10' => array( 'name' => 'settings', 'label' => __( 'Settings', 'kdnaforms' ), 'icon' => 'gform-icon--cog' ),
		);

		// Remove an addon from the sidebar if it is uninstalled from the main uninstall page.
		if ( rgpost( 'uninstall_addon' ) ) {
			check_admin_referer( 'uninstall', 'kdna_addon_uninstall' );
			foreach ( self::$addon_pages as $key => $addon ) {
				if ( rgpost( 'addon' ) == $addon['tab_label'] ) {
					unset( self::$addon_pages[ $key ] );
					break;
				}
			}

			// Set the uninstalled addon variable to display a success message.
			self::$uninstalled_addon = rgpost( 'addon' );
		}

		if ( ! empty( self::$addon_pages ) ) {

			$sorted_addons = self::$addon_pages;
			usort(
				$sorted_addons,
				function ( $a, $b ) {
					return strnatcasecmp( $a['tab_label'], $b['tab_label'] );
				}
			);

			// Add add-ons to menu
			foreach ( $sorted_addons as $sorted_addon ) {
				$setting_tabs[] = array(
					'name'  => urlencode( $sorted_addon['name'] ),
					'label' => esc_html( $sorted_addon['tab_label'] ),
					'title' => esc_html( rgar( $sorted_addon, 'title' ) ),
					'icon'  => rgar( $sorted_addon, 'icon', 'gform-icon--cog' ),
				);
			}
		}

		// Prevent Uninstall tab from being added for users that don't have kdnaforms_uninstall capability.
		if ( KDNACommon::current_user_can_uninstall() ) {
			$setting_tabs[] = array(
				'name'  => 'uninstall',
				'label' => __( 'Uninstall', 'kdnaforms' ),
				'icon'  => 'gform-icon--trash',
			);
		}

		/**
		 * Filters the Settings menu tabs.
		 *
		 * @since Unknown
		 *
		 * @param array $setting_tabs The settings tab names and labels.
		 */
		$setting_tabs = apply_filters( 'kdnaform_settings_menu', $setting_tabs );
		ksort( $setting_tabs, SORT_NUMERIC );

		// Kind of boring having to pass the title, optionally get it from the settings tab.
		if ( ! $title ) {
			foreach ( $setting_tabs as $tab ) {
				if ( $tab['name'] == urlencode( $current_tab ) ) {
					$title = ! empty( $tab['title'] ) ? $tab['title'] : $tab['label'];
				}
			}
		}

		?>

		<div class="<?php echo esc_attr( KDNACommon::get_browser_class() ); ?>">

			<?php
			self::page_header_bar();
			echo KDNACommon::get_remote_message(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			KDNACommon::notices_section();
			?>

			<?php if ( $message ) { ?>
				<div id="message" class="updated"><p><?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p></div>
			<?php } ?>

			<div class="gform-settings__wrapper">

				<?php KDNACommon::display_dismissible_message(); ?>

				<nav class="gform-settings__navigation">
					<?php
					foreach ( $setting_tabs as $tab ) {

						// Prepare tab URL.
						$url  = add_query_arg( array( 'subview' => $tab['name'] ), admin_url( 'admin.php?page=kdna_settings' ) );

						// Get tab icon.
						$icon_markup = KDNACommon::get_icon_markup( $tab, 'gform-icon--cog' );

						printf(
							'<a href="%s" %s><span class="icon">%s</span> <span class="label">%s</span></a>',
							esc_url( $url ),
							$current_tab === $tab['name'] ? ' class="active"' : '',
							$icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							esc_html( $tab['label'] )
						);
					}
					?>
				</nav>

				<div class="gform-settings__content" id="tab_<?php echo esc_attr( $current_tab ); ?>">

		<?php
	}

	/**
	 * Outputs the Settings header bar.
	 *
	 * @since 2.5
	 */
	public static function page_header_bar() {
		?>

		<div class="wrap <?php echo esc_attr( KDNACommon::get_browser_class() ); ?>">

		<?php
		KDNACommon::gf_header();

	}

	/**
	 * Outputs the Settings page footer.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return void
	 */
	public static function page_footer() {
		?>
				</div>
				<!-- / gform-settings__content -->
			</div>
			<!-- / gform-settings__wrapper -->

		</div> <!-- / wrap -->

		<?php
	}

	/**
	 * Gets the Settings page subview based on the query string.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return string The subview.
	 */
	public static function get_subview() {

		// Default to subview, if no subview provided support.
		$subview = rgget( 'subview' ) ? rgget( 'subview' ) : rgget( 'addon' );

		if ( ! $subview ) {
			$subview = 'settings';
		}

		return $subview;
	}

	/**
	 * Handles the enabling/disabling of the Akismet Integration setting
	 *
	 * Called from KDNASettings::kdnaforms_settings_page
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by KDNASettings::kdnaforms_settings_page()
	 *
	 * @return string $akismet_setting '1' if turning on, '2' if turning off.
	 */
	public static function get_posted_akismet_setting() {

		$akismet_setting = rgpost( 'gforms_enable_akismet' );

		if ( $akismet_setting ) {
			$akismet_setting = '1';
		} elseif ( $akismet_setting === false ) {
			$akismet_setting = false;
		} else {
			$akismet_setting = '0';
		}

		return $akismet_setting;
	}

	/**
	 * Enable the KDNALogging class.
	 *
	 * @since 2.4.4.2
	 *
	 * @return bool
	 */
	public static function enable_logging() {

		// Update option.
		$enabled = update_option( 'kdnaform_enable_logging', true );

		// Prepare settings page, enable logging.
		if ( function_exists( 'gf_logging' ) ) {

			// Add settings page.
			self::add_settings_page(
				array(
					'name'      => gf_logging()->get_slug(),
					'tab_label' => gf_logging()->get_short_title(),
					'title'     => gf_logging()->plugin_settings_title(),
					'handler'   => array( gf_logging(), 'plugin_settings_page' ),
					'icon'      => gf_logging()->get_menu_icon(),
				),
				null,
				null
			);

			// Enabling all loggers by default.
			gf_logging()->enable_all_loggers();

		}

		return $enabled;

	}

	/**
	 * Disable the KDNALogging class.
	 *
	 * @since 2.4.4.2
	 *
	 * @return bool
	 */
	public static function disable_logging() {

		// Update option.
		$disabled = update_option( 'kdnaform_enable_logging', false );

		// Remove settings page, log files.
		if ( function_exists( 'gf_logging' ) ) {
			unset( self::$addon_pages[ gf_logging()->get_slug() ] );
			gf_logging()->delete_log_files();
		}

		return $disabled;

	}

}
