<?php

namespace KDNA_Forms\KDNA_Forms\Config\Items;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Collection;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Configurator;

/**
 * Config items for Theme I18N
 *
 * @since 2.6
 */
class KDNA_Config_I18n extends KDNA_Config {

	protected $name               = 'kdnaform_i18n';
	protected $script_to_localize = 'kdnaform_kdnaforms';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'datepicker' => array(
				'days'     => array(
					'monday'    => esc_html__( 'Mo', 'kdnaforms' ),
					'tuesday'   => esc_html__( 'Tu', 'kdnaforms' ),
					'wednesday' => esc_html__( 'We', 'kdnaforms' ),
					'thursday'  => esc_html__( 'Th', 'kdnaforms' ),
					'friday'    => esc_html__( 'Fr', 'kdnaforms' ),
					'saturday'  => esc_html__( 'Sa', 'kdnaforms' ),
					'sunday'    => esc_html__( 'Su', 'kdnaforms' ),
				),
				'months'   => array(
					'january'   => esc_html__( 'January', 'kdnaforms' ),
					'february'  => esc_html__( 'February', 'kdnaforms' ),
					'march'     => esc_html__( 'March', 'kdnaforms' ),
					'april'     => esc_html__( 'April', 'kdnaforms' ),
					'may'       => esc_html_x('May', 'Full month name', 'kdnaforms'),
					'june'      => esc_html__( 'June', 'kdnaforms' ),
					'july'      => esc_html__( 'July', 'kdnaforms' ),
					'august'    => esc_html__( 'August', 'kdnaforms' ),
					'september' => esc_html__( 'September', 'kdnaforms' ),
					'october'   => esc_html__( 'October', 'kdnaforms' ),
					'november'  => esc_html__( 'November', 'kdnaforms' ),
					'december'  => esc_html__( 'December', 'kdnaforms' ),
				),
				'firstDay' => array(
					'value'   => absint( get_option( 'start_of_week' ) ),
					'default' => 1,
				),
				'iconText' => esc_html__( 'Select date', 'kdnaforms' ),

			),
		);
	}
}
