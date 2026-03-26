<?php

namespace KDNA_Forms\KDNA_Forms\Theme_Layers\Framework\Factories;

use KDNA_Forms\KDNA_Forms\Theme_Layers\Framework\Engines\Definition_Engines\Block_Settings_Definition_Engine;
use KDNA_Forms\KDNA_Forms\Theme_Layers\Framework\Engines\Definition_Engines\Settings_Definition_Engine;
use KDNA_Forms\KDNA_Forms\Theme_Layers\KDNA_Theme_Layers_Provider;

/**
 * Factory to generate the Definition Engines used by a theme layer.
 *
 * @since 2.7
 */
class Definition_Engine_Factory {

	/**
	 * Map of engines this factory can provide.
	 *
	 * @since 2.7
	 *
	 * @return string[]
	 */
	public function engines() {
		return array(
			KDNA_Theme_Layers_Provider::SETTINGS_DEFINITION_ENGINE       => Settings_Definition_Engine::class,
			KDNA_Theme_Layers_Provider::BLOCK_SETTINGS_DEFINITION_ENGINE => Block_Settings_Definition_Engine::class,
		);
	}

	/**
	 * Return a specific engine by name.
	 *
	 * @since 2.7
	 *
	 * @param $name
	 *
	 * @return mixed|null
	 */
	public function get( $name ) {
		$engines = $this->engines();

		if ( ! isset( $engines[ $name ] ) ) {
			return null;
		}

		return new $engines[ $name ]();
	}

}