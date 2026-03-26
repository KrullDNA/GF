<?php

namespace KDNA_Forms\KDNA_Forms\Theme_Layers\Framework\Traits;

use KDNA_Forms\KDNA_Forms\Theme_Layers\KDNA_Theme_Layers_Provider;

trait Has_Settings_Fields {

	/**
	 * Return an array of settings fields to add for this theme layer.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	abstract public function settings_fields();

	/**
	 * Add the engine.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function add_engine_settings_field() {
		$engine = $this->definition_engine_factory->get( KDNA_Theme_Layers_Provider::SETTINGS_DEFINITION_ENGINE );
		$engine->set_fields( $this->settings_fields() );

		$this->definition_engines[] = $engine;
	}

}
