<?php
/**
 * KDNA Forms Kit Add-On main class.
 *
 * @package KDNA_Forms_Kit
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_Kit_AddOn extends KDNAFeedAddOn {

    protected $_version = '1.0.0';

    protected $_min_kdnaforms_version = '2.9.30';

    protected $_slug = 'kdna-forms-kit';

    protected $_path = 'kdna-forms-kit/kdna-forms-kit.php';

    protected $_full_path = __FILE__;

    protected $_title = 'KDNA Forms Kit';

    protected $_short_title = 'Kit';

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
                'option_label' => esc_html__( 'Subscribe to Kit only when payment is received.', 'kdna-forms-kit' ),
            )
        );
    }

    // -------------------------------------------------------------------------
    // Plugin Settings
    // -------------------------------------------------------------------------

    public function plugin_settings_fields() {
        return array(
            array(
                'title'       => esc_html__( 'Kit API Settings', 'kdna-forms-kit' ),
                'description' => esc_html__( 'Connect your Kit account by entering your API secret below.', 'kdna-forms-kit' ),
                'fields'      => array(
                    array(
                        'name'              => 'api_secret',
                        'label'             => esc_html__( 'API Secret', 'kdna-forms-kit' ),
                        'type'              => 'text',
                        'class'             => 'medium',
                        'tooltip'           => esc_html__( 'Enter your Kit API secret. You can find this in your Kit account under Settings > Developer.', 'kdna-forms-kit' ),
                        'feedback_callback' => array( $this, 'validate_api_secret' ),
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

    public function validate_api_secret( $value ) {
        if ( empty( $value ) ) {
            return false;
        }

        $api = new KDNA_Kit_API( $value );
        return $api->validate();
    }

    public function render_api_status( $field ) {
        $api_secret = $this->get_plugin_setting( 'api_secret' );

        if ( empty( $api_secret ) ) {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Not connected. Please enter your API Secret.', 'kdna-forms-kit' );
            echo '</div>';
            return;
        }

        $api     = new KDNA_Kit_API( $api_secret );
        $account = $api->get_account();

        if ( is_wp_error( $account ) ) {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Unable to connect to Kit. Please check your API Secret.', 'kdna-forms-kit' );
            echo '</div>';
        } else {
            $name = isset( $account['name'] ) ? $account['name'] : '';
            echo '<div class="alert_green" style="padding: 10px;">';
            printf(
                esc_html__( 'Connected to Kit successfully. Account: %s', 'kdna-forms-kit' ),
                esc_html( $name )
            );
            echo '</div>';
        }
    }

    // -------------------------------------------------------------------------
    // Feed Settings
    // -------------------------------------------------------------------------

    public function feed_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'Kit Feed Settings', 'kdna-forms-kit' ),
                'fields' => array(
                    array(
                        'name'     => 'feed_name',
                        'label'    => esc_html__( 'Feed Name', 'kdna-forms-kit' ),
                        'type'     => 'text',
                        'required' => true,
                        'class'    => 'medium',
                        'tooltip'  => esc_html__( 'Enter a name for this feed to identify it.', 'kdna-forms-kit' ),
                    ),
                    array(
                        'name'     => 'kit_form',
                        'label'    => esc_html__( 'Kit Form', 'kdna-forms-kit' ),
                        'type'     => 'select',
                        'required' => false,
                        'choices'  => $this->get_kit_forms(),
                        'tooltip'  => esc_html__( 'Select a Kit form to subscribe contacts through. This determines the opt-in experience.', 'kdna-forms-kit' ),
                    ),
                    array(
                        'name'      => 'field_map',
                        'label'     => esc_html__( 'Map Fields', 'kdna-forms-kit' ),
                        'type'      => 'field_map',
                        'field_map' => $this->get_field_map_fields_config(),
                        'tooltip'   => esc_html__( 'Map your form fields to the corresponding Kit fields.', 'kdna-forms-kit' ),
                    ),
                    array(
                        'name'    => 'custom_fields',
                        'label'   => esc_html__( 'Custom Fields', 'kdna-forms-kit' ),
                        'type'    => 'dynamic_field_map',
                        'field_map' => $this->get_custom_fields_config(),
                        'tooltip' => esc_html__( 'Map form fields to Kit custom fields.', 'kdna-forms-kit' ),
                    ),
                    array(
                        'name'    => 'tags',
                        'label'   => esc_html__( 'Tags', 'kdna-forms-kit' ),
                        'type'    => 'select',
                        'multiple' => true,
                        'choices' => $this->get_kit_tags(),
                        'tooltip' => esc_html__( 'Select tags to apply to the subscriber.', 'kdna-forms-kit' ),
                    ),
                    array(
                        'name'    => 'feed_condition',
                        'label'   => esc_html__( 'Conditional Logic', 'kdna-forms-kit' ),
                        'type'    => 'feed_condition',
                        'tooltip' => esc_html__( 'When enabled, the subscription will only be processed when the specified conditions are met.', 'kdna-forms-kit' ),
                    ),
                ),
            ),
        );
    }

    public function feed_list_columns() {
        return array(
            'feed_name' => esc_html__( 'Name', 'kdna-forms-kit' ),
            'kit_form'  => esc_html__( 'Kit Form', 'kdna-forms-kit' ),
        );
    }

    public function get_column_value_kit_form( $feed ) {
        $form_id = rgars( $feed, 'meta/kit_form' );

        if ( empty( $form_id ) ) {
            return esc_html__( 'None (direct subscriber)', 'kdna-forms-kit' );
        }

        $api = $this->get_api();
        if ( null === $api ) {
            return $form_id;
        }

        $forms = $api->get_forms();
        if ( is_wp_error( $forms ) || ! is_array( $forms ) ) {
            return $form_id;
        }

        foreach ( $forms as $form ) {
            if ( (string) $form['id'] === (string) $form_id ) {
                return esc_html( $form['name'] );
            }
        }

        return $form_id;
    }

    // -------------------------------------------------------------------------
    // Feed Processing
    // -------------------------------------------------------------------------

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

        $first_name = '';
        if ( ! empty( $field_map['first_name'] ) ) {
            $first_name = $this->get_field_value( $form, $entry, $field_map['first_name'] );
        }

        // Build custom fields from dynamic field map.
        $custom_fields = array();
        $custom_map    = rgars( $feed, 'meta/custom_fields' );
        if ( is_array( $custom_map ) ) {
            foreach ( $custom_map as $mapping ) {
                $kit_key     = rgar( $mapping, 'key' );
                $form_field  = rgar( $mapping, 'value' );
                if ( empty( $kit_key ) || empty( $form_field ) ) {
                    continue;
                }
                $value = $this->get_field_value( $form, $entry, $form_field );
                if ( '' !== $value ) {
                    $custom_fields[ $kit_key ] = $value;
                }
            }
        }

        $subscriber_data = array(
            'email_address' => $email,
            'first_name'    => $first_name,
        );

        if ( ! empty( $custom_fields ) ) {
            $subscriber_data['fields'] = $custom_fields;
        }

        $kit_form_id = rgars( $feed, 'meta/kit_form' );

        if ( ! empty( $kit_form_id ) ) {
            $result = $api->add_subscriber_to_form( $kit_form_id, $subscriber_data );
        } else {
            $result = $api->add_subscriber( $subscriber_data );
        }

        if ( is_wp_error( $result ) ) {
            $this->log_error( __METHOD__ . '(): Failed to add subscriber to Kit. Error: ' . $result->get_error_message() );
            return $entry;
        }

        $subscriber_id = isset( $result['subscriber']['id'] ) ? $result['subscriber']['id'] : null;

        // Apply tags.
        $tags = rgars( $feed, 'meta/tags' );
        if ( ! empty( $tags ) && ! empty( $subscriber_id ) ) {
            if ( ! is_array( $tags ) ) {
                $tags = array( $tags );
            }
            foreach ( $tags as $tag_id ) {
                if ( ! empty( $tag_id ) ) {
                    $tag_result = $api->add_tag_to_subscriber( $subscriber_id, $tag_id );
                    if ( is_wp_error( $tag_result ) ) {
                        $this->log_error( __METHOD__ . "(): Failed to add tag {$tag_id} to subscriber. Error: " . $tag_result->get_error_message() );
                    }
                }
            }
        }

        $this->log_debug( __METHOD__ . '(): Subscriber successfully added to Kit. Email: ' . $email );

        return $entry;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function get_kit_forms() {
        $choices = array(
            array(
                'label' => esc_html__( '-- None (direct subscriber) --', 'kdna-forms-kit' ),
                'value' => '',
            ),
        );

        $api = $this->get_api();
        if ( null === $api ) {
            return $choices;
        }

        $forms = $api->get_forms();
        if ( is_wp_error( $forms ) || ! is_array( $forms ) ) {
            $this->log_error( __METHOD__ . '(): Unable to retrieve forms from Kit.' );
            return $choices;
        }

        foreach ( $forms as $form ) {
            $choices[] = array(
                'label' => esc_html( $form['name'] ),
                'value' => $form['id'],
            );
        }

        return $choices;
    }

    private function get_kit_tags() {
        $choices = array();

        $api = $this->get_api();
        if ( null === $api ) {
            return $choices;
        }

        $tags = $api->get_tags();
        if ( is_wp_error( $tags ) || ! is_array( $tags ) ) {
            $this->log_error( __METHOD__ . '(): Unable to retrieve tags from Kit.' );
            return $choices;
        }

        foreach ( $tags as $tag ) {
            $choices[] = array(
                'label' => esc_html( $tag['name'] ),
                'value' => $tag['id'],
            );
        }

        return $choices;
    }

    private function get_field_map_fields_config() {
        return array(
            array(
                'name'       => 'email',
                'label'      => esc_html__( 'Email Address', 'kdna-forms-kit' ),
                'required'   => true,
                'field_type' => array( 'email', 'hidden' ),
            ),
            array(
                'name'     => 'first_name',
                'label'    => esc_html__( 'First Name', 'kdna-forms-kit' ),
                'required' => false,
            ),
        );
    }

    private function get_custom_fields_config() {
        $fields = array();

        $api = $this->get_api();
        if ( null === $api ) {
            return $fields;
        }

        $custom_fields = $api->get_custom_fields();
        if ( is_wp_error( $custom_fields ) || ! is_array( $custom_fields ) ) {
            return $fields;
        }

        foreach ( $custom_fields as $custom_field ) {
            $fields[] = array(
                'name'  => $custom_field['key'],
                'label' => esc_html( $custom_field['label'] ),
            );
        }

        return $fields;
    }

    public function get_api() {
        if ( null !== $this->_api ) {
            return $this->_api;
        }

        $api_secret = $this->get_plugin_setting( 'api_secret' );
        if ( empty( $api_secret ) ) {
            $this->log_debug( __METHOD__ . '(): API secret not configured.' );
            return null;
        }

        $this->_api = new KDNA_Kit_API( $api_secret );
        return $this->_api;
    }

    public function can_create_feed() {
        $api_secret = $this->get_plugin_setting( 'api_secret' );
        return ! empty( $api_secret );
    }

    public function configure_addon_message() {
        $settings_url = admin_url( 'admin.php?page=kdna_settings&subview=' . $this->_slug );
        return sprintf(
            esc_html__( 'To get started, please configure your %sKit settings%s.', 'kdna-forms-kit' ),
            '<a href="' . esc_url( $settings_url ) . '">',
            '</a>'
        );
    }

    public function can_duplicate_feed( $feed_id ) {
        return true;
    }
}
