<?php
/**
 * Kit API wrapper class.
 *
 * @package KDNA_Forms_Kit
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_Kit_API {

    private $api_key;

    private $api_base = 'https://api.kit.com/v4/';

    public function __construct( $api_key ) {
        $this->api_key = $api_key;
    }

    public function validate() {
        $result = $this->request( 'account' );
        return ! is_wp_error( $result );
    }

    public function get_account() {
        return $this->request( 'account' );
    }

    public function get_forms() {
        return $this->get_paginated( 'forms' );
    }

    public function get_tags() {
        return $this->get_paginated( 'tags' );
    }

    public function get_custom_fields() {
        return $this->get_paginated( 'custom_fields' );
    }

    public function add_subscriber( $data ) {
        return $this->request( 'subscribers', 'POST', $data );
    }

    public function create_tag( $name ) {
        return $this->request( 'tags', 'POST', array( 'name' => $name ) );
    }

    public function add_tag_to_subscriber( $subscriber_id, $tag_id ) {
        return $this->request( "tags/{$tag_id}/subscribers/{$subscriber_id}", 'POST' );
    }

    public function add_subscriber_to_form( $form_id, $data ) {
        return $this->request( "forms/{$form_id}/subscribers", 'POST', $data );
    }

    private function get_paginated( $endpoint ) {
        $all_items = array();
        $url       = $endpoint;

        while ( $url ) {
            $result = $this->request( $url );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            if ( isset( $result['data'] ) && is_array( $result['data'] ) ) {
                $all_items = array_merge( $all_items, $result['data'] );
            }

            $url = null;
            if ( ! empty( $result['pagination']['has_next_page'] ) && ! empty( $result['pagination']['end_cursor'] ) ) {
                $separator = ( strpos( $endpoint, '?' ) !== false ) ? '&' : '?';
                $url       = $endpoint . $separator . 'after=' . $result['pagination']['end_cursor'];
            }
        }

        return $all_items;
    }

    private function request( $endpoint, $method = 'GET', $body = null ) {
        $url = $this->api_base . ltrim( $endpoint, '/' );

        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'X-Kit-Api-Key' => $this->api_key,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
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
            $message = 'Unknown API error';
            if ( isset( $decoded_body['errors'][0]['message'] ) ) {
                $message = $decoded_body['errors'][0]['message'];
            } elseif ( isset( $decoded_body['errors'][0] ) && is_string( $decoded_body['errors'][0] ) ) {
                $message = $decoded_body['errors'][0];
            } elseif ( isset( $decoded_body['message'] ) ) {
                $message = $decoded_body['message'];
            } elseif ( isset( $decoded_body['error'] ) ) {
                $message = $decoded_body['error'];
            }
            return new WP_Error( 'kit_api_error', $message, array( 'status' => $code ) );
        }

        return $decoded_body;
    }
}
