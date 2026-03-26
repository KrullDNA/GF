<?php

namespace KDNA_Forms\KDNA_Forms\Form_Display\Block_Styles\Views;

use KDNA_Forms\KDNA_Forms\Theme_Layers\API\View;
use \KDNAFormDisplay;

class Form_View extends View {

	protected $string_search = ' kdnaform_wrapper';

	public function should_override( $form, $form_id, $block_settings = array() ) {
		return true;
	}

	public function get_markup( $content, $form, $value, $lead_id, $form_id ) {
		$content = $this->add_wrapper_class( $content, $form );
		return $content;
	}

	protected function add_wrapper_class( $content, $form ) {
		require_once( \KDNACommon::get_base_path() . '/form_display.php' );

		$theme_slug = KDNAFormDisplay::get_form_theme_slug( $form );
		$classes    = '';

		switch ( $theme_slug ) {
			case 'orbital':
				$classes = ' kdnaform_wrapper kdnaform-theme kdnaform-theme--foundation kdnaform-theme--framework kdnaform-theme--' . $theme_slug;
				break;
			case 'gravity-theme':
			default:
				$classes = ' kdnaform_wrapper gravity-theme kdnaform-theme--no-framework';
				break;
			case 'legacy':
				$classes = ' kdnaform_wrapper kdnaform_legacy_markup_wrapper kdnaform-theme--no-framework';
				break;
		}

		return str_replace( $this->string_search, $classes, $content );
	}

}
