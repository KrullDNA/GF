<?php

// If KDNA Forms Block Manager is not available, do not run.
if ( ! class_exists( 'KDNA_Blocks' ) || ! defined( 'ABSPATH' ) ) {
	exit;
}

class KDNA_Block_Form extends KDNA_Block {

	/**
	 * Contains an instance of this block, if available.
	 *
	 * @since  2.4.10
	 * @var    KDNA_Block $_instance If available, contains an instance of this block.
	 */
	private static $_instance = null;

	/**
	 * Block type.
	 *
	 * @since 2.4.10
	 * @var   string
	 */
	public $type = 'kdnaforms/form';

	/**
	 * Handle of primary block script.
	 *
	 * @since 2.4.10
	 * @var   string
	 */
	public $script_handle = 'kdnaform_editor_block_form';

	/**
	 * Handle of primary block style.
	 *
	 * @since 2.5.6
	 * @var   string
	 */
	public $style_handle = 'kdnaform_editor_block_form';

	public function __construct() {
		$this->assign_attributes();
	}

	private function assign_attributes() {
		$default_attributes = KDNAForms::get_service_container()->get( \KDNA_Forms\KDNA_Forms\Blocks\KDNA_Blocks_Service_Provider::FORM_BLOCK_ATTRIBUTES );
		$attributes         = apply_filters( 'kdnaform_form_block_attributes', $default_attributes );

		array_walk( $attributes, function ( &$value ) {
			$value = array( 'type' => $value['type'] );
		} );

		$this->attributes = $attributes;
	}

	/**
	 * Get instance of this class.
	 *
	 * @since  2.4.10
	 *
	 * @return KDNA_Block_Form
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;

	}


	// # SCRIPT / STYLES -----------------------------------------------------------------------------------------------
	public function register_block_assets() {
		parent::register_block_assets();
		if ( function_exists( 'wp_enqueue_block_style' ) && is_admin() ) {
			wp_enqueue_block_style( $this->type, array( 'handle' => 'kdna_forms_theme_reset' ) );
			wp_enqueue_block_style( $this->type, array( 'handle' => 'kdna_forms_theme_foundation' ) );
			wp_enqueue_block_style( $this->type, array( 'handle' => 'kdna_forms_theme_framework' ) );
			wp_enqueue_block_style( $this->type, array( 'handle' => 'kdna_forms_orbital_theme' ) );
		}
	}


	/**
	 * Register scripts for block.
	 *
	 * @since  2.4.10
	 *
	 * @return array
	 */
	public function scripts() {
		return array();
	}

	/**
	 * Localize Form block script.
	 *
	 * @since  2.4.10
	 *
	 * @param array $script Script arguments.
	 */
	public function localize_script( $script = array() ) {

		wp_localize_script(
			$script['handle'],
			'kdnaform_block_form',
			array(
				'adminURL' => admin_url( 'admin.php' ),
				'forms'    => $this->get_forms(),
				'preview'  => KDNACommon::get_base_url() . '/images/kdna_block_preview.svg',
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $script['handle'], 'kdnaforms', KDNACommon::get_base_path() . '/languages' );
		}

	}

	/**
	 * Register styles for block.
	 *
	 * @since  2.4.10
	 *
	 * @return array
	 */
	public function styles() {

		// Prepare styling dependencies.
		$deps = array( 'wp-edit-blocks' );

		// Add KDNA Forms styling if CSS is enabled.
		if ( ! KDNACommon::is_frontend_default_css_disabled() ) {
			$deps = array_merge( $deps, array( 'gforms_reset_css', 'kdnaform_basic', 'gforms_formsmain_css', 'gforms_ready_class_css', 'gforms_browsers_css', 'kdnaform_theme' ) );

			/**
			 * Allows users to disable the main theme.css file from being loaded on the Front End.
			 *
			 * @since 2.5-beta-3
			 *
			 * @param boolean Whether to disable the theme css.
			 */
			$disable_theme_css = apply_filters( 'kdnaform_disable_form_theme_css', false );

			if ( ! $disable_theme_css ) {
				$deps[] = 'kdnaform_theme';
			}
		}

		$dev_min = defined( 'KDNA_SCRIPT_DEBUG' ) && KDNA_SCRIPT_DEBUG ? '' : '.min';

		return array(
			array(
				'handle'  => $this->style_handle,
				'src'     => KDNACommon::get_base_url() . "/assets/css/dist/blocks{$dev_min}.css",
				'deps'    => $deps,
				'version' => defined( 'KDNA_SCRIPT_DEBUG' ) && KDNA_SCRIPT_DEBUG ? filemtime( KDNACommon::get_base_path() . "/assets/css/dist/blocks{$dev_min}.css" ) : KDNAForms::$version,
			),
		);

	}


	// # BLOCK RENDER -------------------------------------------------------------------------------------------------

	/**
	 * Display block contents on frontend.
	 *
	 * @since  2.4.10
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string
	 */
	public function render_block( $attributes = array() ) {
		KDNAForms::get_service_container()->get( 'block_attributes' )->store( $attributes );

		// Prepare variables.
		$form_id      = rgar( $attributes, 'formId' ) ? $attributes['formId'] : false;
		$title        = isset( $attributes['title'] ) ? $attributes['title'] : true;
		$description  = isset( $attributes['description'] ) ? $attributes['description'] : true;
		$ajax         = isset( $attributes['ajax'] ) ? $attributes['ajax'] : false;
		$tabindex     = isset( $attributes['tabindex'] ) ? intval( $attributes['tabindex'] ) : 0;
		$field_values = isset( $attributes['fieldValues'] ) ? $attributes['fieldValues'] : '';

		// If form ID was not provided or form does not exist, return.
		if ( ! $form_id || ( $form_id && ! KDNAAPI::get_form( $form_id ) ) ) {
			return '';
		}

		// Use KDNA Forms function for REST API requests.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {

			// Start output buffering.
			ob_start();

			// Prepare field values.
			if ( ! empty( $field_values ) ) {
				$field_values = str_replace( '&#038;', '&', $field_values );
				parse_str( $field_values, $field_value_array );
				$field_values = stripslashes_deep( $field_value_array );
			}

			// Get form output string.
			$form_string = gravity_form( $form_id, $title, $description, false, $field_values, $ajax, $tabindex, false, rgar( $attributes, 'theme' ), json_encode( $attributes ) );

			// Get output buffer contents.
			$buffer_contents = ob_get_contents();
			ob_end_clean();

			// Return buffer contents with form string.
			return $buffer_contents . $form_string; // nosemgrep audit.php.wp.security.xss.block-attr

		}

		// Encode field values.
		$field_values = htmlspecialchars_decode( $field_values );
		$field_values = str_replace( array( '&#038;', '&#091;', '&#093;' ), array( '&', '[', ']' ), $field_values );
		parse_str( $field_values, $field_value_array ); //parsing query string like string for field values and placing them into an associative array
		$field_values = stripslashes_deep( $field_value_array );

		// If no field values are set, set field values to an empty string
		if ( empty( $field_values ) ) {
			$field_values = '';
		}

		return gravity_form( $form_id, $title, $description, false, $field_values, $ajax, $tabindex, false, rgar( $attributes, 'theme' ), json_encode( $attributes ) ); // nosemgrep audit.php.wp.security.xss.block-attr

	}

}

// Register block.
if ( true !== ( $registered = KDNA_Blocks::register( KDNA_Block_Form::get_instance() ) ) && is_wp_error( $registered ) ) {

	// Log that block could not be registered.
	KDNACommon::log_error( 'Unable to register block; ' . $registered->get_error_message() );

}
