<?php
/**
 * KDNA Forms Klaviyo Add-On main class.
 *
 * @package KDNA_Forms_Klaviyo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class KDNA_Klaviyo_AddOn
 *
 * Integrates KDNA Forms with Klaviyo for profile and list management.
 */
class KDNA_Klaviyo_AddOn extends KDNAFeedAddOn {

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
    protected $_slug = 'kdna-forms-klaviyo';

    /**
     * Plugin path relative to plugins directory.
     *
     * @var string
     */
    protected $_path = 'kdna-forms-klaviyo/kdna-forms-klaviyo.php';

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
    protected $_title = 'KDNA Forms Klaviyo';

    /**
     * Short title for menus.
     *
     * @var string
     */
    protected $_short_title = 'Klaviyo';

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
     * @var KDNA_Klaviyo_AddOn|null
     */
    private static $_instance = null;

    /**
     * API instance.
     *
     * @var KDNA_Klaviyo_API|null
     */
    private $_api = null;

    /**
     * Get singleton instance of this class.
     *
     * @return KDNA_Klaviyo_AddOn
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
                'option_label' => esc_html__( 'Subscribe to Klaviyo only when payment is received.', 'kdna-forms-klaviyo' ),
            )
        );
    }

    // -------------------------------------------------------------------------
    // Plugin Settings (API Connection)
    // -------------------------------------------------------------------------

    /**
     * Define plugin settings fields for the Klaviyo connection.
     *
     * @return array Settings fields configuration.
     */
    public function plugin_settings_fields() {
        return array(
            array(
                'title'       => esc_html__( 'Klaviyo API Settings', 'kdna-forms-klaviyo' ),
                'description' => esc_html__( 'Connect your Klaviyo account by entering your Private API Key below.', 'kdna-forms-klaviyo' ),
                'fields'      => array(
                    array(
                        'name'              => 'api_key',
                        'label'             => esc_html__( 'Private API Key', 'kdna-forms-klaviyo' ),
                        'type'              => 'text',
                        'class'             => 'medium',
                        'tooltip'           => esc_html__( 'Enter your Klaviyo Private API Key (starts with pk_). You can create one in your Klaviyo account under Account > Settings > API Keys.', 'kdna-forms-klaviyo' ),
                        'feedback_callback' => array( $this, 'validate_api_key' ),
                    ),
                    array(
                        'name'     => 'validate_credentials',
                        'label'    => '',
                        'type'     => 'api_status',
                        'callback' => array( $this, 'render_api_status' ),
                    ),
                ),
            ),
        );
    }

    /**
     * Validate the Klaviyo Private API key.
     *
     * @param string $value The API key value to validate.
     * @return bool True if valid, false otherwise.
     */
    public function validate_api_key( $value ) {
        if ( empty( $value ) ) {
            return false;
        }

        $api = new KDNA_Klaviyo_API( $value );
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
            echo esc_html__( 'Not connected. Please enter your Private API Key.', 'kdna-forms-klaviyo' );
            echo '</div>';
            return;
        }

        $api   = new KDNA_Klaviyo_API( $api_key );
        $valid = $api->validate();

        if ( $valid ) {
            echo '<div class="alert_green" style="padding: 10px;">';
            echo esc_html__( 'Connected to Klaviyo successfully.', 'kdna-forms-klaviyo' );
            echo '</div>';
        } else {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Unable to connect to Klaviyo. Please check your Private API Key.', 'kdna-forms-klaviyo' );
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
                'title'  => esc_html__( 'Klaviyo Feed Settings', 'kdna-forms-klaviyo' ),
                'fields' => array(
                    array(
                        'name'     => 'feed_name',
                        'label'    => esc_html__( 'Feed Name', 'kdna-forms-klaviyo' ),
                        'type'     => 'text',
                        'required' => true,
                        'class'    => 'medium',
                        'tooltip'  => esc_html__( 'Enter a name for this feed to identify it.', 'kdna-forms-klaviyo' ),
                    ),
                    array(
                        'name'     => 'klaviyo_list',
                        'label'    => esc_html__( 'Klaviyo List', 'kdna-forms-klaviyo' ),
                        'type'     => 'select',
                        'required' => false,
                        'choices'  => $this->get_klaviyo_lists(),
                        'tooltip'  => esc_html__( 'Select the Klaviyo list to which subscribers will be added.', 'kdna-forms-klaviyo' ),
                    ),
                    array(
                        'name'      => 'field_map',
                        'label'     => esc_html__( 'Map Fields', 'kdna-forms-klaviyo' ),
                        'type'      => 'field_map',
                        'field_map' => $this->get_field_map_fields_config(),
                        'tooltip'   => esc_html__( 'Map your form fields to the corresponding Klaviyo profile properties.', 'kdna-forms-klaviyo' ),
                    ),
                    array(
                        'name'    => 'double_optin',
                        'label'   => esc_html__( 'Double Opt-In / Custom Consent', 'kdna-forms-klaviyo' ),
                        'type'    => 'checkbox',
                        'choices' => array(
                            array(
                                'label' => esc_html__( 'Subscribe contact to list with marketing consent (triggers double opt-in flow if configured on the list).', 'kdna-forms-klaviyo' ),
                                'name'  => 'double_optin',
                            ),
                        ),
                        'tooltip' => esc_html__( 'When enabled, the subscription is sent with consent so Klaviyo will either subscribe immediately or trigger the configured double opt-in flow.', 'kdna-forms-klaviyo' ),
                    ),
                    array(
                        'name'    => 'feed_condition',
                        'label'   => esc_html__( 'Conditional Logic', 'kdna-forms-klaviyo' ),
                        'type'    => 'feed_condition',
                        'tooltip' => esc_html__( 'When enabled, the subscription will only be processed when the specified conditions are met.', 'kdna-forms-klaviyo' ),
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
            'feed_name'    => esc_html__( 'Name', 'kdna-forms-klaviyo' ),
            'klaviyo_list' => esc_html__( 'Klaviyo List', 'kdna-forms-klaviyo' ),
        );
    }

    /**
     * Get the value for the Klaviyo List column on the feed list.
     *
     * @param array $feed The current feed.
     * @return string The list name or ID.
     */
    public function get_column_value_klaviyo_list( $feed ) {
        $list_id = rgars( $feed, 'meta/klaviyo_list' );

        if ( empty( $list_id ) ) {
            return esc_html__( 'N/A', 'kdna-forms-klaviyo' );
        }

        $api = $this->get_api();
        if ( null === $api ) {
            return $list_id;
        }

        $lists = $api->get_lists();
        if ( is_wp_error( $lists ) || empty( $lists['data'] ) ) {
            return $list_id;
        }

        foreach ( $lists['data'] as $list ) {
            if ( isset( $list['id'] ) && $list['id'] === $list_id ) {
                $name = isset( $list['attributes']['name'] ) ? $list['attributes']['name'] : $list_id;
                return esc_html( $name );
            }
        }

        return $list_id;
    }

    /**
     * Get Klaviyo lists for the select dropdown.
     *
     * @return array Array of choices for the list dropdown.
     */
    public function get_klaviyo_lists() {
        $choices = array(
            array(
                'label' => esc_html__( '-- Select a List --', 'kdna-forms-klaviyo' ),
                'value' => '',
            ),
        );

        $api = $this->get_api();
        if ( null === $api ) {
            return $choices;
        }

        $lists = $api->get_lists();
        if ( is_wp_error( $lists ) || empty( $lists['data'] ) || ! is_array( $lists['data'] ) ) {
            $this->log_error( __METHOD__ . '(): Unable to retrieve lists from Klaviyo.' );
            return $choices;
        }

        foreach ( $lists['data'] as $list ) {
            if ( empty( $list['id'] ) ) {
                continue;
            }
            $name      = isset( $list['attributes']['name'] ) ? $list['attributes']['name'] : $list['id'];
            $choices[] = array(
                'label' => esc_html( $name ),
                'value' => $list['id'],
            );
        }

        return $choices;
    }

    /**
     * Get field map fields configuration for Klaviyo standard properties.
     *
     * @return array Field map configuration.
     */
    private function get_field_map_fields_config() {
        return array(
            array(
                'name'       => 'email',
                'label'      => esc_html__( 'Email Address', 'kdna-forms-klaviyo' ),
                'required'   => true,
                'field_type' => array( 'email', 'hidden' ),
            ),
            array(
                'name'     => 'first_name',
                'label'    => esc_html__( 'First Name', 'kdna-forms-klaviyo' ),
                'required' => false,
            ),
            array(
                'name'     => 'last_name',
                'label'    => esc_html__( 'Last Name', 'kdna-forms-klaviyo' ),
                'required' => false,
            ),
            array(
                'name'     => 'phone_number',
                'label'    => esc_html__( 'Phone Number', 'kdna-forms-klaviyo' ),
                'required' => false,
            ),
            array(
                'name'     => 'organization',
                'label'    => esc_html__( 'Organization', 'kdna-forms-klaviyo' ),
                'required' => false,
            ),
            array(
                'name'     => 'title',
                'label'    => esc_html__( 'Title', 'kdna-forms-klaviyo' ),
                'required' => false,
            ),
        );
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
     * @return array The entry.
     */
    public function process_feed( $feed, $entry, $form ) {
        $api = $this->get_api();
        if ( null === $api ) {
            $this->log_error( __METHOD__ . '(): Unable to process feed - API not configured.' );
            return $entry;
        }

        $field_map = $this->get_field_map_fields( $feed, 'field_map' );

        $email = $this->get_field_value( $form, $entry, $field_map['email'] );
        if ( empty( $email ) || ! is_email( $email ) ) {
            $this->log_error( __METHOD__ . '(): Invalid or empty email address. Aborting subscription.' );
            return $entry;
        }

        $attributes = array(
            'email' => $email,
        );

        $standard_fields = array( 'first_name', 'last_name', 'phone_number', 'organization', 'title' );
        foreach ( $standard_fields as $field_name ) {
            if ( empty( $field_map[ $field_name ] ) ) {
                continue;
            }
            $value = $this->get_field_value( $form, $entry, $field_map[ $field_name ] );
            if ( '' !== $value && null !== $value ) {
                $attributes[ $field_name ] = $value;
            }
        }

        $this->log_debug( __METHOD__ . '(): Sending profile data to Klaviyo: ' . print_r( $attributes, true ) );

        $result = $api->create_or_update_profile( $attributes );

        if ( is_wp_error( $result ) ) {
            $this->log_error( __METHOD__ . '(): Failed to create/update Klaviyo profile. Error: ' . $result->get_error_message() );
            return $entry;
        }

        $this->log_debug( __METHOD__ . '(): Profile successfully created/updated in Klaviyo. Email: ' . $email );

        $list_id = rgars( $feed, 'meta/klaviyo_list' );
        if ( ! empty( $list_id ) ) {
            $consent           = (bool) rgars( $feed, 'meta/double_optin' );
            $subscribe_result  = $api->subscribe_to_list( $list_id, $email, $consent );

            if ( is_wp_error( $subscribe_result ) ) {
                $this->log_error( __METHOD__ . '(): Failed to subscribe profile to list ' . $list_id . '. Error: ' . $subscribe_result->get_error_message() );
                return $entry;
            }

            $this->log_debug( __METHOD__ . '(): Profile successfully subscribed to Klaviyo list ' . $list_id . '. Email: ' . $email );
        }

        return $entry;
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    /**
     * Get an instance of the Klaviyo API class.
     *
     * @return KDNA_Klaviyo_API|null API instance or null if not configured.
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

        $this->_api = new KDNA_Klaviyo_API( $api_key );
        return $this->_api;
    }

    /**
     * Determine if feeds can be created (requires API key configured).
     *
     * @return bool
     */
    public function can_create_feed() {
        $api_key = $this->get_plugin_setting( 'api_key' );
        return ! empty( $api_key );
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
            esc_html__( 'To get started, please configure your %1$sKlaviyo settings%2$s.', 'kdna-forms-klaviyo' ),
            '<a href="' . esc_url( $settings_url ) . '">',
            '</a>'
        );
    }

    /**
     * Allow duplicating feeds.
     *
     * @param int $feed_id Feed ID.
     * @return bool
     */
    public function can_duplicate_feed( $feed_id ) {
        return true;
    }
}
