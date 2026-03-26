<?php

namespace KDNA_Forms\KDNA_Forms\Embed_Form\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNA_Forms\KDNA_Forms\Embed_Form\Endpoints\KDNA_Embed_Endpoint_Create_With_Block;
use KDNA_Forms\KDNA_Forms\Embed_Form\Endpoints\KDNA_Embed_Endpoint_Get_Posts;

/**
 * Config items for the Embed Forms REST Endpoints.
 *
 * @since 2.6
 */
class KDNA_Embed_Endpoints_Config extends KDNA_Config {

	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';
	protected $name               = 'kdnaform_admin_config';
	protected $overwrite          = false;

	/**
	 * Determine if the config should enqueue its data.
	 *
	 * @since 2.6.2
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return \KDNACommon::is_form_editor();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'components' => array(
				'embed_form' => array(
					'endpoints' => $this->get_endpoints(),
				),
			),
		);
	}

	/**
	 * Get the various endpoints for the Embed UI.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	private function get_endpoints() {
		return array(

			// Endpoint to get posts for typeahead
			'get_posts'              => array(
				'action' => array(
					'value'   => 'gf_embed_query_posts',
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( KDNA_Embed_Endpoint_Get_Posts::ACTION_NAME ),
					'default' => 'nonce',
				)
			),

			// Endpoint to create a new page with our block inserted.
			'create_post_with_block' => array(
				'action' => array(
					'value'   => KDNA_Embed_Endpoint_Create_With_Block::ACTION_NAME,
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( KDNA_Embed_Endpoint_Create_With_Block::ACTION_NAME ),
					'default' => 'nonce',
				)
			)
		);
	}

}
