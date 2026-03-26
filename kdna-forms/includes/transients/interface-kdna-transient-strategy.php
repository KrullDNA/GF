<?php

namespace KDNA_Forms\KDNA_Forms\Transients;

interface GF_Transient_Strategy {

	public function get( $key );

	public function set( $key, $value, $timeout );

	public function delete( $key );

}