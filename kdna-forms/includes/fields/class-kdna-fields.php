<?php

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-field.php' );

class KDNA_Fields {

	public static $deprecation_notice_fired = false;

	/* @var KDNA_Field[] */
	private static $_fields = array();

	public static function register( $field ) {
		if ( ! is_subclass_of( $field, 'KDNA_Field' ) ) {
			throw new Exception( 'Must be a subclass of KDNA_Field' );
		}
		if ( empty( $field->type ) ) {
			throw new Exception( 'The type must be set' );
		}
		if ( isset( self::$_fields[ $field->type ] ) ) {
			throw new Exception( 'Field type already registered: ' . esc_html( $field->type ) );
		}
		self::$_fields[ $field->type ] = $field;
	}

	public static function exists( $field_type ) {
		return isset( self::$_fields[ $field_type ] );
	}

	/**
	 * @param $field_type
	 *
	 * @return KDNA_Field
	 */
	public static function get_instance( $field_type ) {
		return isset( self::$_fields[ $field_type ] ) ? self::$_fields[ $field_type ] : false;
	}

	/**
	 * Alias for get_instance()
	 *
	 * @param $field_type
	 *
	 * @return KDNA_Field
	 */
	public static function get( $field_type ) {
		return self::get_instance( $field_type );
	}

	/**
	 * Return all the registered field types.
	 *
	 * @return KDNA_Field[]
	 */
	public static function get_all() {
		return self::$_fields;
	}

	/**
	 * Creates a Field object from an array of field properties.
	 *
	 * @param array|KDNA_Field $properties
	 *
	 * @return KDNA_Field | bool
	 */
	public static function create( $properties ) {
		if ( $properties instanceof KDNA_Field ) {
			$type = $properties->type;
			$type = empty( $properties->inputType ) ? $type : $properties->inputType;
		} else {
			$type = isset( $properties['type'] ) ? $properties['type'] : '';
			$type = empty( $properties['inputType'] ) ? $type : $properties['inputType'];
		}

		if ( empty( $type ) || ! isset( self::$_fields[ $type ] ) ) {
			return new KDNA_Field( $properties );
		}
		$class      = self::$_fields[ $type ];
		$class_name = get_class( $class );
		$field      = new $class_name( $properties );

		/**
		 * Filter the KDNA_Field object after it is created.
		 *
		 * @since  1.9.18.2
		 *
		 * @param  KDNA_Field $field      A KDNA_Field object.
		 * @param  array    $properties An array of field properties used to generate the KDNA_Field object.
		 *
		 * @see    https://docs.kdnaforms.com/kdnaform_gf_field_create/
		 */
		return apply_filters( 'kdnaform_gf_field_create', $field, $properties );

	}
}

// Load all the field files automatically.
KDNACommon::glob_require_once( '/includes/fields/class-kdna-field-*.php' );
