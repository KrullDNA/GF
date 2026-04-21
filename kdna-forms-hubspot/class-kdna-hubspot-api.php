<?php
/**
 * HubSpot API wrapper class.
 *
 * @package KDNA_Forms_HubSpot
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_HubSpot_API {

    private $access_token;

    private $api_base = 'https://api.hubapi.com/';

    public function __construct( $access_token ) {
        $this->access_token = $access_token;
    }

    public function validate() {
        $result = $this->request( 'account-info/v3/api-usage/daily/private-app' );
        return ! is_wp_error( $result );
    }

    public function get_contact_lists() {
        return $this->request( 'contacts/v1/lists?count=250' );
    }

    public function get_contact_properties() {
        return $this->request( 'crm/v3/properties/contacts' );
    }

    public function create_or_update_contact( $email, $properties ) {
        $properties['email'] = $email;

        $data = array(
            'properties' => $properties,
        );

        $result = $this->request( 'crm/v3/objects/contacts', 'POST', $data );

        if ( is_wp_error( $result ) && 409 === (int) $result->get_error_data( 'hubspot_api_error' )['status'] ) {
            $error_data = $result->get_error_data( 'hubspot_api_error' );
            $existing_id = isset( $error_data['existing_id'] ) ? $error_data['existing_id'] : null;

            if ( ! $existing_id && isset( $error_data['message'] ) ) {
                if ( preg_match( '/Existing ID:\s*(\d+)/', $error_data['message'], $matches ) ) {
                    $existing_id = $matches[1];
                }
            }

            if ( $existing_id ) {
                return $this->request( 'crm/v3/objects/contacts/' . $existing_id, 'PATCH', $data );
            }

            return $result;
        }

        return $result;
    }

    public function add_contact_to_list( $contact_id, $list_id ) {
        $data = array(
            'vids' => array( (int) $contact_id ),
        );

        return $this->request( 'contacts/v1/lists/' . $list_id . '/add', 'POST', $data );
    }

    private function request( $endpoint, $method = 'GET', $body = null ) {
        $url = $this->api_base . $endpoint;

        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
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

            $error_data = array( 'status' => $code );
            if ( 409 === $code && isset( $decoded_body['message'] ) ) {
                $error_data['message'] = $decoded_body['message'];
            }

            return new WP_Error( 'hubspot_api_error', $message, $error_data );
        }

        return $decoded_body;
    }
}
