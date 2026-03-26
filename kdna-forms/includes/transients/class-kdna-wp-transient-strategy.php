<?php

namespace KDNA_Forms\KDNA_Forms\Transients;

class GF_WP_Transient_Strategy implements GF_Transient_Strategy {

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		return get_transient( $key );
	}

	/**
	 * @param $key
	 * @param $value
	 * @param $timeout
	 *
	 * @return bool
	 */
	public function set( $key, $value, $timeout ) {
		return set_transient( $key, $value, $timeout );
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function delete( $key ) {
		return delete_transient( $key );
	}

}