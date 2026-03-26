<?php

use KDNA_Forms\KDNA_Forms\Theme_Layers\API\JSON\Layers\Json_Theme_Layer;
use KDNA_Forms\KDNA_Forms\Theme_Layers\KDNA_Theme_Layers_Provider;

function gforms_register_theme_json( $path ) {
	$container = KDNAForms::get_service_container();
	$layer     = new Json_Theme_Layer( $container->get( KDNA_Theme_Layers_Provider::DEFINITION_ENGINE_FACTORY ), $container->get( KDNA_Theme_Layers_Provider::OUTPUT_ENGINE_FACTORY ) );
	$layer->set_json( $path );
	$layer->init_engines();

	add_filter( 'kdnaform_registered_theme_layers', function ( $layers ) use ( $layer ) {
		$layers[] = $layer;

		return $layers;
	} );
}