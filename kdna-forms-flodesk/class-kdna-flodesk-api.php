<?php
/**
 * Flodesk API wrapper class.
 *
 * @package KDNA_Forms_Flodesk
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_Flodesk_API {

    private $api_key;

    private $api_base = 'https://api.flodesk.com/v1/';

    public function __construct( $api_key ) {
        $this->api_key = $api_key;
    }

    public function validate() {
        $result = $this->request( 'segments' );
        return ! is_wp_error( $result );
    }

    public function get_segments() {
        return $this->request( 'segments' );
    }

    public function add_subscriber( $data ) {
        return $this->request( 'subscribers', 'POST', $data );
    }

    public function add_subscriber_to_segment( $subscriber_id, $segment_id ) {
        return $this->request( "subscribers/{$subscriber_id}/segments", 'POST', array( 'segment_ids' => array( $segment_id ) ) );
    }

    private function request( $endpoint, $method = 'GET', $body = null ) {
        $url = $this->api_base . $endpoint;

        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
        );

        if ( $body && 'GET' !== $method ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code         = wp_remote_retrieve_response_code( $response );
        $decoded_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            $message = isset( $decoded_body['message'] ) ? $decoded_body['message'] : 'Unknown API error';
            return new WP_Error( 'flodesk_api_error', $message, array( 'status' => $code ) );
        }

        return $decoded_body;
    }
}
