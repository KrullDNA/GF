<?php
/**
 * Mailchimp API wrapper class.
 *
 * @package KDNA_Forms_Mailchimp
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_Mailchimp_API {

    private $api_key;

    private $data_center;

    private $api_base;

    public function __construct( $api_key ) {
        $this->api_key     = $api_key;
        $this->data_center = $this->extract_data_center( $api_key );
        $this->api_base    = 'https://' . $this->data_center . '.api.mailchimp.com/3.0/';
    }

    private function extract_data_center( $api_key ) {
        $parts = explode( '-', $api_key );
        return isset( $parts[1] ) ? $parts[1] : 'us1';
    }

    public function validate() {
        $result = $this->request( '' );
        return ! is_wp_error( $result );
    }

    public function get_lists() {
        $result = $this->request( 'lists?count=100' );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return isset( $result['lists'] ) ? $result['lists'] : array();
    }

    public function get_list( $list_id ) {
        return $this->request( 'lists/' . $list_id );
    }

    public function get_merge_fields( $list_id ) {
        $result = $this->request( 'lists/' . $list_id . '/merge-fields?count=100' );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return isset( $result['merge_fields'] ) ? $result['merge_fields'] : array();
    }

    public function add_or_update_subscriber( $list_id, $data ) {
        $email           = strtolower( $data['email_address'] );
        $subscriber_hash = md5( $email );
        $endpoint        = 'lists/' . $list_id . '/members/' . $subscriber_hash;

        return $this->request( $endpoint, 'PUT', $data );
    }

    private function request( $endpoint, $method = 'GET', $body = null ) {
        $url = $this->api_base . $endpoint;

        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( 'apikey:' . $this->api_key ),
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
            $message = isset( $decoded_body['detail'] ) ? $decoded_body['detail'] : 'Unknown API error';
            return new WP_Error( 'mailchimp_api_error', $message, array( 'status' => $code ) );
        }

        return $decoded_body;
    }
}
