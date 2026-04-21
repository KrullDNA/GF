<?php
/**
 * Salesforce API wrapper class.
 *
 * @package KDNA_Forms_Salesforce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_Salesforce_API {

    private $instance_url;

    private $client_id;

    private $client_secret;

    private $username;

    private $password;

    private $access_token;

    private $login_url = 'https://login.salesforce.com';

    private $api_version = 'v59.0';

    private $transient_key = 'kdna_sf_access_token';

    public function __construct( $instance_url, $client_id, $client_secret, $username, $password, $security_token = '' ) {
        $this->instance_url  = rtrim( $instance_url, '/' );
        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->username      = $username;
        $this->password      = $password . $security_token;
        $this->access_token  = get_transient( $this->transient_key );
    }

    public function authenticate() {
        $url = $this->login_url . '/services/oauth2/token';

        $args = array(
            'method'  => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body'    => array(
                'grant_type'    => 'password',
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'username'      => $this->username,
                'password'      => $this->password,
            ),
        );

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 || empty( $body['access_token'] ) ) {
            $message = isset( $body['error_description'] ) ? $body['error_description'] : 'Authentication failed';
            return new WP_Error( 'sf_auth_error', $message, array( 'status' => $code ) );
        }

        $this->access_token = $body['access_token'];

        if ( ! empty( $body['instance_url'] ) ) {
            $this->instance_url = $body['instance_url'];
        }

        set_transient( $this->transient_key, $this->access_token, HOUR_IN_SECONDS );

        return true;
    }

    public function validate() {
        if ( empty( $this->access_token ) ) {
            $auth_result = $this->authenticate();
            if ( is_wp_error( $auth_result ) ) {
                return false;
            }
        }

        $url      = $this->instance_url . '/services/data/' . $this->api_version . '/sobjects/';
        $response = wp_remote_request(
            $url,
            array(
                'method'  => 'GET',
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Content-Type'  => 'application/json',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        return $code >= 200 && $code < 300;
    }

    public function get_fields( $object_type = 'Lead' ) {
        return $this->request( 'sobjects/' . $object_type . '/describe', 'GET' );
    }

    public function create_lead( $data ) {
        return $this->request( 'sobjects/Lead', 'POST', $data );
    }

    public function create_contact( $data ) {
        return $this->request( 'sobjects/Contact', 'POST', $data );
    }

    public function create_record( $object_type, $data ) {
        return $this->request( 'sobjects/' . $object_type, 'POST', $data );
    }

    private function request( $endpoint, $method = 'GET', $body = null ) {
        if ( empty( $this->access_token ) ) {
            $auth_result = $this->authenticate();
            if ( is_wp_error( $auth_result ) ) {
                return $auth_result;
            }
        }

        $url = $this->instance_url . '/services/data/' . $this->api_version . '/' . $endpoint;

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

        if ( 401 === $code ) {
            delete_transient( $this->transient_key );
            $this->access_token = null;

            $auth_result = $this->authenticate();
            if ( is_wp_error( $auth_result ) ) {
                return $auth_result;
            }

            $args['headers']['Authorization'] = 'Bearer ' . $this->access_token;
            $response = wp_remote_request( $url, $args );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $code         = wp_remote_retrieve_response_code( $response );
            $decoded_body = json_decode( wp_remote_retrieve_body( $response ), true );
        }

        if ( $code >= 400 ) {
            $message = 'Unknown API error';
            if ( is_array( $decoded_body ) && isset( $decoded_body[0]['message'] ) ) {
                $message = $decoded_body[0]['message'];
            } elseif ( is_array( $decoded_body ) && isset( $decoded_body['message'] ) ) {
                $message = $decoded_body['message'];
            }
            return new WP_Error( 'sf_api_error', $message, array( 'status' => $code ) );
        }

        return $decoded_body;
    }
}
