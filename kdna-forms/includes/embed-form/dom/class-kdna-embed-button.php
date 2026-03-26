<?php

namespace KDNA_Forms\KDNA_Forms\Embed_Form\Dom;

/**
 * Handle outputting the Embed Button in the UI.
 *
 * @since 2.6
 *
 * @package KDNA_Forms\KDNA_Forms\Embed_Form\Dom
 */
class KDNA_Embed_Button {

	/**
	 * Output the HTML for the Embed Button.
	 */
	public function output_button() {
		?>
		<button data-js="embed-flyout-trigger" class="kdnaform-button kdnaform-button--white kdnaform-button--icon-leading">
			<i class="kdnaform-button__icon kdnaform-icon kdnaform-icon--embed-alt" aria-hidden="true"></i>
			<?php esc_html_e( 'Embed', 'kdnaforms' ); ?>
		</button>
		<?php
	}

}
