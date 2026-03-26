<?php

namespace KDNA_Forms\KDNA_Forms\Config\Items;

/**
 * Acts as a container for any Global Config data we need to send to both
 * the admin and theme side of the ecosystem.
 *
 * @since 2.7
 */
class KDNA_Config_Global {

	/**
	 * The data to send to both configs.
	 *
	 * @return array
	 */
	public function data() {
		return array(
			'hmr_dev'     => defined( 'KDNA_ENABLE_HMR' ) && KDNA_ENABLE_HMR,
			'public_path' => trailingslashit( \KDNACommon::get_base_url() ) . 'assets/js/dist/',
			'config_nonce' => wp_create_nonce( 'kdnaform_config_ajax' ),
		);
	}

}
