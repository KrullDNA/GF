<?php
/**
 * Klaviyo API wrapper class.
 *
 * @package KDNA_Forms_Klaviyo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class KDNA_Klaviyo_API
 *
 * Handles all communication with the Klaviyo API.
 */
class KDNA_Klaviyo_API {

    /**
     * Klaviyo Private API key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Base URL for the Klaviyo API.
     *
     * @var string
     */
    private $api_base = 'https://a.klaviyo.com/api/';

    /**
     * Klaviyo API revision.
     *
     * @var string
     */
    private $revision = '2024-10-15';

    /**
     * Constructor.
     *
     * @param string $api_key Klaviyo Private API key (starts with pk_).
     */
    public function __construct( $api_key ) {
        $this->api_key = $api_key;
    }

    /**
     * Validate the API key by retrieving accounts.
     *
     * @return bool True if the API key is valid, false otherwise.
     */
    public function validate() {
        $result = $this->request( 'accounts' );
        return ! is_wp_error( $result );
    }

    /**
     * Get all lists.
     *
     * @return array|WP_Error Lists array or WP_Error on failure.
     */
    public function get_lists() {
        return $this->request( 'lists' );
    }

    /**
     * Get the standard Klaviyo profile properties.
     *
     * @return array Array of standard profile properties.
     */
    public function get_profile_properties() {
        return array(
            'email'             => 'Email',
            'first_name'        => 'First Name',
            'last_name'         => 'Last Name',
            'phone_number'      => 'Phone Number',
            'organization'      => 'Organization',
            'title'             => 'Title',
            'image'             => 'Image',
            'location.address1' => 'Address 1',
            'location.address2' => 'Address 2',
            'location.city'     => 'City',
            'location.country'  => 'Country',
            'location.region'   => 'Region',
            'location.zip'      => 'Zip',
        );
    }

    /**
     * Create or update a Klaviyo profile.
     *
     * @param array $data Profile attributes (email, first_name, etc.).
     * @return array|WP_Error Response or WP_Error on failure.
     */
    public function create_or_update_profile( $data ) {
        $body = array(
            'data' => array(
                'type'       => 'profile',
                'attributes' => $data,
            ),
        );

        return $this->request( 'profile-import', 'POST', $body );
    }

    /**
     * Subscribe a profile to a list (GDPR-compliant, supports double opt-in).
     *
     * @param string $list_id Klaviyo List ID.
     * @param string $email   Subscriber email address.
     * @param bool   $consent Whether consent has been given (true triggers subscription).
     * @return array|WP_Error Response or WP_Error on failure.
     */
    public function subscribe_to_list( $list_id, $email, $consent ) {
        $profile = array(
            'type'       => 'profile',
            'attributes' => array(
                'email'         => $email,
                'subscriptions' => array(
                    'email' => array(
                        'marketing' => array(
                            'consent' => $consent ? 'SUBSCRIBED' : 'UNSUBSCRIBED',
                        ),
                    ),
                ),
            ),
        );

        $body = array(
            'data' => array(
                'type'          => 'profile-subscription-bulk-create-job',
                'attributes'    => array(
                    'profiles' => array(
                        'data' => array( $profile ),
                    ),
                ),
                'relationships' => array(
                    'list' => array(
                        'data' => array(
                            'type' => 'list',
                            'id'   => $list_id,
                        ),
                    ),
                ),
            ),
        );

        return $this->request( 'profile-subscription-bulk-create-jobs', 'POST', $body );
    }

    /**
     * Make an API request to Klaviyo.
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
                'Authorization' => 'Klaviyo-API-Key ' . $this->api_key,
                'revision'      => $this->revision,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
        );

        if ( null !== $body && 'GET' !== $method ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code        = wp_remote_retrieve_response_code( $response );
        $raw_body    = wp_remote_retrieve_body( $response );
        $decoded_body = json_decode( $raw_body, true );

        if ( $code >= 400 ) {
            $message = 'Unknown API error';
            if ( is_array( $decoded_body ) && ! empty( $decoded_body['errors'] ) && is_array( $decoded_body['errors'] ) ) {
                $first_error = reset( $decoded_body['errors'] );
                if ( is_array( $first_error ) && ! empty( $first_error['detail'] ) ) {
                    $message = $first_error['detail'];
                }
            }
            return new WP_Error( 'klaviyo_api_error', $message, array( 'status' => $code ) );
        }

        if ( null === $decoded_body && '' !== $raw_body ) {
            return array();
        }

        return null === $decoded_body ? array() : $decoded_body;
    }
}
