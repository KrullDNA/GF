<?php
/**
 * KDNA Forms Flodesk Add-On main class.
 *
 * @package KDNA_Forms_Flodesk
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_Flodesk_AddOn extends KDNAFeedAddOn {

    protected $_version = '1.0.0';

    protected $_min_kdnaforms_version = '2.9.30';

    protected $_slug = 'kdna-forms-flodesk';

    protected $_path = 'kdna-forms-flodesk/kdna-forms-flodesk.php';

    protected $_full_path = __FILE__;

    protected $_title = 'KDNA Forms Flodesk';

    protected $_short_title = 'Flodesk';

    protected $_capabilities_settings_page = 'kdnaform_full_access';

    protected $_capabilities_form_settings = 'kdnaform_full_access';

    private static $_instance = null;

    private $_api = null;

    public static function get_instance() {
        if ( null === self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function init() {
        parent::init();
        $this->add_delayed_payment_support(
            array(
                'option_label' => esc_html__( 'Subscribe to Flodesk only when payment is received.', 'kdna-forms-flodesk' ),
            )
        );
    }

    public function plugin_settings_fields() {
        return array(
            array(
                'title'       => esc_html__( 'Flodesk API Settings', 'kdna-forms-flodesk' ),
                'description' => esc_html__( 'Connect your Flodesk account by entering your API key below.', 'kdna-forms-flodesk' ),
                'fields'      => array(
                    array(
                        'name'              => 'api_key',
                        'label'             => esc_html__( 'API Key', 'kdna-forms-flodesk' ),
                        'type'              => 'text',
                        'class'             => 'medium',
                        'tooltip'           => esc_html__( 'Enter your Flodesk API key. You can generate one from your Flodesk account under Integrations > API key.', 'kdna-forms-flodesk' ),
                        'feedback_callback' => array( $this, 'validate_api_key' ),
                    ),
                    array(
                        'name'     => 'api_status',
                        'label'    => '',
                        'type'     => 'api_status',
                        'callback' => array( $this, 'render_api_status' ),
                    ),
                ),
            ),
        );
    }

    public function validate_api_key( $value ) {
        if ( empty( $value ) ) {
            return false;
        }

        $api = new KDNA_Flodesk_API( $value );
        return $api->validate();
    }

    public function render_api_status( $field ) {
        $api_key = $this->get_plugin_setting( 'api_key' );

        if ( empty( $api_key ) ) {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Not connected. Please enter your API Key.', 'kdna-forms-flodesk' );
            echo '</div>';
            return;
        }

        $api   = new KDNA_Flodesk_API( $api_key );
        $valid = $api->validate();

        if ( $valid ) {
            echo '<div class="alert_green" style="padding: 10px;">';
            echo esc_html__( 'Connected to Flodesk successfully.', 'kdna-forms-flodesk' );
            echo '</div>';
        } else {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Unable to connect to Flodesk. Please check your API Key.', 'kdna-forms-flodesk' );
            echo '</div>';
        }
    }

    public function feed_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'Flodesk Feed Settings', 'kdna-forms-flodesk' ),
                'fields' => array(
                    array(
                        'name'     => 'feed_name',
                        'label'    => esc_html__( 'Feed Name', 'kdna-forms-flodesk' ),
                        'type'     => 'text',
                        'required' => true,
                        'class'    => 'medium',
                        'tooltip'  => esc_html__( 'Enter a name for this feed to identify it.', 'kdna-forms-flodesk' ),
                    ),
                    array(
                        'name'     => 'flodesk_segment',
                        'label'    => esc_html__( 'Flodesk Segment', 'kdna-forms-flodesk' ),
                        'type'     => 'select',
                        'required' => true,
                        'choices'  => $this->get_flodesk_segments(),
                        'tooltip'  => esc_html__( 'Select the Flodesk segment to which subscribers will be added.', 'kdna-forms-flodesk' ),
                    ),
                    array(
                        'name'      => 'field_map',
                        'label'     => esc_html__( 'Map Fields', 'kdna-forms-flodesk' ),
                        'type'      => 'field_map',
                        'field_map' => $this->get_field_map_fields_config(),
                        'tooltip'   => esc_html__( 'Map your form fields to the corresponding Flodesk fields.', 'kdna-forms-flodesk' ),
                    ),
                    array(
                        'name'    => 'double_optin',
                        'label'   => esc_html__( 'Double Opt-In', 'kdna-forms-flodesk' ),
                        'type'    => 'checkbox',
                        'choices' => array(
                            array(
                                'label' => esc_html__( 'Require subscribers to confirm their email address (double opt-in)', 'kdna-forms-flodesk' ),
                                'name'  => 'double_optin',
                            ),
                        ),
                        'tooltip' => esc_html__( 'When enabled, subscribers will receive a confirmation email before being added to the segment.', 'kdna-forms-flodesk' ),
                    ),
                    array(
                        'name'    => 'feed_condition',
                        'label'   => esc_html__( 'Conditional Logic', 'kdna-forms-flodesk' ),
                        'type'    => 'feed_condition',
                        'tooltip' => esc_html__( 'When enabled, the subscription will only be processed when the specified conditions are met.', 'kdna-forms-flodesk' ),
                    ),
                ),
            ),
        );
    }

    public function feed_list_columns() {
        return array(
            'feed_name'      => esc_html__( 'Name', 'kdna-forms-flodesk' ),
            'flodesk_segment' => esc_html__( 'Flodesk Segment', 'kdna-forms-flodesk' ),
        );
    }

    public function get_column_value_flodesk_segment( $feed ) {
        $segment_id = rgars( $feed, 'meta/flodesk_segment' );

        if ( empty( $segment_id ) ) {
            return esc_html__( 'N/A', 'kdna-forms-flodesk' );
        }

        $api = $this->get_api();
        if ( null === $api ) {
            return $segment_id;
        }

        $segments = $api->get_segments();
        if ( is_wp_error( $segments ) || ! is_array( $segments ) ) {
            return $segment_id;
        }

        foreach ( $segments as $segment ) {
            if ( isset( $segment['id'] ) && $segment['id'] === $segment_id && ! empty( $segment['name'] ) ) {
                return esc_html( $segment['name'] );
            }
        }

        return $segment_id;
    }

    public function get_flodesk_segments() {
        $choices = array(
            array(
                'label' => esc_html__( '-- Select a Segment --', 'kdna-forms-flodesk' ),
                'value' => '',
            ),
        );

        $api = $this->get_api();
        if ( null === $api ) {
            return $choices;
        }

        $segments = $api->get_segments();
        if ( is_wp_error( $segments ) || ! is_array( $segments ) ) {
            $this->log_error( __METHOD__ . '(): Unable to retrieve segments from Flodesk.' );
            return $choices;
        }

        foreach ( $segments as $segment ) {
            if ( isset( $segment['id'], $segment['name'] ) ) {
                $choices[] = array(
                    'label' => esc_html( $segment['name'] ),
                    'value' => $segment['id'],
                );
            }
        }

        return $choices;
    }

    private function get_field_map_fields_config() {
        return array(
            array(
                'name'       => 'email',
                'label'      => esc_html__( 'Email Address', 'kdna-forms-flodesk' ),
                'required'   => true,
                'field_type' => array( 'email', 'hidden' ),
            ),
            array(
                'name'     => 'first_name',
                'label'    => esc_html__( 'First Name', 'kdna-forms-flodesk' ),
                'required' => false,
            ),
            array(
                'name'     => 'last_name',
                'label'    => esc_html__( 'Last Name', 'kdna-forms-flodesk' ),
                'required' => false,
            ),
        );
    }

    public function process_feed( $feed, $entry, $form ) {
        $api = $this->get_api();
        if ( null === $api ) {
            $this->log_error( __METHOD__ . '(): Unable to process feed - API not configured.' );
            return $entry;
        }

        $segment_id = rgars( $feed, 'meta/flodesk_segment' );
        if ( empty( $segment_id ) ) {
            $this->log_error( __METHOD__ . '(): No Flodesk segment selected for this feed.' );
            return $entry;
        }

        $field_map = $this->get_field_map_fields( $feed, 'field_map' );

        $email = $this->get_field_value( $form, $entry, $field_map['email'] );
        if ( empty( $email ) || ! is_email( $email ) ) {
            $this->log_error( __METHOD__ . '(): Invalid or empty email address. Aborting subscription.' );
            return $entry;
        }

        $subscriber_data = array(
            'email' => $email,
        );

        if ( ! empty( $field_map['first_name'] ) ) {
            $first_name = $this->get_field_value( $form, $entry, $field_map['first_name'] );
            if ( ! empty( $first_name ) ) {
                $subscriber_data['first_name'] = $first_name;
            }
        }

        if ( ! empty( $field_map['last_name'] ) ) {
            $last_name = $this->get_field_value( $form, $entry, $field_map['last_name'] );
            if ( ! empty( $last_name ) ) {
                $subscriber_data['last_name'] = $last_name;
            }
        }

        $double_optin = (bool) rgars( $feed, 'meta/double_optin' );
        if ( $double_optin ) {
            $subscriber_data['double_optin'] = true;
        }

        $this->log_debug( __METHOD__ . '(): Sending subscriber data to Flodesk: ' . print_r( $subscriber_data, true ) );

        $result = $api->add_subscriber( $subscriber_data );

        if ( is_wp_error( $result ) ) {
            $this->log_error( __METHOD__ . '(): Failed to add subscriber to Flodesk. Error: ' . $result->get_error_message() );
            return $entry;
        }

        $subscriber_id = isset( $result['id'] ) ? $result['id'] : '';
        if ( empty( $subscriber_id ) ) {
            $this->log_error( __METHOD__ . '(): Subscriber created but no ID returned. Cannot add to segment.' );
            return $entry;
        }

        $segment_result = $api->add_subscriber_to_segment( $subscriber_id, $segment_id );

        if ( is_wp_error( $segment_result ) ) {
            $this->log_error( __METHOD__ . '(): Failed to add subscriber to segment. Error: ' . $segment_result->get_error_message() );
            return $entry;
        }

        $this->log_debug( __METHOD__ . '(): Subscriber successfully added to Flodesk segment. Email: ' . $email );

        return $entry;
    }

    public function get_api() {
        if ( null !== $this->_api ) {
            return $this->_api;
        }

        $api_key = $this->get_plugin_setting( 'api_key' );
        if ( empty( $api_key ) ) {
            $this->log_debug( __METHOD__ . '(): API key not configured.' );
            return null;
        }

        $this->_api = new KDNA_Flodesk_API( $api_key );
        return $this->_api;
    }

    public function can_create_feed() {
        $api_key = $this->get_plugin_setting( 'api_key' );
        return ! empty( $api_key );
    }

    public function configure_addon_message() {
        $settings_url = admin_url( 'admin.php?page=kdna_settings&subview=' . $this->_slug );
        return sprintf(
            esc_html__( 'To get started, please configure your %sFlodesk settings%s.', 'kdna-forms-flodesk' ),
            '<a href="' . esc_url( $settings_url ) . '">',
            '</a>'
        );
    }

    public function can_duplicate_feed( $feed_id ) {
        return true;
    }
}
