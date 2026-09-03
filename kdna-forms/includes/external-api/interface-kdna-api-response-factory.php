<?php

namespace KDNA_Forms\KDNA_Forms\External_API;

use KDNA_Forms\KDNA_Forms\External_API\GF_API_Response;

/**
 * Interface GF_API_Response_Factory
 *
 * Contract to define how Response Factories should behave.
 *
 * @since 2.5
 *
 * @package KDNA_Forms\KDNA_Forms\External_API
 */
interface GF_API_Response_Factory {

	/**
	 * @param mixed ...$args
	 *
	 * @return GF_API_Response
	 */
	public function create( ...$args );

}