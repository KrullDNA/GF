<?php

namespace KDNA_Forms\KDNA_Forms\Util;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Transients\GF_WP_Transient_Strategy;
use KDNA_Forms\KDNA_Forms\Util\Colors\Color_Modifier;

class KDNA_Util_Service_Provider extends KDNA_Service_Provider {

	const GF_CACHE        = 'gf_cache';
	const TRANSIENT_STRAT = 'gf_license_transient_strat';
	const GF_COMMON       = 'gf_common';
	const GF_FORMS_MODEL  = 'kdna_forms_model';
	const RG_FORMS_MODEL  = 'rg_forms_model';
	const GF_API          = 'gf_api';
	const GF_FORMS        = 'kdna_forms';
	const GF_FORM_DETAIL  = 'kdna_form_detail';
	const GF_COLORS       = 'gf_colors';


	public function register( KDNA_Service_Container $container ) {
		require_once( \KDNACommon::get_base_path() . '/includes/util/colors/class-color-modifier.php' );

		$container->add(
			self::GF_CACHE,
			function () {
				return new \KDNACache;
			}
		);

		$container->add(
			self::TRANSIENT_STRAT,
			function () {
				return new GF_WP_Transient_Strategy();
			}
		);

		$container->add(
			self::GF_COMMON,
			function () {
				return new \KDNACommon;
			}
		);

		$container->add(
			self::GF_FORMS_MODEL,
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
			self::GF_API,
			function () {
				return new \KDNAAPI;
			}
		);

		$container->add(
			self::GF_FORMS,
			function () {
				return new \KDNAForms;
			}
		);

		$container->add(
			self::GF_FORM_DETAIL,
			function () {
				return new \KDNAFormDetail;
			}
		);

		$container->add( self::GF_COLORS, function () {
			return new Color_Modifier();
		} );
	}
}
