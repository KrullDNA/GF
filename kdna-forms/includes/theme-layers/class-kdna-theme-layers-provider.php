<?php

namespace KDNA_Forms\KDNA_Forms\Theme_Layers;

use \KDNAAddOn;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Theme_Layers\API\Fluent\Theme_Layer_Builder;
use KDNA_Forms\KDNA_Forms\Theme_Layers\Framework\Factories\Definition_Engine_Factory;
use KDNA_Forms\KDNA_Forms\Theme_Layers\Framework\Factories\Output_Engine_Factory;

use KDNA_Forms\KDNA_Forms\Theme_Layers\Assets\Scripts;
use KDNA_Forms\KDNA_Forms\Theme_Layers\Assets\Styles;
use KDNA_Forms\KDNA_Forms\Theme_Layers\Framework\GF_Theme_Layer;

/**
 * Class KDNA_Theme_Layers_Provider
 *
 * Service provider for the Style Layers.
 *
 * @since 2.7
 *
 * @package KDNA_Forms\KDNA_Forms\Theme_Layers;
 */
class KDNA_Theme_Layers_Provider extends KDNA_Service_Provider {

	// Global services
	const THEME_LAYERS              = 'theme_layers';
	const DEFINITION_ENGINE_FACTORY = 'definition_engine_factory';
	const OUTPUT_ENGINE_FACTORY     = 'output_engine_factory';

	// Definition Engines
	const SETTINGS_DEFINITION_ENGINE       = 'settings_definition_engine';
	const BLOCK_SETTINGS_DEFINITION_ENGINE = 'block_settings_definition_engine';

	// Output Engines
	const MARKUP_OUTPUT_ENGINE              = 'markup_output_engine';
	const FORM_CSS_PROPERTIES_OUTPUT_ENGINE = 'form_css_properties_output_engine';
	const ASSET_ENQUEUE_OUTPUT_ENGINE       = 'asset_enqueue_output_engine';

	protected $plugin_path;
	protected $namespace;

	public function __construct( $plugin_path, $namespace ) {
		$this->plugin_path = $plugin_path;
		$this->namespace   = $namespace;
	}

	/**
	 * Register services to the container.
	 *
	 * @since 2.7
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		$pp = $this->plugin_path;
		$ns = $this->namespace;

		$this->require_deps();

		$container->add( self::DEFINITION_ENGINE_FACTORY, function () {
			return new Definition_Engine_Factory();
		} );

		$container->add( self::OUTPUT_ENGINE_FACTORY, function () use ( $ns ) {
			return new Output_Engine_Factory( $ns );
		} );

		$container->add( self::THEME_LAYERS, function () use ( $container ) {
			return function () {
				$layers = array();

				return apply_filters( 'kdnaform_registered_theme_layers', $layers );
			};
		} );
	}

	/**
	 * Require the dependencies.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	protected function require_deps() {
		// Framework
		require_once( dirname( __FILE__ ) . '/framework/class-kdna-theme-layer.php' );
		require_once( dirname( __FILE__ ) . '/framework/traits/trait-has-settings-fields.php' );
		require_once( dirname( __FILE__ ) . '/framework/traits/trait-has-block-settings.php' );
		require_once( dirname( __FILE__ ) . '/framework/traits/trait-modifies-markup.php' );
		require_once( dirname( __FILE__ ) . '/framework/traits/trait-outputs-form-css-properties.php' );
		require_once( dirname( __FILE__ ) . '/framework/traits/trait-enqueues-assets.php' );
		require_once( dirname( __FILE__ ) . '/framework/factories/class-definition-engine-factory.php' );
		require_once( dirname( __FILE__ ) . '/framework/factories/class-output-engine-factory.php' );
		require_once( dirname( __FILE__ ) . '/framework/engines/definition-engines/class-definition-engine.php' );
		require_once( dirname( __FILE__ ) . '/framework/engines/definition-engines/class-settings-definition-engine.php' );
		require_once( dirname( __FILE__ ) . '/framework/engines/definition-engines/class-block-settings-definition-engine.php' );
		require_once( dirname( __FILE__ ) . '/framework/engines/output-engines/class-output-engine.php' );
		require_once( dirname( __FILE__ ) . '/framework/engines/output-engines/class-php-markup-output-engine.php' );
		require_once( dirname( __FILE__ ) . '/framework/engines/output-engines/class-form-css-properties-output-engine.php' );
		require_once( dirname( __FILE__ ) . '/framework/engines/output-engines/class-asset-enqueue-output-engine.php' );

		// API
		require_once( dirname( __FILE__ ) . '/api/json/functions.php' );
		require_once( dirname( __FILE__ ) . '/api/views/class-view.php' );
		require_once( dirname( __FILE__ ) . '/api/class-kdna-all-access-theme-layer.php' );
		require_once( dirname( __FILE__ ) . '/api/fluent/layers/class-fluent-theme-layer.php' );
		require_once( dirname( __FILE__ ) . '/api/json/rules/class-kdna-theme-layer-rule.php' );
		require_once( dirname( __FILE__ ) . '/api/json/layers/class-json-theme-layer.php' );

		require_once( dirname( __FILE__ ) . '/api/fluent/class-theme-layer-builder.php' );

		// Addon
		require_once( dirname( __FILE__ ) . '/class-kdna-theme-layers.php' );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since 2.7
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function init( KDNA_Service_Container $container ) {
		add_action( 'kdnaform_loaded', function () {
			KDNAAddOn::register( KDNA_Theme_Layers::class );
		} );

		$this->output_settings( $container );
	}

	/**
	 * Add a filter to output our settings when they exist.
	 *
	 * @since 2.7
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function output_settings( KDNA_Service_Container $container ) {
		add_filter( 'kdnaform_addon_form_settings_fields', function ( $sections, $form ) use ( $container ) {
			/**
			 * @var GF_Theme_Layer[]
			 */
			$style_layers = $container->get( self::THEME_LAYERS );
			$layer_name   = rgget( 'theme_layer' );

			foreach ( $style_layers as $layer ) {
				/**
				 * @var GF_Theme_Layer $layer
				 */
				if ( $layer->name() !== $layer_name ) {
					continue;
				}

				if ( empty( $layer->get_definitions()['settings'] ) ) {
					continue;
				}

				return $layer->get_definitions()['settings'];
			}

			return $sections;
		}, 0, 2 );
	}
}
