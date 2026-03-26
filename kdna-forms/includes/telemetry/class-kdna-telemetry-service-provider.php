<?php

namespace KDNA_Forms\KDNA_Forms\Telemetry;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNACommon;
use KDNAFormsModel;


/**
 * Class KDNA_Telemetry_Service_Provider
 *
 * Service provider for the telemetry Service.
 *
 * @package KDNA_Forms\KDNA_Forms\Telemetry;
 */
class KDNA_Telemetry_Service_Provider extends KDNA_Service_Provider {
	const TELEMETRY_SCHEDULED_TASK = 'kdnaforms_telemetry_dispatcher';
	const BATCH_SIZE = 10;

	/**
	 * Register services to the container.
	 *
	 * @since
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		require_once KDNA_PLUGIN_DIR_PATH . 'includes/telemetry/class-kdna-telemetry-data.php';
		require_once KDNA_PLUGIN_DIR_PATH . 'includes/telemetry/class-kdna-telemetry-snapshot-data.php';
	}

	/**
	 * Initialize the scheduler.
	 *
	 * @since
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function init( KDNA_Service_Container $container ) {
		add_action( self::TELEMETRY_SCHEDULED_TASK, array( $this, 'enqueue_telemetry_batches' ) );
	}

	/**
	 * Enqueue batches of telemetry events to be processed in the background.
	 *
	 * @since
	 *
	 * @return void
	 */
	public function enqueue_telemetry_batches() {
		// Only run once a week.
		$last_run     = get_option( 'gf_last_telemetry_run', 0 );
		$current_time = time();
		if ( $current_time - $last_run < 60 * 60 * 24 * 7 ) {
			return;
		}
		update_option( 'gf_last_telemetry_run', $current_time );

		\KDNACommon::log_debug( __METHOD__ . sprintf( '(): Enqueuing telemetry batches' ) );
		KDNA_Telemetry_Data::take_snapshot();

		$processor = $this->container->get( \KDNA_Forms\KDNA_Forms\Async\KDNA_Background_Process_Service_Provider::TELEMETRY );

		$full_telemetry_data = KDNA_Telemetry_Data::get_data();

		$snapshot = $full_telemetry_data['snapshot'];

		// Enqueue the snapshot first, alone, to be sent to its own endpoint.
		$processor->push_to_queue( $snapshot );
		$processor->save()->dispatch();

		if ( ! empty( $full_telemetry_data['events'] ) && is_array( $full_telemetry_data['events'] ) ) {
			$batches = array_chunk( $full_telemetry_data['events'], self::BATCH_SIZE, true );
			foreach ( $batches as $batch ) {
				$processor->push_to_queue( $batch );
				$processor->save()->dispatch();
			}
		}

		// Clear saved telemetry data except the snapshot.
		update_option(
			'gf_telemetry_data',
			array(
				'snapshot' => $snapshot,
				'events'   => array(),
			),
			false
		);
	}
}

