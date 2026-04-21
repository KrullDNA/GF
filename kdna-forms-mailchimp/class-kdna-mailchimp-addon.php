<?php
/**
 * KDNA Forms Mailchimp Add-On main class.
 *
 * @package KDNA_Forms_Mailchimp
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_Mailchimp_AddOn extends KDNAFeedAddOn {

    protected $_version = '1.0.0';

    protected $_min_kdnaforms_version = '2.9.30';

    protected $_slug = 'kdna-forms-mailchimp';

    protected $_path = 'kdna-forms-mailchimp/kdna-forms-mailchimp.php';

    protected $_full_path = __FILE__;

    protected $_title = 'KDNA Forms Mailchimp';

    protected $_short_title = 'Mailchimp';

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
                'option_label' => esc_html__( 'Subscribe to Mailchimp only when payment is received.', 'kdna-forms-mailchimp' ),
            )
        );
    }

    public function plugin_settings_fields() {
        return array(
            array(
                'title'       => esc_html__( 'Mailchimp API Settings', 'kdna-forms-mailchimp' ),
                'description' => esc_html__( 'Connect your Mailchimp account by entering your API key below.', 'kdna-forms-mailchimp' ),
                'fields'      => array(
                    array(
                        'name'              => 'api_key',
                        'label'             => esc_html__( 'API Key', 'kdna-forms-mailchimp' ),
                        'type'              => 'text',
                        'class'             => 'medium',
                        'tooltip'           => esc_html__( 'Enter your Mailchimp API key. You can find this in your Mailchimp account under Account > Extras > API keys.', 'kdna-forms-mailchimp' ),
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

        $api = new KDNA_Mailchimp_API( $value );
        return $api->validate();
    }

    public function render_api_status( $field ) {
        $api_key = $this->get_plugin_setting( 'api_key' );

        if ( empty( $api_key ) ) {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Not connected. Please enter your API Key.', 'kdna-forms-mailchimp' );
            echo '</div>';
            return;
        }

        $api   = new KDNA_Mailchimp_API( $api_key );
        $valid = $api->validate();

        if ( $valid ) {
            echo '<div class="alert_green" style="padding: 10px;">';
            echo esc_html__( 'Connected to Mailchimp successfully.', 'kdna-forms-mailchimp' );
            echo '</div>';
        } else {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Unable to connect to Mailchimp. Please check your API Key.', 'kdna-forms-mailchimp' );
            echo '</div>';
        }
    }

    public function feed_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'Mailchimp Feed Settings', 'kdna-forms-mailchimp' ),
                'fields' => array(
                    array(
                        'name'     => 'feed_name',
                        'label'    => esc_html__( 'Feed Name', 'kdna-forms-mailchimp' ),
                        'type'     => 'text',
                        'required' => true,
                        'class'    => 'medium',
                        'tooltip'  => esc_html__( 'Enter a name for this feed to identify it.', 'kdna-forms-mailchimp' ),
                    ),
                    array(
                        'name'     => 'mailchimp_list',
                        'label'    => esc_html__( 'Audience', 'kdna-forms-mailchimp' ),
                        'type'     => 'select',
                        'required' => true,
                        'choices'  => $this->get_mailchimp_lists(),
                        'tooltip'  => esc_html__( 'Select the Mailchimp audience to which subscribers will be added.', 'kdna-forms-mailchimp' ),
                        'onchange' => "jQuery(this).parents('form').submit();",
                    ),
                    array(
                        'name'      => 'field_map',
                        'label'     => esc_html__( 'Map Fields', 'kdna-forms-mailchimp' ),
                        'type'      => 'field_map',
                        'field_map' => $this->get_field_map_fields_config(),
                        'tooltip'   => esc_html__( 'Map your form fields to the corresponding Mailchimp fields.', 'kdna-forms-mailchimp' ),
                    ),
                    array(
                        'name'    => 'double_optin',
                        'label'   => esc_html__( 'Double Opt-In', 'kdna-forms-mailchimp' ),
                        'type'    => 'checkbox',
                        'choices' => array(
                            array(
                                'label' => esc_html__( 'Require subscribers to confirm their email address (double opt-in)', 'kdna-forms-mailchimp' ),
                                'name'  => 'double_optin',
                            ),
                        ),
                        'tooltip' => esc_html__( 'When enabled, subscribers will receive a confirmation email before being added to the audience.', 'kdna-forms-mailchimp' ),
                    ),
                    array(
                        'name'    => 'tags',
                        'label'   => esc_html__( 'Tags', 'kdna-forms-mailchimp' ),
                        'type'    => 'text',
                        'class'   => 'medium',
                        'tooltip' => esc_html__( 'Enter comma-separated tags to apply to the subscriber.', 'kdna-forms-mailchimp' ),
                    ),
                    array(
                        'name'    => 'feed_condition',
                        'label'   => esc_html__( 'Conditional Logic', 'kdna-forms-mailchimp' ),
                        'type'    => 'feed_condition',
                        'tooltip' => esc_html__( 'When enabled, the subscription will only be processed when the specified conditions are met.', 'kdna-forms-mailchimp' ),
                    ),
                ),
            ),
        );
    }

    public function feed_list_columns() {
        return array(
            'feed_name'      => esc_html__( 'Name', 'kdna-forms-mailchimp' ),
            'mailchimp_list' => esc_html__( 'Mailchimp Audience', 'kdna-forms-mailchimp' ),
        );
    }

    public function get_column_value_mailchimp_list( $feed ) {
        $list_id = rgars( $feed, 'meta/mailchimp_list' );

        if ( empty( $list_id ) ) {
            return esc_html__( 'N/A', 'kdna-forms-mailchimp' );
        }

        $api = $this->get_api();
        if ( null === $api ) {
            return $list_id;
        }

        $list = $api->get_list( $list_id );
        if ( is_wp_error( $list ) || empty( $list['name'] ) ) {
            return $list_id;
        }

        return esc_html( $list['name'] );
    }

    public function get_mailchimp_lists() {
        $choices = array(
            array(
                'label' => esc_html__( '-- Select an Audience --', 'kdna-forms-mailchimp' ),
                'value' => '',
            ),
        );

        $api = $this->get_api();
        if ( null === $api ) {
            return $choices;
        }

        $lists = $api->get_lists();
        if ( is_wp_error( $lists ) || ! is_array( $lists ) ) {
            $this->log_error( __METHOD__ . '(): Unable to retrieve audiences from Mailchimp.' );
            return $choices;
        }

        foreach ( $lists as $list ) {
            $choices[] = array(
                'label' => esc_html( $list['name'] ),
                'value' => $list['id'],
            );
        }

        return $choices;
    }

    private function get_field_map_fields_config() {
        $fields = array(
            array(
                'name'       => 'email',
                'label'      => esc_html__( 'Email Address', 'kdna-forms-mailchimp' ),
                'required'   => true,
                'field_type' => array( 'email', 'hidden' ),
            ),
            array(
                'name'     => 'FNAME',
                'label'    => esc_html__( 'First Name', 'kdna-forms-mailchimp' ),
                'required' => false,
            ),
            array(
                'name'     => 'LNAME',
                'label'    => esc_html__( 'Last Name', 'kdna-forms-mailchimp' ),
                'required' => false,
            ),
        );

        $list_id = $this->get_setting( 'mailchimp_list' );
        if ( ! empty( $list_id ) ) {
            $merge_fields = $this->get_mailchimp_merge_fields( $list_id );
            if ( is_array( $merge_fields ) ) {
                $standard_tags = array( 'EMAIL', 'FNAME', 'LNAME' );
                foreach ( $merge_fields as $merge_field ) {
                    if ( in_array( $merge_field['tag'], $standard_tags, true ) ) {
                        continue;
                    }
                    $fields[] = array(
                        'name'     => $merge_field['tag'],
                        'label'    => esc_html( $merge_field['name'] ),
                        'required' => false,
                    );
                }
            }
        }

        return $fields;
    }

    private function get_mailchimp_merge_fields( $list_id ) {
        $api = $this->get_api();
        if ( null === $api ) {
            return false;
        }

        $merge_fields = $api->get_merge_fields( $list_id );
        if ( is_wp_error( $merge_fields ) ) {
            $this->log_error( __METHOD__ . '(): Unable to retrieve merge fields: ' . $merge_fields->get_error_message() );
            return false;
        }

        return $merge_fields;
    }

    public function process_feed( $feed, $entry, $form ) {
        $api = $this->get_api();
        if ( null === $api ) {
            $this->log_error( __METHOD__ . '(): Unable to process feed - API not configured.' );
            return $entry;
        }

        $list_id = rgars( $feed, 'meta/mailchimp_list' );
        if ( empty( $list_id ) ) {
            $this->log_error( __METHOD__ . '(): No Mailchimp audience selected for this feed.' );
            return $entry;
        }

        $field_map = $this->get_field_map_fields( $feed, 'field_map' );

        $email = $this->get_field_value( $form, $entry, $field_map['email'] );
        if ( empty( $email ) || ! is_email( $email ) ) {
            $this->log_error( __METHOD__ . '(): Invalid or empty email address. Aborting subscription.' );
            return $entry;
        }

        $merge_fields = array();

        foreach ( $field_map as $mc_field => $form_field_id ) {
            if ( 'email' === $mc_field ) {
                continue;
            }

            if ( empty( $form_field_id ) ) {
                continue;
            }

            $value = $this->get_field_value( $form, $entry, $form_field_id );
            if ( ! empty( $value ) ) {
                $merge_fields[ $mc_field ] = $value;
            }
        }

        $double_optin = (bool) rgars( $feed, 'meta/double_optin' );
        $status       = $double_optin ? 'pending' : 'subscribed';

        $subscriber_data = array(
            'email_address' => $email,
            'status_if_new' => $status,
            'status'        => $status,
        );

        if ( ! empty( $merge_fields ) ) {
            $subscriber_data['merge_fields'] = $merge_fields;
        }

        $tags_setting = rgars( $feed, 'meta/tags' );
        if ( ! empty( $tags_setting ) ) {
            $tags_array = array_map( 'trim', explode( ',', $tags_setting ) );
            $tags_array = array_filter( $tags_array );
            if ( ! empty( $tags_array ) ) {
                $subscriber_data['tags'] = $tags_array;
            }
        }

        $this->log_debug( __METHOD__ . '(): Sending subscriber data to Mailchimp: ' . print_r( $subscriber_data, true ) );

        $result = $api->add_or_update_subscriber( $list_id, $subscriber_data );

        if ( is_wp_error( $result ) ) {
            $this->log_error( __METHOD__ . '(): Failed to add subscriber to Mailchimp. Error: ' . $result->get_error_message() );
            return $entry;
        }

        $this->log_debug( __METHOD__ . '(): Subscriber successfully added to Mailchimp. Email: ' . $email );

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

        $this->_api = new KDNA_Mailchimp_API( $api_key );
        return $this->_api;
    }

    public function can_create_feed() {
        $api_key = $this->get_plugin_setting( 'api_key' );
        return ! empty( $api_key );
    }

    public function configure_addon_message() {
        $settings_url = admin_url( 'admin.php?page=kdna_settings&subview=' . $this->_slug );
        return sprintf(
            esc_html__( 'To get started, please configure your %sMailchimp settings%s.', 'kdna-forms-mailchimp' ),
            '<a href="' . esc_url( $settings_url ) . '">',
            '</a>'
        );
    }

    public function can_duplicate_feed( $feed_id ) {
        return true;
    }
}
