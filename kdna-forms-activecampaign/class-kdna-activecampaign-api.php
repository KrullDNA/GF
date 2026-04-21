<?php
/**
 * ActiveCampaign API wrapper class.
 *
 * @package KDNA_Forms_ActiveCampaign
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_ActiveCampaign_API {

    private $api_key;

    private $api_base;

    public function __construct( $api_url, $api_key ) {
        $this->api_key  = $api_key;
        $this->api_base = rtrim( $api_url, '/' ) . '/api/3/';
    }

    public function validate() {
        $result = $this->request( 'users/me' );
        return ! is_wp_error( $result );
    }

    public function get_lists() {
        return $this->request( 'lists?limit=100' );
    }

    public function get_list( $list_id ) {
        return $this->request( 'lists/' . $list_id );
    }

    public function get_custom_fields() {
        return $this->request( 'fields?limit=100' );
    }

    public function get_tags() {
        return $this->request( 'tags?limit=100' );
    }

    public function create_or_update_contact( $data ) {
        return $this->request( 'contact/sync', 'POST', array( 'contact' => $data ) );
    }

    public function add_contact_to_list( $contact_id, $list_id ) {
        $data = array(
            'contactList' => array(
                'list'    => (int) $list_id,
                'contact' => (int) $contact_id,
                'status'  => 1,
            ),
        );
        return $this->request( 'contactLists', 'POST', $data );
    }

    public function add_tag_to_contact( $contact_id, $tag_id ) {
        $data = array(
            'contactTag' => array(
                'contact' => (string) $contact_id,
                'tag'     => (string) $tag_id,
            ),
        );
        return $this->request( 'contactTags', 'POST', $data );
    }

    public function update_custom_field_value( $contact_id, $field_id, $value ) {
        $data = array(
            'fieldValue' => array(
                'contact' => (string) $contact_id,
                'field'   => (string) $field_id,
                'value'   => $value,
            ),
        );
        return $this->request( 'fieldValues', 'POST', $data );
    }

    private function request( $endpoint, $method = 'GET', $body = null ) {
        $url = $this->api_base . $endpoint;

        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Api-Token'    => $this->api_key,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
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
            return new WP_Error( 'ac_api_error', $message, array( 'status' => $code ) );
        }

        return $decoded_body;
    }
}
