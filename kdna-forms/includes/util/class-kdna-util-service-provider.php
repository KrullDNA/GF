<?php

namespace KDNA_Forms\KDNA_Forms\Util;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Transients\KDNA_WP_Transient_Strategy;
use KDNA_Forms\KDNA_Forms\Util\Colors\Color_Modifier;

class KDNA_Util_Service_Provider extends KDNA_Service_Provider {

	const KDNA_CACHE        = 'kdna_cache';
	const TRANSIENT_STRAT = 'kdna_license_transient_strat';
	const KDNA_COMMON       = 'kdna_common';
	const KDNA_FORMS_MODEL  = 'kdna_forms_model';
	const RG_FORMS_MODEL  = 'rg_forms_model';
	const KDNA_API          = 'kdna_api';
	const KDNA_FORMS        = 'kdna_forms';
	const KDNA_FORM_DETAIL  = 'kdna_form_detail';
	const KDNA_COLORS       = 'kdna_colors';


	public function register( KDNA_Service_Container $container ) {
		require_once( \KDNACommon::get_base_path() . '/includes/util/colors/class-color-modifier.php' );

		$container->add(
			self::KDNA_CACHE,
			function () {
				return new \KDNACache;
			}
		);

		$container->add(
			self::TRANSIENT_STRAT,
			function () {
				return new KDNA_WP_Transient_Strategy();
			}
		);

		$container->add(
			self::KDNA_COMMON,
			function () {
				return new \KDNACommon;
			}
		);

		$container->add(
			self::KDNA_FORMS_MODEL,
			function () {
				return new \KDNAFormsModel;
			}
		);

		$container->add(
			self::RG_FORMS_MODEL,
			function () {
				return new \KDNAFormsModel;
			}
		);

		$container->add(
			self::KDNA_API,
			function () {
				return new \KDNAAPI;
			}
		);

		$container->add(
			self::KDNA_FORMS,
			function () {
				return new \KDNAForms;
			}
		);

		$container->add(
			self::KDNA_FORM_DETAIL,
			function () {
				return new \KDNAFormDetail;
			}
		);

		$container->add( self::KDNA_COLORS, function () {
			return new Color_Modifier();
		} );
	}
}
