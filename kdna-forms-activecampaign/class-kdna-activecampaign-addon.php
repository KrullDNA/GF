<?php
/**
 * KDNA Forms ActiveCampaign Add-On main class.
 *
 * @package KDNA_Forms_ActiveCampaign
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_ActiveCampaign_AddOn extends KDNAFeedAddOn {

    protected $_version = '1.0.0';

    protected $_min_kdnaforms_version = '2.9.30';

    protected $_slug = 'kdna-forms-activecampaign';

    protected $_path = 'kdna-forms-activecampaign/kdna-forms-activecampaign.php';

    protected $_full_path = __FILE__;

    protected $_title = 'KDNA Forms ActiveCampaign';

    protected $_short_title = 'ActiveCampaign';

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
                'option_label' => esc_html__( 'Subscribe to ActiveCampaign only when payment is received.', 'kdna-forms-activecampaign' ),
            )
        );
    }

    public function plugin_settings_fields() {
        return array(
            array(
                'title'       => esc_html__( 'ActiveCampaign API Settings', 'kdna-forms-activecampaign' ),
                'description' => esc_html__( 'Connect your ActiveCampaign account by entering your API credentials below.', 'kdna-forms-activecampaign' ),
                'fields'      => array(
                    array(
                        'name'    => 'api_url',
                        'label'   => esc_html__( 'API URL', 'kdna-forms-activecampaign' ),
                        'type'    => 'text',
                        'class'   => 'medium',
                        'tooltip' => esc_html__( 'Enter your ActiveCampaign account URL (e.g. https://yourname.api-us1.com). Found under Settings > Developer.', 'kdna-forms-activecampaign' ),
                    ),
                    array(
                        'name'              => 'api_key',
                        'label'             => esc_html__( 'API Key', 'kdna-forms-activecampaign' ),
                        'type'              => 'text',
                        'class'             => 'medium',
                        'tooltip'           => esc_html__( 'Enter your ActiveCampaign API key. Found under Settings > Developer.', 'kdna-forms-activecampaign' ),
                        'feedback_callback' => array( $this, 'validate_api_credentials' ),
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

    public function validate_api_credentials( $value ) {
        $api_url = $this->get_plugin_setting( 'api_url' );
        if ( empty( $api_url ) || empty( $value ) ) {
            return false;
        }

        $api = new KDNA_ActiveCampaign_API( $api_url, $value );
        return $api->validate();
    }

    public function render_api_status( $field ) {
        $api_url = $this->get_plugin_setting( 'api_url' );
        $api_key = $this->get_plugin_setting( 'api_key' );

        if ( empty( $api_url ) || empty( $api_key ) ) {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Not connected. Please enter your API URL and API Key.', 'kdna-forms-activecampaign' );
            echo '</div>';
            return;
        }

        $api   = new KDNA_ActiveCampaign_API( $api_url, $api_key );
        $valid = $api->validate();

        if ( $valid ) {
            echo '<div class="alert_green" style="padding: 10px;">';
            echo esc_html__( 'Connected to ActiveCampaign successfully.', 'kdna-forms-activecampaign' );
            echo '</div>';
        } else {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Unable to connect to ActiveCampaign. Please check your API URL and API Key.', 'kdna-forms-activecampaign' );
            echo '</div>';
        }
    }

    public function feed_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'ActiveCampaign Feed Settings', 'kdna-forms-activecampaign' ),
                'fields' => array(
                    array(
                        'name'     => 'feed_name',
                        'label'    => esc_html__( 'Feed Name', 'kdna-forms-activecampaign' ),
                        'type'     => 'text',
                        'required' => true,
                        'class'    => 'medium',
                        'tooltip'  => esc_html__( 'Enter a name for this feed to identify it.', 'kdna-forms-activecampaign' ),
                    ),
                    array(
                        'name'     => 'ac_list',
                        'label'    => esc_html__( 'ActiveCampaign List', 'kdna-forms-activecampaign' ),
                        'type'     => 'select',
                        'required' => true,
                        'choices'  => $this->get_ac_lists(),
                        'tooltip'  => esc_html__( 'Select the ActiveCampaign list to which contacts will be added.', 'kdna-forms-activecampaign' ),
                        'onchange' => "jQuery(this).parents('form').submit();",
                    ),
                    array(
                        'name'      => 'field_map',
                        'label'     => esc_html__( 'Map Fields', 'kdna-forms-activecampaign' ),
                        'type'      => 'field_map',
                        'field_map' => $this->get_field_map_fields_config(),
                        'tooltip'   => esc_html__( 'Map your form fields to the corresponding ActiveCampaign fields.', 'kdna-forms-activecampaign' ),
                    ),
                    array(
                        'name'    => 'ac_tags',
                        'label'   => esc_html__( 'Tags', 'kdna-forms-activecampaign' ),
                        'type'    => 'select',
                        'multiple' => true,
                        'choices' => $this->get_ac_tags(),
                        'tooltip' => esc_html__( 'Select tags to apply to contacts added by this feed.', 'kdna-forms-activecampaign' ),
                    ),
                    array(
                        'name'    => 'feed_condition',
                        'label'   => esc_html__( 'Conditional Logic', 'kdna-forms-activecampaign' ),
                        'type'    => 'feed_condition',
                        'tooltip' => esc_html__( 'When enabled, the contact will only be added when the specified conditions are met.', 'kdna-forms-activecampaign' ),
                    ),
                ),
            ),
        );
    }

    public function feed_list_columns() {
        return array(
            'feed_name' => esc_html__( 'Name', 'kdna-forms-activecampaign' ),
            'ac_list'   => esc_html__( 'ActiveCampaign List', 'kdna-forms-activecampaign' ),
        );
    }

    public function get_column_value_ac_list( $feed ) {
        $list_id = rgars( $feed, 'meta/ac_list' );

        if ( empty( $list_id ) ) {
            return esc_html__( 'N/A', 'kdna-forms-activecampaign' );
        }

        $api = $this->get_api();
        if ( null === $api ) {
            return $list_id;
        }

        $result = $api->get_list( $list_id );
        if ( is_wp_error( $result ) || empty( $result['list']['name'] ) ) {
            return $list_id;
        }

        return esc_html( $result['list']['name'] );
    }

    private function get_ac_lists() {
        $choices = array(
            array(
                'label' => esc_html__( '-- Select a List --', 'kdna-forms-activecampaign' ),
                'value' => '',
            ),
        );

        $api = $this->get_api();
        if ( null === $api ) {
            return $choices;
        }

        $result = $api->get_lists();
        if ( is_wp_error( $result ) || ! isset( $result['lists'] ) || ! is_array( $result['lists'] ) ) {
            $this->log_error( __METHOD__ . '(): Unable to retrieve lists from ActiveCampaign.' );
            return $choices;
        }

        foreach ( $result['lists'] as $list ) {
            $choices[] = array(
                'label' => esc_html( $list['name'] ),
                'value' => $list['id'],
            );
        }

        return $choices;
    }

    private function get_ac_tags() {
        $choices = array(
            array(
                'label' => esc_html__( '-- Select Tags --', 'kdna-forms-activecampaign' ),
                'value' => '',
            ),
        );

        $api = $this->get_api();
        if ( null === $api ) {
            return $choices;
        }

        $result = $api->get_tags();
        if ( is_wp_error( $result ) || ! isset( $result['tags'] ) || ! is_array( $result['tags'] ) ) {
            return $choices;
        }

        foreach ( $result['tags'] as $tag ) {
            $choices[] = array(
                'label' => esc_html( $tag['tag'] ),
                'value' => $tag['id'],
            );
        }

        return $choices;
    }

    private function get_field_map_fields_config() {
        $fields = array(
            array(
                'name'       => 'email',
                'label'      => esc_html__( 'Email Address', 'kdna-forms-activecampaign' ),
                'required'   => true,
                'field_type' => array( 'email', 'hidden' ),
            ),
            array(
                'name'     => 'first_name',
                'label'    => esc_html__( 'First Name', 'kdna-forms-activecampaign' ),
                'required' => false,
            ),
            array(
                'name'     => 'last_name',
                'label'    => esc_html__( 'Last Name', 'kdna-forms-activecampaign' ),
                'required' => false,
            ),
            array(
                'name'     => 'phone',
                'label'    => esc_html__( 'Phone', 'kdna-forms-activecampaign' ),
                'required' => false,
            ),
        );

        $api = $this->get_api();
        if ( null !== $api ) {
            $result = $api->get_custom_fields();
            if ( ! is_wp_error( $result ) && isset( $result['fields'] ) && is_array( $result['fields'] ) ) {
                foreach ( $result['fields'] as $custom_field ) {
                    $fields[] = array(
                        'name'     => 'custom_' . $custom_field['id'],
                        'label'    => esc_html( $custom_field['title'] ),
                        'required' => false,
                    );
                }
            }
        }

        return $fields;
    }

    public function process_feed( $feed, $entry, $form ) {
        $api = $this->get_api();
        if ( null === $api ) {
            $this->log_error( __METHOD__ . '(): Unable to process feed - API not configured.' );
            return $entry;
        }

        $list_id = rgars( $feed, 'meta/ac_list' );
        if ( empty( $list_id ) ) {
            $this->log_error( __METHOD__ . '(): No ActiveCampaign list selected for this feed.' );
            return $entry;
        }

        $field_map = $this->get_field_map_fields( $feed, 'field_map' );

        $email = $this->get_field_value( $form, $entry, $field_map['email'] );
        if ( empty( $email ) || ! is_email( $email ) ) {
            $this->log_error( __METHOD__ . '(): Invalid or empty email address. Aborting.' );
            return $entry;
        }

        $contact_data = array(
            'email' => $email,
        );

        if ( ! empty( $field_map['first_name'] ) ) {
            $first_name = $this->get_field_value( $form, $entry, $field_map['first_name'] );
            if ( ! empty( $first_name ) ) {
                $contact_data['firstName'] = $first_name;
            }
        }

        if ( ! empty( $field_map['last_name'] ) ) {
            $last_name = $this->get_field_value( $form, $entry, $field_map['last_name'] );
            if ( ! empty( $last_name ) ) {
                $contact_data['lastName'] = $last_name;
            }
        }

        if ( ! empty( $field_map['phone'] ) ) {
            $phone = $this->get_field_value( $form, $entry, $field_map['phone'] );
            if ( ! empty( $phone ) ) {
                $contact_data['phone'] = $phone;
            }
        }

        $this->log_debug( __METHOD__ . '(): Syncing contact to ActiveCampaign: ' . print_r( $contact_data, true ) );

        $result = $api->create_or_update_contact( $contact_data );

        if ( is_wp_error( $result ) ) {
            $this->log_error( __METHOD__ . '(): Failed to create/update contact. Error: ' . $result->get_error_message() );
            return $entry;
        }

        if ( empty( $result['contact']['id'] ) ) {
            $this->log_error( __METHOD__ . '(): Contact sync response did not contain a contact ID.' );
            return $entry;
        }

        $contact_id = $result['contact']['id'];
        $this->log_debug( __METHOD__ . '(): Contact synced successfully. Contact ID: ' . $contact_id );

        $list_result = $api->add_contact_to_list( $contact_id, $list_id );
        if ( is_wp_error( $list_result ) ) {
            $this->log_error( __METHOD__ . '(): Failed to add contact to list. Error: ' . $list_result->get_error_message() );
        } else {
            $this->log_debug( __METHOD__ . '(): Contact added to list ID: ' . $list_id );
        }

        foreach ( $field_map as $ac_field => $form_field_id ) {
            if ( in_array( $ac_field, array( 'email', 'first_name', 'last_name', 'phone' ), true ) ) {
                continue;
            }

            if ( empty( $form_field_id ) || 0 !== strpos( $ac_field, 'custom_' ) ) {
                continue;
            }

            $value = $this->get_field_value( $form, $entry, $form_field_id );
            if ( '' === $value ) {
                continue;
            }

            $real_field_id = str_replace( 'custom_', '', $ac_field );
            $field_result  = $api->update_custom_field_value( $contact_id, $real_field_id, $value );
            if ( is_wp_error( $field_result ) ) {
                $this->log_error( __METHOD__ . '(): Failed to set custom field ' . $real_field_id . ': ' . $field_result->get_error_message() );
            }
        }

        $tags = rgars( $feed, 'meta/ac_tags' );
        if ( ! empty( $tags ) && is_array( $tags ) ) {
            foreach ( $tags as $tag_id ) {
                if ( empty( $tag_id ) ) {
                    continue;
                }
                $tag_result = $api->add_tag_to_contact( $contact_id, $tag_id );
                if ( is_wp_error( $tag_result ) ) {
                    $this->log_error( __METHOD__ . '(): Failed to add tag ' . $tag_id . ' to contact. Error: ' . $tag_result->get_error_message() );
                } else {
                    $this->log_debug( __METHOD__ . '(): Tag ' . $tag_id . ' added to contact.' );
                }
            }
        }

        $this->log_debug( __METHOD__ . '(): Feed processing complete for email: ' . $email );

        return $entry;
    }

    public function get_api() {
        if ( null !== $this->_api ) {
            return $this->_api;
        }

        $api_url = $this->get_plugin_setting( 'api_url' );
        $api_key = $this->get_plugin_setting( 'api_key' );

        if ( empty( $api_url ) || empty( $api_key ) ) {
            $this->log_debug( __METHOD__ . '(): API credentials not configured.' );
            return null;
        }

        $this->_api = new KDNA_ActiveCampaign_API( $api_url, $api_key );
        return $this->_api;
    }

    public function can_create_feed() {
        $api_url = $this->get_plugin_setting( 'api_url' );
        $api_key = $this->get_plugin_setting( 'api_key' );

        return ! empty( $api_url ) && ! empty( $api_key );
    }

    public function configure_addon_message() {
        $settings_url = admin_url( 'admin.php?page=kdna_settings&subview=' . $this->_slug );
        return sprintf(
            esc_html__( 'To get started, please configure your %sActiveCampaign settings%s.', 'kdna-forms-activecampaign' ),
            '<a href="' . esc_url( $settings_url ) . '">',
            '</a>'
        );
    }

    public function can_duplicate_feed( $feed_id ) {
        return true;
    }
}
