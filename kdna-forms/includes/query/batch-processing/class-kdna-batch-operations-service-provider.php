<?php


namespace KDNA_Forms\KDNA_Forms\Query\Batch_Processing;


use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;

class KDNA_Batch_Operations_Service_Provider extends KDNA_Service_Provider {

	const ENTRY_META_BATCH_PROCESSOR = 'entry_meta_batch_processor';
	/**
	 * Register new services to the Service Container.
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function register( KDNA_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-entry-meta-batch-processor.php' );
		$container->add(
			self::ENTRY_META_BATCH_PROCESSOR,
			function() {
				return new GF_Entry_Meta_Batch_Processor();
			}
		);
	}
}
