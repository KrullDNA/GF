<?php
/**
 * Minimal stub for the removed KDNA_Background_Process class.
 *
 * Provides no-op implementations of the background processing methods
 * so that KDNA_Feed_Processor and other code that extends this class
 * can still be loaded and used without fatal errors.
 *
 * All tasks are effectively no-ops; feed processing falls back
 * to synchronous execution in KDNAFeedAddOn::maybe_process_feed().
 *
 * @package KDNAForms
 */

namespace KDNA_Forms\KDNA_Forms\Async;

if ( ! class_exists( 'KDNA_Forms\KDNA_Forms\Async\KDNA_Background_Process' ) ) {

	/**
	 * No-op background process stub.
	 */
	class KDNA_Background_Process {

		/**
		 * Action name.
		 *
		 * @var string
		 */
		protected $action = 'kdna_background_process';

		/**
		 * Whether the task supports the attempts key.
		 *
		 * @var bool
		 */
		protected $supports_attempts = false;

		/**
		 * Queued data.
		 *
		 * @var array
		 */
		protected $data = array();

		/**
		 * Constructor.
		 *
		 * @param bool|array $allowed_batch_data_classes Optional.
		 */
		public function __construct( $allowed_batch_data_classes = true ) {
			// No-op.
		}

		/**
		 * Push item to queue.
		 *
		 * @param mixed $data Data.
		 *
		 * @return $this
		 */
		public function push_to_queue( $data ) {
			$this->data[] = $data;
			return $this;
		}

		/**
		 * Save the queue (no-op).
		 *
		 * @return $this
		 */
		public function save() {
			return $this;
		}

		/**
		 * Dispatch on shutdown (no-op).
		 *
		 * @return $this
		 */
		public function dispatch_on_shutdown() {
			return $this;
		}

		/**
		 * Dispatch (no-op).
		 *
		 * @return mixed
		 */
		public function dispatch() {
			return null;
		}

		/**
		 * Get queued data.
		 *
		 * @return array
		 */
		public function get_data() {
			return $this->data;
		}

		/**
		 * Clear the queue.
		 *
		 * @param bool $force Optional.
		 *
		 * @return $this
		 */
		public function clear_queue( $force = false ) {
			$this->data = array();
			return $this;
		}

		/**
		 * Handle cron healthcheck (no-op).
		 */
		public function handle_cron_healthcheck() {
			// No-op.
		}

		/**
		 * Check if processing is enabled (no-op, always returns false).
		 *
		 * @return bool
		 */
		public function is_enabled() {
			return false;
		}

		/**
		 * Uninstall (no-op).
		 */
		public function uninstall() {
			// No-op.
		}

		/**
		 * Handle error (no-op).
		 *
		 * @param mixed $error The error.
		 */
		protected function handle_error( $error ) {
			// No-op.
		}

		/**
		 * Log debug message (no-op).
		 *
		 * @param string $message The message.
		 */
		protected function log_debug( $message ) {
			// No-op.
		}

		/**
		 * Log error message (no-op).
		 *
		 * @param string $message The message.
		 */
		protected function log_error( $message ) {
			// No-op.
		}

		/**
		 * Get the action for log (no-op).
		 *
		 * @return string
		 */
		protected function get_action_for_log() {
			return $this->action;
		}

		/**
		 * Filter form (no-op stub, returns form as-is).
		 *
		 * @param array $form  The form.
		 * @param array $entry The entry.
		 *
		 * @return array
		 */
		protected function filter_form( $form, $entry ) {
			return $form;
		}
	}
}
