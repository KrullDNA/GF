<?php
/**
 * KDNA Forms Salesforce Add-On main class.
 *
 * @package KDNA_Forms_Salesforce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_Salesforce_AddOn extends KDNAFeedAddOn {

    protected $_version = '1.0.0';

    protected $_min_kdnaforms_version = '2.9.30';

    protected $_slug = 'kdna-forms-salesforce';

    protected $_path = 'kdna-forms-salesforce/kdna-forms-salesforce.php';

    protected $_full_path = __FILE__;

    protected $_title = 'KDNA Forms Salesforce';

    protected $_short_title = 'Salesforce';

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
                'option_label' => esc_html__( 'Send to Salesforce only when payment is received.', 'kdna-forms-salesforce' ),
            )
        );
    }

    public function plugin_settings_fields() {
        return array(
            array(
                'title'       => esc_html__( 'Salesforce API Settings', 'kdna-forms-salesforce' ),
                'description' => esc_html__( 'Connect your Salesforce account by entering your API credentials below. Use the OAuth2 Username-Password flow.', 'kdna-forms-salesforce' ),
                'fields'      => array(
                    array(
                        'name'    => 'instance_url',
                        'label'   => esc_html__( 'Instance URL', 'kdna-forms-salesforce' ),
                        'type'    => 'text',
                        'class'   => 'medium',
                        'tooltip' => esc_html__( 'Enter your Salesforce instance URL (e.g. https://yourorg.my.salesforce.com).', 'kdna-forms-salesforce' ),
                    ),
                    array(
                        'name'    => 'consumer_key',
                        'label'   => esc_html__( 'Consumer Key (Client ID)', 'kdna-forms-salesforce' ),
                        'type'    => 'text',
                        'class'   => 'medium',
                        'tooltip' => esc_html__( 'Enter the Consumer Key from your Salesforce Connected App.', 'kdna-forms-salesforce' ),
                    ),
                    array(
                        'name'    => 'consumer_secret',
                        'label'   => esc_html__( 'Consumer Secret (Client Secret)', 'kdna-forms-salesforce' ),
                        'type'    => 'text',
                        'class'   => 'medium',
                        'tooltip' => esc_html__( 'Enter the Consumer Secret from your Salesforce Connected App.', 'kdna-forms-salesforce' ),
                    ),
                    array(
                        'name'    => 'username',
                        'label'   => esc_html__( 'Username', 'kdna-forms-salesforce' ),
                        'type'    => 'text',
                        'class'   => 'medium',
                        'tooltip' => esc_html__( 'Enter your Salesforce username.', 'kdna-forms-salesforce' ),
                    ),
                    array(
                        'name'              => 'password',
                        'label'             => esc_html__( 'Password (with Security Token)', 'kdna-forms-salesforce' ),
                        'type'              => 'text',
                        'class'             => 'medium',
                        'tooltip'           => esc_html__( 'Enter your Salesforce password with your security token appended (e.g. MyPassword123SecurityToken).', 'kdna-forms-salesforce' ),
                        'feedback_callback' => array( $this, 'validate_credentials' ),
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

    public function validate_credentials() {
        $instance_url    = $this->get_plugin_setting( 'instance_url' );
        $consumer_key    = $this->get_plugin_setting( 'consumer_key' );
        $consumer_secret = $this->get_plugin_setting( 'consumer_secret' );
        $username        = $this->get_plugin_setting( 'username' );
        $password        = $this->get_plugin_setting( 'password' );

        if ( empty( $instance_url ) || empty( $consumer_key ) || empty( $consumer_secret ) || empty( $username ) || empty( $password ) ) {
            return false;
        }

        $api = new KDNA_Salesforce_API( $instance_url, $consumer_key, $consumer_secret, $username, $password );
        return $api->validate();
    }

    public function render_api_status( $field ) {
        $instance_url    = $this->get_plugin_setting( 'instance_url' );
        $consumer_key    = $this->get_plugin_setting( 'consumer_key' );
        $consumer_secret = $this->get_plugin_setting( 'consumer_secret' );
        $username        = $this->get_plugin_setting( 'username' );
        $password        = $this->get_plugin_setting( 'password' );

        if ( empty( $instance_url ) || empty( $consumer_key ) || empty( $consumer_secret ) || empty( $username ) || empty( $password ) ) {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Not connected. Please enter all Salesforce API credentials.', 'kdna-forms-salesforce' );
            echo '</div>';
            return;
        }

        $api   = new KDNA_Salesforce_API( $instance_url, $consumer_key, $consumer_secret, $username, $password );
        $valid = $api->validate();

        if ( $valid ) {
            echo '<div class="alert_green" style="padding: 10px;">';
            echo esc_html__( 'Connected to Salesforce successfully.', 'kdna-forms-salesforce' );
            echo '</div>';
        } else {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Unable to connect to Salesforce. Please check your credentials.', 'kdna-forms-salesforce' );
            echo '</div>';
        }
    }

    public function feed_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'Salesforce Feed Settings', 'kdna-forms-salesforce' ),
                'fields' => array(
                    array(
                        'name'     => 'feed_name',
                        'label'    => esc_html__( 'Feed Name', 'kdna-forms-salesforce' ),
                        'type'     => 'text',
                        'required' => true,
                        'class'    => 'medium',
                        'tooltip'  => esc_html__( 'Enter a name for this feed to identify it.', 'kdna-forms-salesforce' ),
                    ),
                    array(
                        'name'     => 'object_type',
                        'label'    => esc_html__( 'Salesforce Object', 'kdna-forms-salesforce' ),
                        'type'     => 'select',
                        'required' => true,
                        'choices'  => array(
                            array(
                                'label' => esc_html__( '-- Select Object Type --', 'kdna-forms-salesforce' ),
                                'value' => '',
                            ),
                            array(
                                'label' => esc_html__( 'Lead', 'kdna-forms-salesforce' ),
                                'value' => 'Lead',
                            ),
                            array(
                                'label' => esc_html__( 'Contact', 'kdna-forms-salesforce' ),
                                'value' => 'Contact',
                            ),
                        ),
                        'tooltip'  => esc_html__( 'Select whether to create a Lead or Contact in Salesforce.', 'kdna-forms-salesforce' ),
                        'onchange' => "jQuery(this).parents('form').submit();",
                    ),
                    array(
                        'name'      => 'field_map',
                        'label'     => esc_html__( 'Map Fields', 'kdna-forms-salesforce' ),
                        'type'      => 'field_map',
                        'field_map' => $this->get_field_map_fields_config(),
                        'tooltip'   => esc_html__( 'Map your form fields to the corresponding Salesforce fields.', 'kdna-forms-salesforce' ),
                    ),
                    array(
                        'name'    => 'lead_source',
                        'label'   => esc_html__( 'Lead Source', 'kdna-forms-salesforce' ),
                        'type'    => 'text',
                        'class'   => 'medium',
                        'tooltip' => esc_html__( 'Enter a value for the LeadSource field in Salesforce (e.g. Web Form).', 'kdna-forms-salesforce' ),
                    ),
                    array(
                        'name'    => 'feed_condition',
                        'label'   => esc_html__( 'Conditional Logic', 'kdna-forms-salesforce' ),
                        'type'    => 'feed_condition',
                        'tooltip' => esc_html__( 'When enabled, the Salesforce record will only be created when the specified conditions are met.', 'kdna-forms-salesforce' ),
                    ),
                ),
            ),
        );
    }

    public function feed_list_columns() {
        return array(
            'feed_name'   => esc_html__( 'Name', 'kdna-forms-salesforce' ),
            'object_type' => esc_html__( 'Salesforce Object', 'kdna-forms-salesforce' ),
        );
    }

    public function get_column_value_object_type( $feed ) {
        $object_type = rgars( $feed, 'meta/object_type' );

        if ( empty( $object_type ) ) {
            return esc_html__( 'N/A', 'kdna-forms-salesforce' );
        }

        return esc_html( $object_type );
    }

    private function get_field_map_fields_config() {
        $fields = array(
            array(
                'name'       => 'Email',
                'label'      => esc_html__( 'Email Address', 'kdna-forms-salesforce' ),
                'required'   => true,
                'field_type' => array( 'email', 'hidden' ),
            ),
            array(
                'name'     => 'FirstName',
                'label'    => esc_html__( 'First Name', 'kdna-forms-salesforce' ),
                'required' => false,
            ),
            array(
                'name'     => 'LastName',
                'label'    => esc_html__( 'Last Name', 'kdna-forms-salesforce' ),
                'required' => false,
            ),
            array(
                'name'     => 'Company',
                'label'    => esc_html__( 'Company', 'kdna-forms-salesforce' ),
                'required' => false,
            ),
            array(
                'name'     => 'Phone',
                'label'    => esc_html__( 'Phone', 'kdna-forms-salesforce' ),
                'required' => false,
            ),
            array(
                'name'     => 'Title',
                'label'    => esc_html__( 'Title', 'kdna-forms-salesforce' ),
                'required' => false,
            ),
            array(
                'name'     => 'Description',
                'label'    => esc_html__( 'Description', 'kdna-forms-salesforce' ),
                'required' => false,
            ),
        );

        $object_type = $this->get_setting( 'object_type' );
        if ( ! empty( $object_type ) ) {
            $dynamic_fields = $this->get_salesforce_dynamic_fields( $object_type );
            if ( is_array( $dynamic_fields ) ) {
                $standard_names = array( 'Email', 'FirstName', 'LastName', 'Company', 'Phone', 'Title', 'Description' );
                foreach ( $dynamic_fields as $sf_field ) {
                    if ( in_array( $sf_field['name'], $standard_names, true ) ) {
                        continue;
                    }
                    $fields[] = array(
                        'name'     => $sf_field['name'],
                        'label'    => esc_html( $sf_field['label'] ),
                        'required' => false,
                    );
                }
            }
        }

        return $fields;
    }

    private function get_salesforce_dynamic_fields( $object_type ) {
        $api = $this->get_api();
        if ( null === $api ) {
            return false;
        }

        $result = $api->get_fields( $object_type );
        if ( is_wp_error( $result ) || ! isset( $result['fields'] ) ) {
            $this->log_error( __METHOD__ . '(): Unable to retrieve fields from Salesforce.' );
            return false;
        }

        $creatable_fields = array();
        foreach ( $result['fields'] as $field ) {
            if ( ! empty( $field['createable'] ) && ! empty( $field['name'] ) && ! empty( $field['label'] ) ) {
                $creatable_fields[] = array(
                    'name'  => $field['name'],
                    'label' => $field['label'],
                );
            }
        }

        return $creatable_fields;
    }

    public function process_feed( $feed, $entry, $form ) {
        $api = $this->get_api();
        if ( null === $api ) {
            $this->log_error( __METHOD__ . '(): Unable to process feed - API not configured.' );
            return $entry;
        }

        $object_type = rgars( $feed, 'meta/object_type' );
        if ( empty( $object_type ) ) {
            $this->log_error( __METHOD__ . '(): No Salesforce object type selected for this feed.' );
            return $entry;
        }

        $field_map = $this->get_field_map_fields( $feed, 'field_map' );

        $email = '';
        if ( ! empty( $field_map['Email'] ) ) {
            $email = $this->get_field_value( $form, $entry, $field_map['Email'] );
        }

        if ( empty( $email ) || ! is_email( $email ) ) {
            $this->log_error( __METHOD__ . '(): Invalid or empty email address. Aborting Salesforce record creation.' );
            return $entry;
        }

        $record_data = array(
            'Email' => $email,
        );

        foreach ( $field_map as $sf_field => $form_field_id ) {
            if ( 'Email' === $sf_field ) {
                continue;
            }

            if ( empty( $form_field_id ) ) {
                continue;
            }

            $value = $this->get_field_value( $form, $entry, $form_field_id );
            if ( '' !== $value && null !== $value ) {
                $record_data[ $sf_field ] = $value;
            }
        }

        $lead_source = rgars( $feed, 'meta/lead_source' );
        if ( ! empty( $lead_source ) ) {
            $record_data['LeadSource'] = $lead_source;
        }

        $this->log_debug( __METHOD__ . '(): Sending ' . $object_type . ' data to Salesforce: ' . print_r( $record_data, true ) );

        $result = $api->create_record( $object_type, $record_data );

        if ( is_wp_error( $result ) ) {
            $this->log_error( __METHOD__ . '(): Failed to create ' . $object_type . ' in Salesforce. Error: ' . $result->get_error_message() );
            return $entry;
        }

        $record_id = isset( $result['id'] ) ? $result['id'] : 'unknown';
        $this->log_debug( __METHOD__ . '(): ' . $object_type . ' successfully created in Salesforce. Record ID: ' . $record_id );

        return $entry;
    }

    public function get_api() {
        if ( null !== $this->_api ) {
            return $this->_api;
        }

        $instance_url    = $this->get_plugin_setting( 'instance_url' );
        $consumer_key    = $this->get_plugin_setting( 'consumer_key' );
        $consumer_secret = $this->get_plugin_setting( 'consumer_secret' );
        $username        = $this->get_plugin_setting( 'username' );
        $password        = $this->get_plugin_setting( 'password' );

        if ( empty( $instance_url ) || empty( $consumer_key ) || empty( $consumer_secret ) || empty( $username ) || empty( $password ) ) {
            $this->log_debug( __METHOD__ . '(): Salesforce API credentials not fully configured.' );
            return null;
        }

        $this->_api = new KDNA_Salesforce_API( $instance_url, $consumer_key, $consumer_secret, $username, $password );
        return $this->_api;
    }

    public function can_create_feed() {
        $instance_url    = $this->get_plugin_setting( 'instance_url' );
        $consumer_key    = $this->get_plugin_setting( 'consumer_key' );
        $consumer_secret = $this->get_plugin_setting( 'consumer_secret' );
        $username        = $this->get_plugin_setting( 'username' );
        $password        = $this->get_plugin_setting( 'password' );

        return ! empty( $instance_url ) && ! empty( $consumer_key ) && ! empty( $consumer_secret ) && ! empty( $username ) && ! empty( $password );
    }

    public function configure_addon_message() {
        $settings_url = admin_url( 'admin.php?page=kdna_settings&subview=' . $this->_slug );
        return sprintf(
            esc_html__( 'To get started, please configure your %sSalesforce settings%s.', 'kdna-forms-salesforce' ),
            '<a href="' . esc_url( $settings_url ) . '">',
            '</a>'
        );
    }

    public function can_duplicate_feed( $feed_id ) {
        return true;
    }
}
