<?php

namespace KDNA_Forms\KDNA_Forms\Editor_Button\Dom;

/**
 * Handle outputting the Embed Button in the UI.
 *
 * @since 2.6
 *
 * @package KDNA_Forms\KDNA_Forms\Embed_Form\Dom
 */
class KDNA_Editor_Button {

	/**
	 * Output the HTML for the Embed Button.
	 */
	public function output_button() {
		?>
		<button
            data-js="editor-flyout-trigger"
            class="kdnaform-button kdnaform-button--icon-white kdnaform-button--icon-editor"
            aria-label="<?php esc_attr_e( 'Open editor preferences', 'kdnaforms' ); ?>"
            title="<?php esc_attr_e( 'Open editor preferences', 'kdnaforms' ); ?>"
        >
			<i class="kdnaform-icon kdnaform-icon--cog kdnaform-button__icon" aria-hidden="true"></i>
		</button>
		<?php
	}

}
