<?php
/**
 * KDNA Forms reCAPTCHA Integration
 *
 * @package KDNA_Forms
 * @since 1.0.0
 */

namespace KDNA_Forms\KDNA_Forms_RECAPTCHA;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class KDNA_Recaptcha extends \KDNAAddOn {

	protected $_version = '1.0.0';
	protected $_min_kdnaforms_version = '1.0.0';
	protected $_slug = 'kdnaformsrecaptcha';
	protected $_path = 'kdna-forms/includes/recaptcha/class-kdna-recaptcha.php';
	protected $_full_path = __FILE__;
	protected $_title = 'KDNA Forms reCAPTCHA';
	protected $_short_title = 'reCAPTCHA';
	protected $_capabilities_settings_page = 'kdnaform_full_access';
	protected $_capabilities_form_settings = 'kdnaform_full_access';

	private static $_instance = null;

	/** @var Token_Verifier */
	private $token_verifier;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function pre_init() {
		parent::pre_init();
		add_filter( 'kdnaform_form_tag', array( $this, 'add_recaptcha_input' ), 50, 2 );
		add_filter( 'kdnaform_entry_is_spam', array( $this, 'check_for_spam' ), 10, 3 );
	}

	public function init() {
		parent::init();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_recaptcha_script' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_recaptcha_script' ) );
		add_filter( 'kdnaform_validation', array( $this, 'validate_recaptcha' ), 19 );
		add_filter( 'kdnaform_entry_meta', array( $this, 'entry_meta' ), 10, 2 );
		add_filter( 'kdnaform_entry_detail_meta_boxes', array( $this, 'register_meta_box' ), 10, 3 );
	}

	public function plugin_settings_fields() {
		$fields = array(
			array(
				'title'  => esc_html__( 'reCAPTCHA Settings', 'kdnaforms' ),
				'fields' => array(
					array(
						'name'    => 'connection_type',
						'label'   => esc_html__( 'Connection Type', 'kdnaforms' ),
						'type'    => 'select',
						'choices' => array(
							array( 'label' => esc_html__( 'reCAPTCHA v3 (Recommended)', 'kdnaforms' ), 'value' => 'classic' ),
							array( 'label' => esc_html__( 'reCAPTCHA v3 Enterprise', 'kdnaforms' ), 'value' => 'enterprise' ),
							array( 'label' => esc_html__( 'reCAPTCHA v2', 'kdnaforms' ), 'value' => 'v2' ),
						),
						'default_value' => 'classic',
					),
					array(
						'name'  => 'site_key_v3',
						'label' => esc_html__( 'Site Key', 'kdnaforms' ),
						'type'  => 'text',
						'class' => 'medium',
					),
					array(
						'name'  => 'secret_key_v3',
						'label' => esc_html__( 'Secret Key', 'kdnaforms' ),
						'type'  => 'text',
						'class' => 'medium',
					),
					array(
						'name'          => 'score_threshold_v3',
						'label'         => esc_html__( 'Spam Score Threshold', 'kdnaforms' ),
						'type'          => 'text',
						'default_value' => '0.5',
						'class'         => 'small',
						'description'   => esc_html__( 'Submissions scoring at or below this threshold will be marked as spam. Value between 0.0 and 1.0.', 'kdnaforms' ),
					),
					array(
						'name'    => 'disable_badge_v3',
						'label'   => esc_html__( 'Hide reCAPTCHA Badge', 'kdnaforms' ),
						'type'    => 'checkbox',
						'choices' => array(
							array(
								'label' => esc_html__( 'Hide the reCAPTCHA badge on the frontend', 'kdnaforms' ),
								'name'  => 'disable_badge_v3',
							),
						),
					),
				),
			),
		);

		return $fields;
	}

	public function form_settings_fields( $form ) {
		return array(
			array(
				'title'  => esc_html__( 'reCAPTCHA Settings', 'kdnaforms' ),
				'fields' => array(
					array(
						'name'    => 'disable-recaptchav3',
						'label'   => esc_html__( 'Disable reCAPTCHA', 'kdnaforms' ),
						'type'    => 'checkbox',
						'choices' => array(
							array(
								'label' => esc_html__( 'Disable reCAPTCHA for this form', 'kdnaforms' ),
								'name'  => 'disable-recaptchav3',
							),
						),
					),
				),
			),
		);
	}

	public function get_connection_type() {
		$settings = $this->get_plugin_settings();
		return rgar( $settings, 'connection_type', 'classic' );
	}

	public function get_site_key() {
		if ( defined( 'KDNA_RECAPTCHA_V3_SITE_KEY' ) ) {
			return KDNA_RECAPTCHA_V3_SITE_KEY;
		}
		$settings = $this->get_plugin_settings();
		$connection_type = $this->get_connection_type();
		if ( $connection_type === 'enterprise' ) {
			return rgar( $settings, 'site_key_v3_enterprise', '' );
		}
		return rgar( $settings, 'site_key_v3', '' );
	}

	public function get_secret_key() {
		if ( defined( 'KDNA_RECAPTCHA_V3_SECRET_KEY' ) ) {
			return KDNA_RECAPTCHA_V3_SECRET_KEY;
		}
		$settings = $this->get_plugin_settings();
		return rgar( $settings, 'secret_key_v3', '' );
	}

	public function get_score_threshold() {
		$settings = $this->get_plugin_settings();
		$threshold = rgar( $settings, 'score_threshold_v3', '0.5' );
		$threshold = floatval( $threshold );
		if ( $threshold < 0 || $threshold > 1 ) {
			$threshold = 0.5;
		}
		return $threshold;
	}

	public function is_recaptcha_configured() {
		$site_key = $this->get_site_key();
		$connection_type = $this->get_connection_type();

		if ( empty( $site_key ) ) {
			return false;
		}

		if ( $connection_type !== 'enterprise' ) {
			$secret_key = $this->get_secret_key();
			if ( empty( $secret_key ) ) {
				return false;
			}
		}

		return true;
	}

	public function is_recaptcha_enabled_for_form( $form ) {
		if ( ! $this->is_recaptcha_configured() ) {
			return false;
		}
		if ( rgar( $form, 'disable-recaptchav3' ) ) {
			return false;
		}
		return true;
	}

	public function enqueue_recaptcha_script() {
		if ( ! $this->is_recaptcha_configured() ) {
			return;
		}

		$site_key = $this->get_site_key();
		$connection_type = $this->get_connection_type();

		if ( $connection_type === 'v2' ) {
			wp_enqueue_script( 'kdna-recaptcha-v2', 'https://www.google.com/recaptcha/api.js', array(), null, true );
		} elseif ( $connection_type === 'enterprise' ) {
			wp_enqueue_script( 'kdna-recaptcha-enterprise', 'https://www.google.com/recaptcha/enterprise.js?render=' . esc_attr( $site_key ), array(), null, true );
		} else {
			wp_enqueue_script( 'kdna-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $site_key ), array(), null, true );
		}

		wp_enqueue_script( 'kdna-recaptcha-frontend', plugin_dir_url( __FILE__ ) . 'js/frontend.js', array( 'jquery' ), $this->_version, true );
		wp_localize_script( 'kdna-recaptcha-frontend', 'kdna_recaptcha_strings', array(
			'site_key'        => $site_key,
			'connection_type' => $connection_type,
		) );
	}

	public function add_recaptcha_input( $form_tag, $form ) {
		if ( ! $this->is_recaptcha_enabled_for_form( $form ) ) {
			return $form_tag;
		}

		$connection_type = $this->get_connection_type();

		if ( $connection_type === 'v2' ) {
			return $form_tag;
		}

		$site_key  = $this->get_site_key();
		$input_name = 'input_' . md5( 'recaptchav3' . $this->_version . rgar( $form, 'id' ) );

		$recaptcha_input = sprintf(
			'<div class="kdna_invisible kdnainput_recaptchav3" data-sitekey="%s"><input type="hidden" name="%s" class="kdnafield_recaptcha_response" value=""/></div>',
			esc_attr( $site_key ),
			esc_attr( $input_name )
		);

		return $form_tag . $recaptcha_input;
	}

	public function validate_recaptcha( $validation_result ) {
		$form = $validation_result['form'];

		if ( ! $this->is_recaptcha_enabled_for_form( $form ) ) {
			return $validation_result;
		}

		$connection_type = $this->get_connection_type();
		if ( $connection_type === 'v2' ) {
			return $validation_result;
		}

		$input_name = 'input_' . md5( 'recaptchav3' . $this->_version . rgar( $form, 'id' ) );
		$token = rgpost( $input_name );

		if ( empty( $token ) ) {
			$this->log_debug( __METHOD__ . '(): No reCAPTCHA token found.' );
			return $validation_result;
		}

		$verifier = $this->get_token_verifier();
		$result = $verifier->verify_submission( $token );

		if ( is_wp_error( $result ) ) {
			$this->log_error( __METHOD__ . '(): reCAPTCHA verification error: ' . $result->get_error_message() );
			return $validation_result;
		}

		$score = rgar( $result, 'score', 0 );
		$threshold = $this->get_score_threshold();

		$validation_result['form']['kdna_recaptcha_score'] = $score;

		if ( is_numeric( $score ) && $score <= $threshold ) {
			$this->log_debug( __METHOD__ . sprintf( '(): Score %s is at or below threshold %s. Marking as spam.', $score, $threshold ) );
			$validation_result['is_spam'] = true;
		}

		return $validation_result;
	}

	public function check_for_spam( $is_spam, $form, $entry ) {
		return $is_spam;
	}

	public function entry_meta( $entry_meta, $form_id ) {
		$entry_meta['kdnaformsrecaptcha_score'] = array(
			'label'             => esc_html__( 'reCAPTCHA Score', 'kdnaforms' ),
			'is_numeric'        => true,
			'is_default_column' => false,
		);
		return $entry_meta;
	}

	public function register_meta_box( $meta_boxes, $entry, $form ) {
		if ( ! $this->is_recaptcha_enabled_for_form( $form ) ) {
			return $meta_boxes;
		}

		$meta_boxes['kdna_recaptcha'] = array(
			'title'    => esc_html__( 'reCAPTCHA', 'kdnaforms' ),
			'callback' => array( $this, 'meta_box_recaptcha' ),
			'context'  => 'side',
		);

		return $meta_boxes;
	}

	public function meta_box_recaptcha( $args ) {
		$entry = $args['entry'];
		$score = kdna_get_meta( rgar( $entry, 'id' ), 'kdnaformsrecaptcha_score' );
		if ( $score !== false && $score !== '' ) {
			echo '<p><strong>' . esc_html__( 'Score:', 'kdnaforms' ) . '</strong> ' . esc_html( $score ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'No reCAPTCHA data available.', 'kdnaforms' ) . '</p>';
		}
	}

	private function get_token_verifier() {
		if ( ! $this->token_verifier ) {
			require_once dirname( __FILE__ ) . '/class-token-verifier.php';
			$this->token_verifier = new Token_Verifier( $this );
		}
		return $this->token_verifier;
	}
}
