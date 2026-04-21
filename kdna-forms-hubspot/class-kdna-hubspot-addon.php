<?php
/**
 * KDNA Forms HubSpot Add-On main class.
 *
 * @package KDNA_Forms_HubSpot
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNA_HubSpot_AddOn extends KDNAFeedAddOn {

    protected $_version = '1.0.0';

    protected $_min_kdnaforms_version = '2.9.30';

    protected $_slug = 'kdna-forms-hubspot';

    protected $_path = 'kdna-forms-hubspot/kdna-forms-hubspot.php';

    protected $_full_path = __FILE__;

    protected $_title = 'KDNA Forms HubSpot';

    protected $_short_title = 'HubSpot';

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
                'option_label' => esc_html__( 'Send to HubSpot only when payment is received.', 'kdna-forms-hubspot' ),
            )
        );
    }

    public function plugin_settings_fields() {
        return array(
            array(
                'title'       => esc_html__( 'HubSpot API Settings', 'kdna-forms-hubspot' ),
                'description' => esc_html__( 'Connect your HubSpot account by entering your Private App Access Token below.', 'kdna-forms-hubspot' ),
                'fields'      => array(
                    array(
                        'name'              => 'access_token',
                        'label'             => esc_html__( 'Private App Access Token', 'kdna-forms-hubspot' ),
                        'type'              => 'text',
                        'class'             => 'medium',
                        'tooltip'           => esc_html__( 'Enter your HubSpot Private App Access Token. You can create one in your HubSpot account under Settings > Integrations > Private Apps.', 'kdna-forms-hubspot' ),
                        'feedback_callback' => array( $this, 'validate_access_token' ),
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

    public function validate_access_token( $value ) {
        if ( empty( $value ) ) {
            return false;
        }

        $api = new KDNA_HubSpot_API( $value );
        return $api->validate();
    }

    public function render_api_status( $field ) {
        $access_token = $this->get_plugin_setting( 'access_token' );

        if ( empty( $access_token ) ) {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Not connected. Please enter your Private App Access Token.', 'kdna-forms-hubspot' );
            echo '</div>';
            return;
        }

        $api   = new KDNA_HubSpot_API( $access_token );
        $valid = $api->validate();

        if ( $valid ) {
            echo '<div class="alert_green" style="padding: 10px;">';
            echo esc_html__( 'Connected to HubSpot successfully.', 'kdna-forms-hubspot' );
            echo '</div>';
        } else {
            echo '<div class="alert_red" style="padding: 10px;">';
            echo esc_html__( 'Unable to connect to HubSpot. Please check your Access Token.', 'kdna-forms-hubspot' );
            echo '</div>';
        }
    }

    public function feed_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'HubSpot Feed Settings', 'kdna-forms-hubspot' ),
                'fields' => array(
                    array(
                        'name'     => 'feed_name',
                        'label'    => esc_html__( 'Feed Name', 'kdna-forms-hubspot' ),
                        'type'     => 'text',
                        'required' => true,
                        'class'    => 'medium',
                        'tooltip'  => esc_html__( 'Enter a name for this feed to identify it.', 'kdna-forms-hubspot' ),
                    ),
                    array(
                        'name'     => 'contact_list',
                        'label'    => esc_html__( 'Contact List', 'kdna-forms-hubspot' ),
                        'type'     => 'select',
                        'required' => false,
                        'choices'  => $this->get_hubspot_lists(),
                        'tooltip'  => esc_html__( 'Optionally select a HubSpot list to add the contact to.', 'kdna-forms-hubspot' ),
                    ),
                    array(
                        'name'      => 'field_map',
                        'label'     => esc_html__( 'Map Fields', 'kdna-forms-hubspot' ),
                        'type'      => 'field_map',
                        'field_map' => $this->get_field_map_fields_config(),
                        'tooltip'   => esc_html__( 'Map your form fields to the corresponding HubSpot contact properties.', 'kdna-forms-hubspot' ),
                    ),
                    array(
                        'name'     => 'lifecycle_stage',
                        'label'    => esc_html__( 'Lifecycle Stage', 'kdna-forms-hubspot' ),
                        'type'     => 'select',
                        'required' => false,
                        'choices'  => array(
                            array(
                                'label' => esc_html__( '-- Select a Stage --', 'kdna-forms-hubspot' ),
                                'value' => '',
                            ),
                            array(
                                'label' => esc_html__( 'Subscriber', 'kdna-forms-hubspot' ),
                                'value' => 'subscriber',
                            ),
                            array(
                                'label' => esc_html__( 'Lead', 'kdna-forms-hubspot' ),
                                'value' => 'lead',
                            ),
                            array(
                                'label' => esc_html__( 'Marketing Qualified Lead', 'kdna-forms-hubspot' ),
                                'value' => 'marketingqualifiedlead',
                            ),
                            array(
                                'label' => esc_html__( 'Sales Qualified Lead', 'kdna-forms-hubspot' ),
                                'value' => 'salesqualifiedlead',
                            ),
                            array(
                                'label' => esc_html__( 'Opportunity', 'kdna-forms-hubspot' ),
                                'value' => 'opportunity',
                            ),
                            array(
                                'label' => esc_html__( 'Customer', 'kdna-forms-hubspot' ),
                                'value' => 'customer',
                            ),
                            array(
                                'label' => esc_html__( 'Evangelist', 'kdna-forms-hubspot' ),
                                'value' => 'evangelist',
                            ),
                            array(
                                'label' => esc_html__( 'Other', 'kdna-forms-hubspot' ),
                                'value' => 'other',
                            ),
                        ),
                        'tooltip'  => esc_html__( 'Set the lifecycle stage for the contact in HubSpot.', 'kdna-forms-hubspot' ),
                    ),
                    array(
                        'name'    => 'feed_condition',
                        'label'   => esc_html__( 'Conditional Logic', 'kdna-forms-hubspot' ),
                        'type'    => 'feed_condition',
                        'tooltip' => esc_html__( 'When enabled, the contact will only be sent to HubSpot when the specified conditions are met.', 'kdna-forms-hubspot' ),
                    ),
                ),
            ),
        );
    }

    public function feed_list_columns() {
        return array(
            'feed_name'    => esc_html__( 'Name', 'kdna-forms-hubspot' ),
            'contact_list' => esc_html__( 'HubSpot List', 'kdna-forms-hubspot' ),
        );
    }

    public function get_column_value_contact_list( $feed ) {
        $list_id = rgars( $feed, 'meta/contact_list' );

        if ( empty( $list_id ) ) {
            return esc_html__( 'None', 'kdna-forms-hubspot' );
        }

        $api = $this->get_api();
        if ( null === $api ) {
            return $list_id;
        }

        $lists = $api->get_contact_lists();
        if ( is_wp_error( $lists ) || ! isset( $lists['lists'] ) ) {
            return $list_id;
        }

        foreach ( $lists['lists'] as $list ) {
            if ( (string) $list['listId'] === (string) $list_id ) {
                return esc_html( $list['name'] );
            }
        }

        return $list_id;
    }

    public function get_hubspot_lists() {
        $choices = array(
            array(
                'label' => esc_html__( '-- No List (Create Contact Only) --', 'kdna-forms-hubspot' ),
                'value' => '',
            ),
        );

        $api = $this->get_api();
        if ( null === $api ) {
            return $choices;
        }

        $lists = $api->get_contact_lists();
        if ( is_wp_error( $lists ) || ! isset( $lists['lists'] ) ) {
            $this->log_error( __METHOD__ . '(): Unable to retrieve lists from HubSpot.' );
            return $choices;
        }

        foreach ( $lists['lists'] as $list ) {
            $choices[] = array(
                'label' => esc_html( $list['name'] ),
                'value' => $list['listId'],
            );
        }

        return $choices;
    }

    private function get_field_map_fields_config() {
        $fields = array(
            array(
                'name'       => 'email',
                'label'      => esc_html__( 'Email Address', 'kdna-forms-hubspot' ),
                'required'   => true,
                'field_type' => array( 'email', 'hidden' ),
            ),
            array(
                'name'     => 'firstname',
                'label'    => esc_html__( 'First Name', 'kdna-forms-hubspot' ),
                'required' => false,
            ),
            array(
                'name'     => 'lastname',
                'label'    => esc_html__( 'Last Name', 'kdna-forms-hubspot' ),
                'required' => false,
            ),
            array(
                'name'     => 'phone',
                'label'    => esc_html__( 'Phone', 'kdna-forms-hubspot' ),
                'required' => false,
            ),
            array(
                'name'     => 'company',
                'label'    => esc_html__( 'Company', 'kdna-forms-hubspot' ),
                'required' => false,
            ),
            array(
                'name'     => 'website',
                'label'    => esc_html__( 'Website', 'kdna-forms-hubspot' ),
                'required' => false,
            ),
            array(
                'name'     => 'jobtitle',
                'label'    => esc_html__( 'Job Title', 'kdna-forms-hubspot' ),
                'required' => false,
            ),
        );

        $api = $this->get_api();
        if ( null !== $api ) {
            $properties = $api->get_contact_properties();
            if ( ! is_wp_error( $properties ) && isset( $properties['results'] ) ) {
                $standard_keys = array( 'email', 'firstname', 'lastname', 'phone', 'company', 'website', 'jobtitle' );
                foreach ( $properties['results'] as $property ) {
                    if ( in_array( $property['name'], $standard_keys, true ) ) {
                        continue;
                    }
                    if ( isset( $property['modificationMetadata']['readOnlyValue'] ) && $property['modificationMetadata']['readOnlyValue'] ) {
                        continue;
                    }
                    if ( isset( $property['calculated'] ) && $property['calculated'] ) {
                        continue;
                    }
                    $fields[] = array(
                        'name'     => $property['name'],
                        'label'    => esc_html( $property['label'] ),
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

        $field_map = $this->get_field_map_fields( $feed, 'field_map' );

        $email = $this->get_field_value( $form, $entry, $field_map['email'] );
        if ( empty( $email ) || ! is_email( $email ) ) {
            $this->log_error( __METHOD__ . '(): Invalid or empty email address. Aborting.' );
            return $entry;
        }

        $properties = array();
        foreach ( $field_map as $hs_field => $form_field_id ) {
            if ( 'email' === $hs_field ) {
                continue;
            }
            if ( empty( $form_field_id ) ) {
                continue;
            }
            $value = $this->get_field_value( $form, $entry, $form_field_id );
            if ( '' !== $value && null !== $value ) {
                $properties[ $hs_field ] = $value;
            }
        }

        $lifecycle_stage = rgars( $feed, 'meta/lifecycle_stage' );
        if ( ! empty( $lifecycle_stage ) ) {
            $properties['lifecyclestage'] = $lifecycle_stage;
        }

        $this->log_debug( __METHOD__ . '(): Creating/updating HubSpot contact: ' . $email );

        $result = $api->create_or_update_contact( $email, $properties );

        if ( is_wp_error( $result ) ) {
            $this->log_error( __METHOD__ . '(): Failed to create/update contact in HubSpot. Error: ' . $result->get_error_message() );
            return $entry;
        }

        $contact_id = isset( $result['id'] ) ? $result['id'] : null;

        $this->log_debug( __METHOD__ . '(): Contact successfully created/updated in HubSpot. Email: ' . $email . ', ID: ' . $contact_id );

        $list_id = rgars( $feed, 'meta/contact_list' );
        if ( ! empty( $list_id ) && ! empty( $contact_id ) ) {
            $list_result = $api->add_contact_to_list( $contact_id, $list_id );
            if ( is_wp_error( $list_result ) ) {
                $this->log_error( __METHOD__ . '(): Failed to add contact to list. Error: ' . $list_result->get_error_message() );
            } else {
                $this->log_debug( __METHOD__ . '(): Contact added to list ' . $list_id . ' successfully.' );
            }
        }

        return $entry;
    }

    public function get_api() {
        if ( null !== $this->_api ) {
            return $this->_api;
        }

        $access_token = $this->get_plugin_setting( 'access_token' );
        if ( empty( $access_token ) ) {
            $this->log_debug( __METHOD__ . '(): Access token not configured.' );
            return null;
        }

        $this->_api = new KDNA_HubSpot_API( $access_token );
        return $this->_api;
    }

    public function can_create_feed() {
        $access_token = $this->get_plugin_setting( 'access_token' );
        return ! empty( $access_token );
    }

    public function configure_addon_message() {
        $settings_url = admin_url( 'admin.php?page=kdna_settings&subview=' . $this->_slug );
        return sprintf(
            esc_html__( 'To get started, please configure your %sHubSpot settings%s.', 'kdna-forms-hubspot' ),
            '<a href="' . esc_url( $settings_url ) . '">',
            '</a>'
        );
    }

    public function can_duplicate_feed( $feed_id ) {
        return true;
    }
}
