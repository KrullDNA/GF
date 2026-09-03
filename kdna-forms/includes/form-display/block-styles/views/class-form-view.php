<?php

namespace KDNA_Forms\KDNA_Forms\Form_Display\Block_Styles\Views;

use KDNA_Forms\KDNA_Forms\Theme_Layers\API\View;
use \KDNAFormDisplay;

class Form_View extends View {

	protected $string_search = ' kform_wrapper';

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
				$classes = ' kform_wrapper kform-theme kform-theme--foundation kform-theme--framework kform-theme--' . $theme_slug;
				break;
			case 'kdna-theme':
			default:
				$classes = ' kform_wrapper kdna-theme kform-theme--no-framework';
				break;
			case 'legacy':
				$classes = ' kform_wrapper kdnaform_legacy_markup_wrapper kform-theme--no-framework';
				break;
		}

		return str_replace( $this->string_search, $classes, $content );
	}

}
