<?php

namespace KDNA_Forms\KDNA_Forms\Orders\Exporters;

use \KDNA_Forms\KDNA_Forms\Orders\KDNA_Order;
use \KDNA_Forms\KDNA_Forms\Orders\Exporters\KDNA_Order_Exporter;
use \KDNACommon;

class GF_Entry_Details_Order_Exporter extends KDNA_Order_Exporter {

	/**
	 * GF_Entry_Details_Order_Formatter constructor.
	 *
	 * @param KDNA_Order $order  The order to be formatted.
	 * @param array    $config Any specific configurations required while formatting the order.
	 */
	public function __construct( $order, $config = array() ) {
		parent::__construct( $order, $config );
		$this->data['rows'] = array();
	}

	/**
	 * Extracts a set of raw data from the order.
	 *
	 * @since 2.6
	 */
	protected function format() {

		foreach ( $this->order->get_items() as $item ) {
			$this->data['rows'][ $item->belongs_to ][] = $this->filter_item_data(
				$item,
				array(),
				array(
					'price_money'     => KDNACommon::to_money( $item->get_base_price(), $this->order->currency ),
					'sub_total_money' => KDNACommon::to_money( $item->sub_total, $this->order->currency ),
				)
			);
		}

		foreach ( $this->data['totals'] as $label => $total ) {
			$this->data['totals'][ $label . '_money' ] = KDNACommon::to_money( $total, $this->order->currency );
		}
	}
}

