<?php
/**
 * Campaign Monitor API wrapper class.
 *
 * @package KDNA_Forms_Campaign_Monitor
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class KDNA_CM_API
 *
 * Handles all communication with the Campaign Monitor API v3.3.
 */
class KDNA_CM_API {

    /**
     * Campaign Monitor API key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Base URL for the Campaign Monitor API.
     *
     * @var string
     */
    private $api_base = 'https://api.createsend.com/api/v3.3/';

    /**
     * Constructor.
     *
     * @param string $api_key Campaign Monitor API key.
     */
    public function __construct( $api_key ) {
        $this->api_key = $api_key;
    }

    /**
     * Get all clients.
     *
     * @return array|WP_Error List of clients or WP_Error on failure.
     */
    public function get_clients() {
        return $this->request( 'clients.json' );
    }

    /**
     * Get lists for a client.
     *
     * @param string $client_id Campaign Monitor Client ID.
     * @return array|WP_Error List of lists or WP_Error on failure.
     */
    public function get_lists( $client_id ) {
        return $this->request( "clients/{$client_id}/lists.json" );
    }

    /**
     * Get list details including custom fields.
     *
     * @param string $list_id Campaign Monitor List ID.
     * @return array|WP_Error List details or WP_Error on failure.
     */
    public function get_list_details( $list_id ) {
        return $this->request( "lists/{$list_id}.json" );
    }

    /**
     * Get custom fields for a list.
     *
     * @param string $list_id Campaign Monitor List ID.
     * @return array|WP_Error Custom fields or WP_Error on failure.
     */
    public function get_custom_fields( $list_id ) {
        return $this->request( "lists/{$list_id}/customfields.json" );
    }

    /**
     * Add or update a subscriber.
     *
     * @param string $list_id Campaign Monitor List ID.
     * @param array  $data    Subscriber data.
     * @return array|WP_Error Response or WP_Error on failure.
     */
    public function add_subscriber( $list_id, $data ) {
        return $this->request( "subscribers/{$list_id}.json", 'POST', $data );
    }

    /**
     * Validate the API key by making a test request.
     *
     * @return bool True if the API key is valid, false otherwise.
     */
    public function validate() {
        $result = $this->request( 'systemdate.json' );
        return ! is_wp_error( $result );
    }

    /**
     * Make an API request to Campaign Monitor.
     *
     * @param string     $endpoint API endpoint (relative to base URL).
     * @param string     $method   HTTP method (GET, POST, PUT, DELETE).
     * @param array|null $body     Request body for POST/PUT requests.
     * @return array|WP_Error Decoded response body or WP_Error on failure.
     */
    private function request( $endpoint, $method = 'GET', $body = null ) {
        $url = $this->api_base . $endpoint;

        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $this->api_key . ':x' ),
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
            $message = isset( $decoded_body['Message'] ) ? $decoded_body['Message'] : 'Unknown API error';
            return new WP_Error( 'cm_api_error', $message, array( 'status' => $code ) );
        }

        return $decoded_body;
    }
}
