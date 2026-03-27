<?php
/**
 * KDNA Forms Elementor Integration Loader
 *
 * Handles registration of the KDNA Forms widget for Elementor,
 * including category registration, widget registration, and
 * popup compatibility.
 *
 * @package KDNA_Forms
 * @subpackage Elementor
 * @since 2.9.30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KDNA_Elementor_Loader
 *
 * Main loader for KDNA Forms Elementor integration.
 */
class KDNA_Elementor_Loader {

	/**
	 * Singleton instance.
	 *
	 * @var KDNA_Elementor_Loader|null
	 */
	private static $instance = null;

	/**
	 * Minimum Elementor version required.
	 *
	 * @var string
	 */
	const MINIMUM_ELEMENTOR_VERSION = '3.0.0';

	/**
	 * Get singleton instance.
	 *
	 * @return KDNA_Elementor_Loader
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor. Hooks into WordPress and Elementor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize the Elementor integration if Elementor is active and meets version requirements.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! $this->is_elementor_active() ) {
			return;
		}

		if ( ! $this->is_elementor_version_compatible() ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );
			return;
		}

		// Register widget category.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_widget_categories' ) );

		// Register widgets.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Enqueue editor styles.
		add_action( 'elementor/editor/before_enqueue_styles', array( $this, 'enqueue_editor_styles' ) );

		// Enqueue front-end styles.
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_frontend_styles' ) );

		// Enqueue front-end scripts for popup compatibility.
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_frontend_scripts' ) );

		// Add popup compatibility script inline.
		add_action( 'wp_footer', array( $this, 'render_popup_compatibility_script' ) );
	}

	/**
	 * Check if Elementor is installed and activated.
	 *
	 * @return bool
	 */
	private function is_elementor_active() {
		return did_action( 'elementor/loaded' );
	}

	/**
	 * Check if the installed Elementor version meets the minimum requirement.
	 *
	 * @return bool
	 */
	private function is_elementor_version_compatible() {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return false;
		}

		return version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' );
	}

	/**
	 * Display admin notice for incompatible Elementor version.
	 *
	 * @return void
	 */
	public function admin_notice_minimum_elementor_version() {
		$message = sprintf(
			/* translators: 1: Plugin name, 2: Required Elementor version */
			esc_html__( '%1$s requires Elementor version %2$s or greater.', 'kdnaforms' ),
			'<strong>' . esc_html__( 'KDNA Forms Elementor Widget', 'kdnaforms' ) . '</strong>',
			self::MINIMUM_ELEMENTOR_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Register the KDNA Forms widget category in Elementor.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_widget_categories( $elements_manager ) {
		$elements_manager->add_category(
			'kdna-forms',
			array(
				'title' => esc_html__( 'KDNA Forms', 'kdnaforms' ),
				'icon'  => 'eicon-form-horizontal',
			)
		);
	}

	/**
	 * Register the KDNA Forms widget with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		require_once __DIR__ . '/widgets/class-kdna-forms-widget.php';
		$widgets_manager->register( new KDNA_Forms_Widget() );
	}

	/**
	 * Enqueue styles for the Elementor editor panel.
	 *
	 * @return void
	 */
	public function enqueue_editor_styles() {
		$css_file = __DIR__ . '/assets/css/kdna-elementor-editor.css';
		$css_url  = plugins_url( 'assets/css/kdna-elementor-editor.css', __FILE__ );

		wp_enqueue_style(
			'kdna-elementor-editor',
			$css_url,
			array(),
			file_exists( $css_file ) ? filemtime( $css_file ) : ( class_exists( 'KDNAForms' ) ? KDNAForms::$version : '1.0.0' )
		);
	}

	/**
	 * Enqueue front-end styles for the Elementor widget.
	 *
	 * @return void
	 */
	public function enqueue_frontend_styles() {
		$css_file = __DIR__ . '/assets/css/kdna-elementor-editor.css';
		$css_url  = plugins_url( 'assets/css/kdna-elementor-editor.css', __FILE__ );

		wp_enqueue_style(
			'kdna-elementor-frontend',
			$css_url,
			array(),
			file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0'
		);
	}

	/**
	 * Register front-end scripts for popup compatibility.
	 *
	 * @return void
	 */
	public function register_frontend_scripts() {
		// Scripts are rendered inline for popup compatibility.
	}

	/**
	 * Render inline JavaScript for Elementor popup compatibility.
	 *
	 * Listens for the elementor/popup/show event and reinitializes
	 * KDNA Forms conditional logic, input masks, and other JS-dependent features.
	 *
	 * @return void
	 */
	public function render_popup_compatibility_script() {
		if ( ! $this->is_elementor_active() ) {
			return;
		}

		?>
		<script type="text/javascript">
		(function() {
			'use strict';

			if ( typeof jQuery === 'undefined' ) {
				return;
			}

			jQuery( document ).ready( function( $ ) {

				// Listen for Elementor popup show events.
				$( document ).on( 'elementor/popup/show', function( event, id, instance ) {
					var $popup = instance ? instance.getElements( '$element' ) : null;
					if ( ! $popup || ! $popup.length ) {
						return;
					}

					var $forms = $popup.find( '.gform_wrapper' );
					if ( ! $forms.length ) {
						return;
					}

					$forms.each( function() {
						var $wrapper = $( this );
						var formId   = $wrapper.attr( 'id' );

						if ( ! formId ) {
							return;
						}

						// Extract numeric form ID from wrapper ID (gform_wrapper_123).
						var numericId = formId.replace( 'gform_wrapper_', '' );

						// Reinitialize conditional logic if available.
						if ( typeof window[ 'kdnaform_conditional_logic_' + numericId ] === 'function' ) {
							window[ 'kdnaform_conditional_logic_' + numericId ]();
						}

						// Trigger a custom event for add-ons to hook into.
						$( document ).trigger( 'kdnaform_post_render', [ numericId, 0 ] );

						// Re-apply input masks if the library is loaded.
						if ( $.fn.mask ) {
							$wrapper.find( 'input[data-mask]' ).each( function() {
								var mask = $( this ).data( 'mask' );
								if ( mask ) {
									$( this ).mask( mask );
								}
							});
						}

						// Reinitialize date pickers if available.
						if ( $.fn.datepicker ) {
							$wrapper.find( '.datepicker' ).each( function() {
								if ( ! $( this ).hasClass( 'hasDatepicker' ) ) {
									$( this ).datepicker();
								}
							});
						}

						// Trigger window resize to recalculate any responsive layouts.
						$( window ).trigger( 'resize' );
					});
				});
			});
		})();
		</script>
		<?php
	}
}

// Initialize the loader.
KDNA_Elementor_Loader::get_instance();
