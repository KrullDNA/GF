<?php
/**
 * KDNA Forms Campaign Monitor Add-On main class.
 *
 * @package KDNA_Forms_Campaign_Monitor
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class KDNA_CM_AddOn
 *
 * Integrates KDNA Forms with Campaign Monitor for subscriber management.
 */
class KDNA_CM_AddOn extends KDNAFeedAddOn {

    /**
     * Add-on version.
     *
     * @var string
     */
    protected $_version = '1.0.0';

    /**
     * Minimum required version of KDNA Forms.
     *
     * @var string
     */
    protected $_min_kdnaforms_version = '2.9.30';

    /**
     * Add-on slug.
     *
     * @var string
     */
    protected $_slug = 'kdna-forms-campaign-monitor';

    /**
     * Plugin path relative to plugins directory.
     *
     * @var string
     */
    protected $_path = 'kdna-forms-campaign-monitor/kdna-forms-campaign-monitor.php';

    /**
     * Full path to this file.
     *
     * @var string
     */
    protected $_full_path = __FILE__;

    /**
     * Add-on title.
     *
     * @var string
     */
    protected $_title = 'KDNA Forms Campaign Monitor';

    /**
     * Short title for menus.
     *
     * @var string
     */
    protected $_short_title = 'Campaign Monitor';

    /**
     * Capability required to access plugin settings.
     *
     * @var string
     */
    protected $_capabilities_settings_page = 'kdnaform_full_access';

    /**
     * Capability required to access form settings.
     *
     * @var string
     */
    protected $_capabilities_form_settings = 'kdnaform_full_access';

    /**
     * Singleton instance.
     *
     * @var KDNA_CM_AddOn|null
     */
    private static $_instance = null;

    /**
     * API instance.
     *
     * @var KDNA_CM_API|null
     */
    private $_api = null;

    /**
     * Get singleton instance of this class.
     *
     * @return KDNA_CM_AddOn
     */
    public static function get_instance() {
        if ( null === self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Initialize the add-on.
     */
    public function init() {
        parent::init();
        $this->add_delayed_payment_support(
            array(
                'option_label' => esc_html__( 'Subscribe to Campaign Monitor only when payment is received.', 'kdna-forms-cm' ),
            )
        );
    }

    // -------------------------------------------------------------------------
    // Plugin Settings (API Connection)
    // -------------------------------------------------------------------------

    /**
     * Define plugin settings fields for the Campaign Monitor connection.
     *
     * @return array Settings fields configuration.
     */
    public function plugin_settings_fields() {
        return array(
            array(
                'title'       => esc_html__( 'Campaign Monitor API Settings', 'kdna-forms-cm' ),
                'description' => esc_html__( 'Connect your Campaign Monitor account by entering your API credentials below.', 'kdna-forms-cm' ),
                'fields'      => array(
                    array(
                        'name'              => 'api_key',
                        'label'             => esc_html__( 'API Key', 'kdna-forms-cm' ),
                        'type'              => 'text',
                        'class'             => 'medium',
                        'tooltip'           => esc_html__( 'Enter your Campaign Monitor API key. You can find this in your Campaign Monitor account under Account Settings > API keys.', 'kdna-forms-cm' ),
                        'feedback_callback' => array( $this, 'validate_api_key' ),
                    ),
                    array(
                        'name'    => 'client_id',
                        'label'   => esc_html__( 'Client ID', 'kdna-forms-cm' ),
                        'type'    => 'text',
                        'class'   => 'medium',
                        'tooltip' => esc_html__( 'Enter your Campaign Monitor Client ID. You can find this in your Campaign Monitor account under Account Settings > API keys.', 'kdna-forms-cm' ),
                    ),
                    array(
                        'name'              => 'validate_credentials',
                        'label'             => '',
                        'type'              => 'api_status',
                        'callback'          => array( $this, 'render_api_status' ),
                    ),
                ),
            ),
        );
    }

    /**
     * Validate the Campaign Monitor API key.
     *
     * @param string $value The API key value to validate.
     * @return bool True if valid, false otherwise.
     */
    public function validate_api_key( $value ) {
        if ( empty( $value ) ) {
            return false;
        }

        $api = new KDNA_CM_API( $value );
        return $api->validate();
    }

    /**
     * Render the API connection status.
     *
     * @param array $field Field configuration.
     */
    public function render_api_status( $field ) {
        $api_key = $this->get_plugin_setting( 'api_key' );

        if ( empty( $api_key ) ) {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Not connected. Please enter your API Key and Client ID.', 'kdna-forms-cm' );
            echo '</div>';
            return;
        }

        $api    = new KDNA_CM_API( $api_key );
        $valid  = $api->validate();

        if ( $valid ) {
            echo '<div class="alert_green" style="padding: 10px;">';
            echo esc_html__( 'Connected to Campaign Monitor successfully.', 'kdna-forms-cm' );
            echo '</div>';
        } else {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Unable to connect to Campaign Monitor. Please check your API Key.', 'kdna-forms-cm' );
            echo '</div>';
        }
    }

    // -------------------------------------------------------------------------
    // Feed Settings (Per-Form Mapping)
    // -------------------------------------------------------------------------

    /**
     * Define the feed settings fields.
     *
     * @return array Feed settings configuration.
     */
    public function feed_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'Campaign Monitor Feed Settings', 'kdna-forms-cm' ),
                'fields' => array(
                    array(
                        'name'     => 'feed_name',
                        'label'    => esc_html__( 'Feed Name', 'kdna-forms-cm' ),
                        'type'     => 'text',
                        'required' => true,
                        'class'    => 'medium',
                        'tooltip'  => esc_html__( 'Enter a name for this feed to identify it.', 'kdna-forms-cm' ),
                    ),
                    array(
                        'name'     => 'cm_list',
                        'label'    => esc_html__( 'Campaign Monitor List', 'kdna-forms-cm' ),
                        'type'     => 'select',
                        'required' => true,
                        'choices'  => $this->get_cm_lists(),
                        'tooltip'  => esc_html__( 'Select the Campaign Monitor list to which subscribers will be added.', 'kdna-forms-cm' ),
                        'onchange' => "jQuery(this).parents('form').submit();",
                    ),
                    array(
                        'name'       => 'field_map',
                        'label'      => esc_html__( 'Map Fields', 'kdna-forms-cm' ),
                        'type'       => 'field_map',
                        'field_map'  => $this->get_field_map_fields_config(),
                        'tooltip'    => esc_html__( 'Map your form fields to the corresponding Campaign Monitor fields.', 'kdna-forms-cm' ),
                    ),
                    array(
                        'name'    => 'double_optin',
                        'label'   => esc_html__( 'Double Opt-In', 'kdna-forms-cm' ),
                        'type'    => 'checkbox',
                        'choices' => array(
                            array(
                                'label' => esc_html__( 'Require subscribers to confirm their email address (double opt-in)', 'kdna-forms-cm' ),
                                'name'  => 'double_optin',
                            ),
                        ),
                        'tooltip' => esc_html__( 'When enabled, subscribers will receive a confirmation email before being added to the list.', 'kdna-forms-cm' ),
                    ),
                    array(
                        'name'    => 'resubscribe',
                        'label'   => esc_html__( 'Resubscribe', 'kdna-forms-cm' ),
                        'type'    => 'checkbox',
                        'choices' => array(
                            array(
                                'label' => esc_html__( 'Resubscribe contacts who have previously unsubscribed', 'kdna-forms-cm' ),
                                'name'  => 'resubscribe',
                            ),
                        ),
                        'tooltip' => esc_html__( 'When enabled, previously unsubscribed contacts will be re-added to the list.', 'kdna-forms-cm' ),
                    ),
                    array(
                        'name'    => 'feed_condition',
                        'label'   => esc_html__( 'Conditional Logic', 'kdna-forms-cm' ),
                        'type'    => 'feed_condition',
                        'tooltip' => esc_html__( 'When enabled, the subscription will only be processed when the specified conditions are met.', 'kdna-forms-cm' ),
                    ),
                ),
            ),
        );
    }

    /**
     * Define columns displayed on the feed list page.
     *
     * @return array Feed list columns.
     */
    public function feed_list_columns() {
        return array(
            'feed_name' => esc_html__( 'Name', 'kdna-forms-cm' ),
            'cm_list'   => esc_html__( 'Campaign Monitor List', 'kdna-forms-cm' ),
        );
    }

    /**
     * Get the value for the Campaign Monitor List column on the feed list.
     *
     * @param array $feed The current feed.
     * @return string The list name or ID.
     */
    public function get_column_value_cm_list( $feed ) {
        $list_id = rgars( $feed, 'meta/cm_list' );

        if ( empty( $list_id ) ) {
            return esc_html__( 'N/A', 'kdna-forms-cm' );
        }

        $api = $this->get_api();
        if ( null === $api ) {
            return $list_id;
        }

        $details = $api->get_list_details( $list_id );
        if ( is_wp_error( $details ) || empty( $details['Title'] ) ) {
            return $list_id;
        }

        return esc_html( $details['Title'] );
    }

    /**
     * Get Campaign Monitor lists for the select dropdown.
     *
     * @return array Array of choices for the list dropdown.
     */
    public function get_cm_lists() {
        $choices = array(
            array(
                'label' => esc_html__( '-- Select a List --', 'kdna-forms-cm' ),
                'value' => '',
            ),
        );

        $api = $this->get_api();
        if ( null === $api ) {
            return $choices;
        }

        $client_id = $this->get_plugin_setting( 'client_id' );
        if ( empty( $client_id ) ) {
            return $choices;
        }

        $lists = $api->get_lists( $client_id );
        if ( is_wp_error( $lists ) || ! is_array( $lists ) ) {
            $this->log_error( __METHOD__ . '(): Unable to retrieve lists from Campaign Monitor.' );
            return $choices;
        }

        foreach ( $lists as $list ) {
            $choices[] = array(
                'label' => esc_html( $list['Name'] ),
                'value' => $list['ListID'],
            );
        }

        return $choices;
    }

    /**
     * Get field map fields configuration including standard and custom fields.
     *
     * @return array Field map configuration.
     */
    private function get_field_map_fields_config() {
        $fields = array(
            array(
                'name'       => 'email',
                'label'      => esc_html__( 'Email Address', 'kdna-forms-cm' ),
                'required'   => true,
                'field_type' => array( 'email', 'hidden' ),
            ),
            array(
                'name'     => 'name',
                'label'    => esc_html__( 'Full Name', 'kdna-forms-cm' ),
                'required' => false,
            ),
        );

        // Attempt to get custom fields from the selected list.
        $list_id = $this->get_setting( 'cm_list' );
        if ( ! empty( $list_id ) ) {
            $custom_fields = $this->get_cm_custom_fields( $list_id );
            if ( is_array( $custom_fields ) ) {
                foreach ( $custom_fields as $custom_field ) {
                    $fields[] = array(
                        'name'     => sanitize_title( $custom_field['FieldName'] ),
                        'label'    => esc_html( $custom_field['FieldName'] ),
                        'required' => false,
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * Get custom fields for a Campaign Monitor list.
     *
     * @param string $list_id Campaign Monitor List ID.
     * @return array|false Custom fields array or false on failure.
     */
    private function get_cm_custom_fields( $list_id ) {
        $api = $this->get_api();
        if ( null === $api ) {
            return false;
        }

        $custom_fields = $api->get_custom_fields( $list_id );
        if ( is_wp_error( $custom_fields ) ) {
            $this->log_error( __METHOD__ . '(): Unable to retrieve custom fields: ' . $custom_fields->get_error_message() );
            return false;
        }

        return $custom_fields;
    }

    // -------------------------------------------------------------------------
    // Feed Processing
    // -------------------------------------------------------------------------

    /**
     * Process the feed on form submission.
     *
     * @param array $feed  The current feed being processed.
     * @param array $entry The current entry.
     * @param array $form  The current form.
     */
    public function process_feed( $feed, $entry, $form ) {
        $api = $this->get_api();
        if ( null === $api ) {
            $this->log_error( __METHOD__ . '(): Unable to process feed - API not configured.' );
            return $entry;
        }

        // Get the list ID from feed settings.
        $list_id = rgars( $feed, 'meta/cm_list' );
        if ( empty( $list_id ) ) {
            $this->log_error( __METHOD__ . '(): No Campaign Monitor list selected for this feed.' );
            return $entry;
        }

        // Get field map values.
        $field_map = $this->get_field_map_fields( $feed, 'field_map' );

        // Get the email address (required).
        $email = $this->get_field_value( $form, $entry, $field_map['email'] );
        if ( empty( $email ) || ! is_email( $email ) ) {
            $this->log_error( __METHOD__ . '(): Invalid or empty email address. Aborting subscription.' );
            return $entry;
        }

        // Get the name (optional).
        $name = '';
        if ( ! empty( $field_map['name'] ) ) {
            $name = $this->get_field_value( $form, $entry, $field_map['name'] );
        }

        // Build custom fields array.
        $custom_fields = array();
        foreach ( $field_map as $cm_field => $form_field_id ) {
            // Skip standard fields.
            if ( in_array( $cm_field, array( 'email', 'name' ), true ) ) {
                continue;
            }

            if ( empty( $form_field_id ) ) {
                continue;
            }

            $value = $this->get_field_value( $form, $entry, $form_field_id );
            if ( ! empty( $value ) ) {
                $custom_fields[] = array(
                    'Key'   => $cm_field,
                    'Value' => $value,
                );
            }
        }

        // Determine opt-in and resubscribe settings.
        $double_optin = (bool) rgars( $feed, 'meta/double_optin' );
        $resubscribe  = (bool) rgars( $feed, 'meta/resubscribe' );

        // Build subscriber data.
        $subscriber_data = array(
            'EmailAddress'                           => $email,
            'Name'                                   => $name,
            'CustomFields'                           => $custom_fields,
            'Resubscribe'                            => $resubscribe,
            'ConsentToTrack'                         => 'Yes',
        );

        // If not double opt-in, send without confirmation.
        if ( ! $double_optin ) {
            $subscriber_data['ConsentToSendEmail'] = 'Yes';
        }

        $this->log_debug( __METHOD__ . '(): Sending subscriber data to Campaign Monitor: ' . print_r( $subscriber_data, true ) );

        // Add subscriber via API.
        $result = $api->add_subscriber( $list_id, $subscriber_data );

        if ( is_wp_error( $result ) ) {
            $this->log_error( __METHOD__ . '(): Failed to add subscriber to Campaign Monitor. Error: ' . $result->get_error_message() );
            return $entry;
        }

        $this->log_debug( __METHOD__ . '(): Subscriber successfully added to Campaign Monitor. Email: ' . $email );

        return $entry;
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    /**
     * Get an instance of the Campaign Monitor API class.
     *
     * @return KDNA_CM_API|null API instance or null if not configured.
     */
    public function get_api() {
        if ( null !== $this->_api ) {
            return $this->_api;
        }

        $api_key = $this->get_plugin_setting( 'api_key' );
        if ( empty( $api_key ) ) {
            $this->log_debug( __METHOD__ . '(): API key not configured.' );
            return null;
        }

        $this->_api = new KDNA_CM_API( $api_key );
        return $this->_api;
    }

    /**
     * Configures which columns should be displayed on the feed list page.
     *
     * @return array
     */
    public function can_create_feed() {
        $api_key   = $this->get_plugin_setting( 'api_key' );
        $client_id = $this->get_plugin_setting( 'client_id' );

        return ! empty( $api_key ) && ! empty( $client_id );
    }

    /**
     * Display a message when the feed cannot be created.
     *
     * @return string Message to display.
     */
    public function configure_addon_message() {
        $settings_url = admin_url( 'admin.php?page=kdna_settings&subview=' . $this->_slug );
        return sprintf(
            /* translators: %s: URL to settings page */
            esc_html__( 'To get started, please configure your %sCampaign Monitor settings%s.', 'kdna-forms-cm' ),
            '<a href="' . esc_url( $settings_url ) . '">',
            '</a>'
        );
    }

    /**
     * Prevent feeds from being created if API is not configured.
     *
     * @return bool
     */
    public function can_duplicate_feed( $feed_id ) {
        return true;
    }
}
