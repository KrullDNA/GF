<?php

namespace KDNA_Forms\KDNA_Forms\Config\Items;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Configurator;

/**
 * Config items for Multifile Strings
 *
 * @since 2.6
 */
class KDNA_Config_Multifile extends KDNA_Config {

	protected $script_to_localize = 'kdnaform_kdnaforms';
	protected $name               = 'kdnaform_kdnaforms';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'strings' => array(
				'invalid_file_extension' => wp_strip_all_tags( __( 'This type of file is not allowed. Must be one of the following: ', 'kdnaforms' ) ),
				'delete_file'            => wp_strip_all_tags( __( 'Delete this file', 'kdnaforms' ) ),
				'in_progress'            => wp_strip_all_tags( __( 'in progress', 'kdnaforms' ) ),
				'file_exceeds_limit'     => wp_strip_all_tags( __( 'File exceeds size limit', 'kdnaforms' ) ),
				'illegal_extension'      => wp_strip_all_tags( __( 'This type of file is not allowed.', 'kdnaforms' ) ),
				'max_reached'            => wp_strip_all_tags( __( 'Maximum number of files reached', 'kdnaforms' ) ),
				'unknown_error'          => wp_strip_all_tags( __( 'There was a problem while saving the file on the server', 'kdnaforms' ) ),
				'currently_uploading'    => wp_strip_all_tags( __( 'Please wait for the uploading to complete', 'kdnaforms' ) ),
				'cancel'                 => wp_strip_all_tags( __( 'Cancel', 'kdnaforms' ) ),
				'cancel_upload'          => wp_strip_all_tags( __( 'Cancel this upload', 'kdnaforms' ) ),
				'cancelled'              => wp_strip_all_tags( __( 'Cancelled', 'kdnaforms' ) ),
				'error'                  => wp_strip_all_tags( __( 'Error', 'kdnaforms' ) ),
				'message'                => wp_strip_all_tags( __( 'Message', 'kdnaforms' ) ),
			),
			'vars'    => array(
				'images_url' => \KDNACommon::get_base_url() . '/images'
			)
		);
	}

}