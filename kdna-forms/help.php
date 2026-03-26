<?php

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

/**
 * Class KDNAHelp
 * Displays the KDNA Forms Help page
 */
class KDNAHelp {

	/**
	 * Displays the KDNA Forms Help page
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function help_page() {
		if ( ! KDNACommon::ensure_wp_version() ) {
			return;
		}

		$dev_min = defined( 'KDNA_SCRIPT_DEBUG' ) && KDNA_SCRIPT_DEBUG ? '' : '.min';

		?>
        <link rel="stylesheet" href="<?php echo esc_url( KDNACommon::get_base_url() ); ?>/assets/css/dist/admin<?php echo esc_html( $dev_min ); ?>.css" />
		<div class="wrap gforms_help <?php echo esc_attr( KDNACommon::get_browser_class() ); ?>">

            <?php
            KDNACommon::admin_screen_reader_title();
            ?>
            <h2><?php esc_html_e( 'How can we help you?', 'kdnaforms' ); ?></h2>

            <div class="gf_help_content">
				<p>
					<?php printf(
						esc_html__( "Please review the %sdocumentation%s first. If you still can't find the answer %sopen a support ticket%s and we will be happy to answer your questions and assist you with any problems.", 'kdnaforms' ),
						'<a href="https://docs.kdnaforms.com/" target="_blank">',
						'<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'kdnaforms' ) . '</span>&nbsp;<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span></a>',
						'<a href="' . esc_attr( KDNACommon::get_support_url() ) . '" target="_blank">',
						'<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'kdnaforms' ) . '</span>&nbsp;<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span></a>'
					); ?>
				</p>
            </div>

            <form id="gf_help_page_search" action="https://docs.kdnaforms.com" target="_blank">
                <div class="search_box">
                    <label for="gf_help_search" class="screen-reader-text"><?php esc_html_e( 'Search Our Documentation', 'kdnaforms' ) ?></label>
                    <input type="text" id="gf_help_search" name="s" placeholder="<?php esc_attr_e( 'Search Our Documentation', 'kdnaforms' ) ?>"/>
                    <button class="kdnaform-button kdnaform-button--size-r kdnaform-button--white kdnaform-button--width-auto kdnaform-button--active-type-loader kdnaform-button--loader-after kdnaform-button--icon-trailing button_focus">
                        <span class="kdnaform-button__text kdnaform-button__text--inactive"><?php esc_html_e( 'Search', 'kdnaforms' ) ?></span>
                        <span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'kdnaforms' ) ?></span>
                        <span class="kdnaform-icon kdnaform-icon--external-link kdnaform-button__icon" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

            <div id="gforms_helpboxes">
                <div class="gforms_helpbox user_documentation">
                    <div class="helpbox_header"></div>
                    <div class="resource_list">
                        <h3><?php esc_html_e( 'User Documentation', 'kdnaforms' ); ?></h3>
                        <ul>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/create-a-new-form/">
									<?php esc_html_e( 'Creating a Form', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/category/user-guides/getting-started/add-form-to-site/">
									<?php esc_html_e( 'Embedding a Form', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/reviewing-form-submissions/">
									<?php esc_html_e( 'Reviewing Form Submissions', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/configuring-confirmations/">
									<?php esc_html_e( 'Configuring Confirmations', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/configuring-notifications-in-kdna-forms/">
									<?php esc_html_e( 'Configuring Notifications', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="gforms_helpbox developer_documentation">
                    <div class="helpbox_header"></div>
                    <div class="resource_list">
                        <h3><?php esc_html_e( 'Developer Documentation', 'kdnaforms' ); ?></h3>
                        <ul class="resource_list">
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/getting-started-kdna-forms-api-gfapi/">
									<?php esc_html_e( 'Discover the KDNA Forms API', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/api-functions/">
									<?php esc_html_e( 'API Functions', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/category/developers/rest-api/">
									<?php esc_html_e( 'REST API', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/category/developers/php-api/add-on-framework/">
									<?php esc_html_e( 'Add-On Framework', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/gfaddon/">
									<?php esc_html_e( 'KDNAAddOn', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="gforms_helpbox designer_documentation">
                    <div class="helpbox_header"></div>
                    <div class="resource_list">
                        <h3><?php esc_html_e( 'Designer Documentation', 'kdnaforms' ); ?></h3>
                        <ul class="resource_list">
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/category/user-guides/design-and-layout/css-selectors/">
									<?php esc_html_e( 'CSS Selectors', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/css-targeting-examples/">
									<?php esc_html_e( 'CSS Targeting Examples', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/css-ready-classes/">
									<?php esc_html_e( 'CSS Ready Classes', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/kdnaform_field_css_class/">
									<?php esc_html_e( 'kdnaform_field_css_class', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://docs.kdnaforms.com/kdnaform_noconflict_styles/">
									<?php esc_html_e( 'kdnaform_noconflict_styles', 'kdnaforms' ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
									<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
		</div>
		<img id="hexagons-bg-orange" src="<?php echo esc_url( KDNACommon::get_base_url() ); ?>/images/hexagons-bg-orange.svg" alt=""/>
		<img id="hexagons-bg-dark-blue" src="<?php echo esc_url( KDNACommon::get_base_url() ); ?>/images/hexagons-bg-dark-blue.svg" alt=""/>
		<img id="hexagons-bg-light-blue" src="<?php echo esc_url( KDNACommon::get_base_url() ); ?>/images/hexagons-bg-light-blue.svg" alt=""/>
	<?php
	}
}
