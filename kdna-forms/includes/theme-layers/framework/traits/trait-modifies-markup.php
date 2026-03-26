<?php

namespace KDNA_Forms\KDNA_Forms\Theme_Layers\Framework\Traits;

use KDNA_Forms\KDNA_Forms\Theme_Layers\KDNA_Theme_Layers_Provider;

trait Modifies_Markup {

	/**
	 * Return an array of views to override for fields/forms.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	abstract public function overriden_fields();

	/**
	 * Add the engine.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function add_engine_markup_output() {
		$engine = $this->output_engine_factory->get( KDNA_Theme_Layers_Provider::MARKUP_OUTPUT_ENGINE );
		$engine->set_views( $this->overriden_fields() );

		$this->output_engines[] = $engine;

		add_action( 'init', array( $engine, 'output' ), 11 );
	}

}
