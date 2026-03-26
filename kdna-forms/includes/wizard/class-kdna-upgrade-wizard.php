<?php

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

class KDNA_Upgrade_Wizard {
	private $_step_class_names = array();

	function __construct(){

	}

	public function display(){
		//not implemented
		return false;
	}
/*
	public function display(){

		// register admin styles
		wp_print_styles( array( 'jquery-ui-styles', 'kdnaform_admin' ) );

		?>

		<div class="wrap about-wrap kdnaform_installation_progress_step_wrap">

			<h1><?php esc_html_e( 'KDNA Forms Upgrade', 'kdnaforms' ) ?></h1>

			<hr/>

			<h2><?php esc_html_e( 'Database Update Required', 'kdnaforms' ); ?></h2>

			<p><?php esc_html_e( 'KDNA Forms has been updated! Before we send you on your way, we have to update your database to the newest version.', 'kdnaforms' ); ?></p>
			<p><?php esc_html_e( 'The database update process may take a little while, so please be patient.', 'kdnaforms' ); ?></p>

			<input class="button button-primary" type="submit" value="<?php esc_attr_e( 'Upgrade', 'kdnaforms' ) ?>" name="_upgrade"/>

			<script type="text/javascript">

				function kdnaform_start_upgrade(){

					kdnaform_message( 'Progress: 0%' );

					//TODO: implement AJAX callbacks for manual upgrade

					jQuery.post(ajaxurl, {
						action			: "kdna_upgrade",
						kdna_upgrade		: '<?php echo wp_create_nonce( 'kdna_upgrade' ); ?>',
					})
					.done(function( data ) {
						kdnaform_success_message();
					})

					setTimeout( 'kdnaform_check_upgrade_status', 1000 );

				}

				function kdnaform_check_upgrade_status(){

					jQuery.post(ajaxurl, {
						action				: "gf_check_upgrade_status",
						kdna_upgrade_status	: '<?php echo wp_create_nonce( 'kdna_upgrade_status' ); ?>',
					})
					.done(function( data ) {
						if( data == '100' ){
							kdnaform_success_message();
						}
						else{
							kdnaform_message( 'Progress: ' + parseInt( data ) + '%' );
						}
					})

				}

				function kdnaform_message( message ){
					jQuery( '#kdnaform_upgrade_message' ).html( message );
				}

				function kdnaform_success_message(){
					kdnaform_message( 'Database upgrade complete' );
				}
			</script>
		</div>

	<?php

		return true;
	}
*/
}
