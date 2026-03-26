<?php

class KDNA_Installation_Wizard_Step_Settings extends KDNA_Installation_Wizard_Step {

	protected $_name = 'settings';

	public $defaults = array(
		'currency' => '',
		'enable_noconflict' => false,
		'enable_toolbar_menu' => true,
		'enable_akismet' => true,
	);

	function display() {
		$disabled = apply_filters( 'kdnaform_currency_disabled', false ) ? "disabled='disabled'" : ''
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="gforms_currency"><?php esc_html_e( 'Currency', 'kdnaforms' ); ?></label>  <?php kdnaform_tooltip( 'settings_currency' ) ?>
				</th>
				<td>
					<?php
					$disabled = apply_filters( 'kdnaform_currency_disabled', false ) ? "disabled='disabled'" : ''
					?>

					<select id="gforms_currency" name="currency" <?php echo $disabled; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<option value=""><?php esc_html_e( 'Select a Currency', 'kdnaforms' ) ?></option>
						<?php
						$current_currency = $this->currency;

						foreach ( RGCurrency::get_currencies() as $code => $currency ) {
							?>
							<option value="<?php echo esc_attr( $code ) ?>" <?php echo $current_currency == $code ? "selected='selected'" : '' ?>><?php echo esc_html( $currency['name'] ) ?></option>
						<?php
						}
						?>
					</select>
					<?php do_action( 'kdnaform_currency_setting_message', '' ); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="kdnaform_enable_noconflict"><?php esc_html_e( 'No-Conflict Mode', 'kdnaforms' ); ?></label>  <?php kdnaform_tooltip( 'settings_noconflict' ) ?>
				</th>
				<td>
					<input type="radio" name="enable_noconflict" value="1" <?php echo $this->enable_noconflict == 1 ? "checked='checked'" : '' ?> id="kdnaform_enable_noconflict" /> <?php esc_html_e( 'On', 'kdnaforms' ); ?>&nbsp;&nbsp;
					<input type="radio" name="enable_noconflict" value="0" <?php echo  $this->enable_noconflict == 1 ? '' : "checked='checked'" ?> id="kdnaform_disable_noconflict" /> <?php esc_html_e( 'Off', 'kdnaforms' ); ?>
					<br />
					<span class="gf_settings_description"><?php esc_html_e( 'Set this to ON to prevent extraneous scripts and styles from being printed on KDNA Forms admin pages, reducing conflicts with other plugins and themes.', 'kdnaforms' ); ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="kdnaform_enable_toolbar_menu"><?php esc_html_e( 'Toolbar Menu', 'kdnaforms' ); ?></label>  <?php kdnaform_tooltip( 'settings_toolbar_menu' ) ?>
				</th>
				<td>
					<input type="radio" name="enable_toolbar_menu" value="1" <?php checked( $this->enable_toolbar_menu, true ); ?> id="kdnaform_enable_toolbar_menu" /> <?php esc_html_e( 'On', 'kdnaforms' ); ?>&nbsp;&nbsp;
					<input type="radio" name="enable_toolbar_menu" value="0" <?php checked( $this->enable_toolbar_menu, false );?> id="kdnaform_disable_toolbar_menu" /> <?php esc_html_e( 'Off', 'kdnaforms' ); ?>
					<br />
					<span class="gf_settings_description"><?php esc_html_e( 'Set this to ON to display the Forms menu in the WordPress top toolbar. The Forms menu will display the latest ten forms recently opened in the form editor.', 'kdnaforms' ); ?></span>
				</td>
			</tr>

			<?php if ( KDNACommon::has_akismet() ) { ?>
				<tr valign="top">
					<th scope="row">
						<label for="gforms_enable_akismet"><?php esc_html_e( 'Akismet Integration', 'kdnaforms' ); ?></label>  <?php kdnaform_tooltip( 'settings_akismet' ) ?>
					</th>
					<td>
						<input type="radio" name="enable_akismet" value="1" <?php checked( $this->enable_akismet, true ) ?> id="gforms_enable_akismet" /> <?php esc_html_e( 'Yes', 'kdnaforms' ); ?>&nbsp;&nbsp;
						<input type="radio" name="enable_akismet" value="0" <?php checked( $this->enable_akismet, false ) ?> /> <?php esc_html_e( 'No', 'kdnaforms' ); ?>
						<br />
						<span class="gf_settings_description"><?php esc_html_e( 'Protect your form entries from spam using Akismet.', 'kdnaforms' ); ?></span>
					</td>
				</tr>
			<?php } ?>
		</table>

	<?php
	}

	function get_title() {
		return esc_html__( 'Global Settings', 'kdnaforms' );
	}

	function install() {
		update_option( 'kdnaform_enable_noconflict', (bool) $this->enable_noconflict );
		update_option( 'kdna_forms_enable_akismet', (bool) $this->enable_akismet );
		update_option( 'kdna_forms_currency', $this->currency );
		update_option( 'kdnaform_enable_toolbar_menu', (bool) $this->enable_toolbar_menu );
	}
}
