<?php

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

class KDNAExport {

	private static $min_import_version = '1.3.12.3';

	/**
	 * Process the forms export request.
	 */
	public static function maybe_export() {
		if ( isset( $_POST['export_forms'] ) ) {
			check_admin_referer( 'gf_export_forms', 'gf_export_forms_nonce' );
			$selected_forms = rgpost( 'kdna_form_id' );
			if ( empty( $selected_forms ) ) {
				KDNACommon::add_error_message( __( 'Please select the forms to be exported', 'kdnaforms' ) );

				return;
			}

			self::export_forms( $selected_forms );
		}
	}

	public static function export_forms( $form_ids ) {

		$forms = KDNAFormsModel::get_form_meta_by_id( $form_ids );
		$forms = self::prepare_forms_for_export( $forms );

		$forms['version'] = KDNAForms::$version;
		$forms_json       = json_encode( $forms );

		/**
		 * Allows the form export filename to be changed.
		 *
		 * @since 2.3.4
		 *
		 * @param string   $filename	The new filename to use for the export file.
		 * @param array    $form_ids    Array containing the IDs of forms selected for export.
		 */
		$filename = apply_filters( 'kdnaform_form_export_filename', 'kdnaforms-export-' . date( 'Y-m-d' ), $form_ids ) . '.json';
		$filename = sanitize_file_name( $filename );
		header( 'Content-Description: File Transfer' );
		header( "Content-Disposition: attachment; filename=$filename" );
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );

		$buffer_length = ob_get_length(); // length or false if no buffer.
		if ( $buffer_length > 1 ) {
			ob_clean();
		}

		echo $forms_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		die();
	}

	public static function export_page() {

		if ( ! KDNACommon::ensure_wp_version() ) {
			return;
		}

		$view = rgget( 'subview' ) ? rgget( 'subview' ) : 'export_entry';

		switch ( $view ) {

			case 'export_entry':
				self::export_lead_page();
				break;

			case 'import_form' :
				self::import_form_page();
				break;

			case 'export_form' :
				self::export_form_page();
				break;

			default:
				/**
				 * Fires when export pages are gathered
				 *
				 * Used to add additional export settings pages
				 *
				 * @param string $view Set when defining the action string.  Creates the name for the new page
				 */
				do_action( "kdnaform_export_page_{$view}" );
				break;

		}

	}

	public static function import_file( $filepath, &$forms = null ) {
		$file_contents = file_get_contents( $filepath );

		if ( KDNACommon::safe_substr( $file_contents, 0, 38 ) == '<?xml version="1.0" encoding="UTF-8"?>' ) {
			return self::import_xml( $file_contents, $forms );
		}

		return self::import_json( $file_contents, $forms );
	}

	public static function import_json( $forms_json, &$forms = null ) {

		$forms_json = self::sanitize_forms_json( $forms_json );

		$forms = json_decode( $forms_json, true );

		if ( ! $forms ) {
			KDNACommon::log_debug( __METHOD__ . '(): Import Failed. Invalid form objects.' );

			return 0;
		} else if ( ! rgar( $forms, 'version' ) || version_compare( $forms['version'], self::$min_import_version, '<' ) ) {
			KDNACommon::log_debug( __METHOD__ . '(): Import Failed. The JSON version is not compatible with the current KDNA Forms version.' );

			return - 1;
		} //Error. JSON version is not compatible with current KDNA Forms version

		KDNACache::delete( 'legacy_is_in_use' );

		unset( $forms['version'] );

		$clean_forms = array();

		foreach ( $forms as $form ) {
			$form['markupVersion'] = 2;
			$form                  = KDNAFormsModel::convert_field_objects( $form );
			$clean_forms[]         = KDNAFormsModel::sanitize_settings( $form );
		}

		$form_result  = KDNAAPI::add_forms( $clean_forms, true );
		$form_ids     = $form_result['form_ids'];
		$failed_forms = $form_result['failed_forms'];

		if ( is_wp_error( $form_result ) ) {
			KDNACommon::log_debug( __METHOD__ . '(): Import Failed => ' . print_r( $form_ids, 1 ) );
			$form_ids = array();
		} else {
			foreach ( $failed_forms as $failed_form ) {
				if ( $failed_form['error'] instanceof WP_Error ) {
					KDNACommon::log_debug( __METHOD__ . '(): Import Failed => ' . print_r( $failed_form, 1 ) );
				}
			}

			foreach ( $form_ids as $key => $form_id ) {
				$forms[ $key ] = KDNAAPI::get_form( $form_id );
			}

			if ( rgpost( 'gf_import_media' ) ) {
				$forms = self::import_form_media( $form_ids, $forms );
			}
			/**
			 * Fires after forms have been imported.
			 *
			 * Used to perform additional actions after import
			 *
			 * @param array $forms An array imported form objects.
			 *
			 */
			do_action( 'kdnaform_forms_post_import', $forms );
		}

		return array(
			'form_ids'     => $form_ids,
			'failed_forms' => $failed_forms
		);
	}

	/**
	 * If a form includes images, import them into the WordPress media library.
	 *
	 * @since 2.9
	 *
	 * @param $form_ids
	 * @param $forms
	 *
	 * @return mixed
	 */
	public static function import_form_media( $form_ids, $forms ) {
		foreach ( $forms as $form ) {
			$updated_form = self::find_and_replace_media( $form );
			KDNAFormsModel::update_form_meta( $form['id'], $updated_form );
		}

		foreach ( $form_ids as $key => $form_id ) {
			$forms[ $key ] = KDNAAPI::get_form( $form_id );
		}

		return $forms;
	}

	/**
	 * Iterate through the form meta data to find images that need to be imported.
	 *
	 * Any meta data with the key of "file_url" will be imported into the WordPress media library.
	 *
	 * @since 2.9
	 *
	 * @param $form_meta
	 *
	 * @return mixed
	 */
	public static function find_and_replace_media( &$form_meta ) {
		foreach( $form_meta as $key => &$value ) {
			if( is_array( $value ) || is_object( $value ) ) {
				if( rgar( $value, 'file_url' ) ) {
					$new_media = self::import_media( $value['file_url'] );
					if( $new_media ) {
						$value['attachment_id'] = $new_media;
						$value['file_url']      = wp_get_attachment_url( $new_media );
					}
				}
				// Recursively call the function to handle nested arrays
				self::find_and_replace_media( $value );
			}
		}

		return $form_meta;
	}

	/**
	 * Import images into the WordPress media library.
	 *
	 * @since 2.9
	 *
	 * @param $image_url
	 *
	 * @return false|int|WP_Error
	 */
	public static function import_media( $image_url ) {
		KDNACommon::log_debug( __METHOD__ . '(): Importing ' . esc_url( $image_url ) . 'to media library' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );

		// Download to temp directory.
		$temp_file = download_url( $image_url );

		if( is_wp_error( $temp_file ) ) {
			KDNACommon::log_debug( __METHOD__ . '(): Import Failed => ' . print_r( $temp_file, 1 ) );
			return false;
		}

		// Move the temp file into the uploads directory.
		$file = array(
			'name'     => basename( $image_url ),
			'type'     => mime_content_type( $temp_file ),
			'tmp_name' => $temp_file,
			'size'     => filesize( $temp_file ),
		);
		$sideload = wp_handle_sideload(
			$file,
			array(
				'test_form'   => false
			)
		);

		if( ! empty( $sideload[ 'error' ] ) ) {
			KDNACommon::log_debug( __METHOD__ . '(): Import Failed => ' . print_r( $temp_file, 1 ) );
			return false;
		}

		// Add the image to the media library.
		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => $sideload[ 'url' ],
				'post_mime_type' => $sideload[ 'type' ],
				'post_title'     => basename( $sideload[ 'file' ] ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$sideload[ 'file' ]
		);

		if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			KDNACommon::log_debug( __METHOD__ . '(): Unable to add image to media library ' . print_r( $image_url, 1 ) );
			return false;
		}

		// Update metadata, regenerate image sizes.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
		);

		KDNACommon::log_debug( __METHOD__ . '(): Successfully imported ' . esc_url( $image_url ) );

		return $attachment_id;
	}

	/**
	 * Removes any extraneous strings from the beginning of the JSON file to be imported.
	 *
	 * @since 2.5.16
	 *
	 * @param string $forms_json Exported form JSON to be sanitized.
	 *
	 * @return string Sanitized JSON string.
	 */
	public static function sanitize_forms_json( $forms_json ) {

		// Remove any whitespace from before and after the JSON.
		$forms_json = trim( $forms_json );

		// Remove any characters before the beginning of the JSON string.
		if ( preg_match( '/{\s*"\d"\s*:\s*{/', $forms_json, $matches, PREG_OFFSET_CAPTURE ) ) {
			$forms_json = substr( $forms_json, $matches[0][1] );
		}

		return $forms_json;
	}


	// This function is not deprecated as of 1.9 because it will still be needed for a while to import legacy XML files without generating deprecation notices.
	// However, XML is not used to export Forms so this function will soon be deprecated.
	public static function import_xml( $xmlstr, &$forms = null ) {

		require_once( 'xml.php' );

		$options = array(
			'page'         => array( 'unserialize_as_array' => true ),
			'form'         => array( 'unserialize_as_array' => true ),
			'field'        => array( 'unserialize_as_array' => true ),
			'rule'         => array( 'unserialize_as_array' => true ),
			'choice'       => array( 'unserialize_as_array' => true ),
			'input'        => array( 'unserialize_as_array' => true ),
			'routing_item' => array( 'unserialize_as_array' => true ),
			'creditCard'   => array( 'unserialize_as_array' => true ),
			'routin'       => array( 'unserialize_as_array' => true ), //routin is for backwards compatibility
			'confirmation' => array( 'unserialize_as_array' => true ),
			'notification' => array( 'unserialize_as_array' => true ),
		);
		$options = apply_filters( 'kdnaform_import_form_xml_options', $options );
		$xml     = new RGXML( $options );
		$forms   = $xml->unserialize( $xmlstr );

		if ( ! $forms ) {
			return 0;
		} //Error. could not unserialize XML file
		else if ( version_compare( $forms['version'], self::$min_import_version, '<' ) ) {
			return - 1;
		} //Error. XML version is not compatible with current KDNA Forms version

		//cleaning up generated object
		self::cleanup( $forms );

		foreach ( $forms as $key => &$form ) {

			$title = $form['title'];
			$count = 2;
			while ( ! KDNAFormsModel::is_unique_title( $title ) ) {
				$title = $form['title'] . "($count)";
				$count ++;
			}

			//inserting form
			$form_id = KDNAFormsModel::insert_form( $title );

			//updating form meta
			$form['title']         = $title;
			$form['id']            = $form_id;
			$form['markupVersion'] = 2;

			$form = KDNAFormsModel::trim_form_meta_values( $form );

			if ( isset( $form['confirmations'] ) ) {
				$form['confirmations'] = self::set_property_as_key( $form['confirmations'], 'id' );
				$form['confirmations'] = KDNAFormsModel::trim_conditional_logic_values( $form['confirmations'], $form );
				KDNAFormsModel::update_form_meta( $form_id, $form['confirmations'], 'confirmations' );
				unset( $form['confirmations'] );
			}

			if ( isset( $form['notifications'] ) ) {
				$form['notifications'] = self::set_property_as_key( $form['notifications'], 'id' );
				$form['notifications'] = KDNAFormsModel::trim_conditional_logic_values( $form['notifications'], $form );
				KDNAFormsModel::update_form_meta( $form_id, $form['notifications'], 'notifications' );
				unset( $form['notifications'] );
			}

			KDNAFormsModel::update_form_meta( $form_id, $form );

		}

		return sizeof( $forms );
	}

	private static function cleanup( &$forms ) {
		unset( $forms['version'] );

		//adding checkboxes 'inputs' property based on 'choices'. (they were removed from the export
		//to provide a cleaner xml format
		foreach ( $forms as &$form ) {
			if ( ! is_array( $form['fields'] ) ) {
				continue;
			}
			$form = KDNAFormsModel::convert_field_objects( $form );

			foreach ( $form['fields'] as &$field ) {
				$input_type = KDNAFormsModel::get_input_type( $field );

				if ( in_array( $input_type, array( 'checkbox', 'radio', 'select', 'multiselect' ) ) ) {

					//creating inputs array for checkboxes
					if ( $input_type == 'checkbox' && ! isset( $field->inputs ) ) {
						$field->inputs = array();
					}

					$adjust_by = 0;
					for ( $i = 1, $count = sizeof( $field->choices ); $i <= $count; $i ++ ) {

						if ( ! $field->enableChoiceValue ) {
							$field->choices[ $i - 1 ]['value'] = $field->choices[ $i - 1 ]['text'];
						}

						if ( $input_type == 'checkbox' ) {
							if ( ( ( $i + $adjust_by ) % 10 ) == 0 ) {
								$adjust_by ++;
							}

							$id = $i + $adjust_by;

							$field->inputs[] = array( 'id' => $field->id . '.' . $id, 'label' => $field->choices[ $i - 1 ]['text'] );

						}
					}
				}
			}
		}
	}

	public static function set_property_as_key( $array, $property ) {
		$new_array = array();
		foreach ( $array as $item ) {
			$new_array[ $item[ $property ] ] = $item;
		}

		return $new_array;
	}

	/**
	 * Processes the forms import request.
	 *
	 * This method checks if the import forms request is set, verifies the nonce,
	 * and processes the uploaded files for import. It handles errors and success messages
	 * based on the import results.
	 *
	 * @since 2.9.5
	 */
	private static function process_forms_import() {
		if ( isset( $_POST['import_forms'] ) ) {

			check_admin_referer( 'gf_import_forms', 'gf_import_forms_nonce' );

			if ( ! empty( $_FILES['gf_import_file']['tmp_name'][0] ) ) {

				$count       = 0;
				$all_results = []; // Store the results of each import.

				// Loop through each uploaded file.
				foreach ( $_FILES['gf_import_file']['tmp_name'] as $import_file ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$result = self::import_file( $import_file, $forms );
					$count += ( $result === -1 ) ? -1 : ( ( $result === 0 ) ? 0 : count( $result['form_ids'] ) );
					$all_results[] = $result;
				}

				if ( $count == 0 || $result == 0 ) {
					$error_message = sprintf(
						esc_html__( 'Forms could not be imported. Please make sure your files have the .json extension, and that they were generated by the %sKDNA Forms Export form%s tool.', 'kdnaforms' ),
						'<a href="admin.php?page=kdna_export&view=export_form">',
						'</a>'
					);
					KDNACommon::add_error_message( $error_message );
				} else if ( $count == '-1' || $result == '-1' ) {
					KDNACommon::add_error_message( esc_html__( 'Forms could not be imported. Your export file is not compatible with your current version of KDNA Forms.', 'kdnaforms' ) );
				} else {

					self::process_import_results( $all_results, $count );

				}
			}
		}
	}

	/**
	 * Processes the import results and generates appropriate messages.
	 *
	 * This method processes the results of the form import, including the number of forms imported
	 * and any errors that occurred. It generates success or error messages based on the results.
	 *
	 * @since 2.9.5
	 *
	 * @param array $all_results An array of results from the import process.
	 * @param int   $count       The number of forms successfully imported.
	 */
	private static function process_import_results( $all_results, $count ) {

		$total_forms  = 0; // Keep track of the total number of forms imported.
		$forms_ids    = []; // Store the forms ids for the success message.
		$forms_errors = []; //store the failed forms for the success message.

		// Loop through each result and store the form ids and failed forms.
		foreach ( $all_results as $result ) {
			$total_forms += count( $result['form_ids'] ) + count( $result['failed_forms'] );
			$forms_ids    = array_merge( $forms_ids, $result['form_ids'] );
			$forms_errors = array_merge( $forms_errors, $result['failed_forms'] );
		}

		$failed_forms_count = $total_forms - $count;
		$form_ids           = implode( ',', $forms_ids );

		if ( $total_forms > 1 ) {
			if ( $failed_forms_count > 0 ) {
				$failed_form_errors = array_map( function( $failed_form_errors ) {
					return $failed_form_errors['error']->get_error_message();
				}, $forms_errors );

				$form_id = array_map( function( $form_id ) {
					return $form_id['form_id'];
				}, $forms_errors );

				$failed_errors = $failed_form_errors ? sprintf(
					'<span>%s: %s</span>',
					_n( 'Error', 'Errors', count( $failed_form_errors ), 'kdnaforms' ),
					'<ul style="margin: 10px 0;">' . implode( '', array_map( function( $error, $id ) {
						return '<li>ID ' . $id . ': ' . $error . '.</li>';
					}, $failed_form_errors, $form_id ) ) . '</ul>'
				) : '';

				printf(
					'<div class="gf-notice notice notice-error">%s</div>',
					sprintf(
						// translators: 1: number of forms; 2: error details (may contain HTML)
						esc_html(
							_n(
								'Notice: %1$s form failed the import process. %2$s',
								'Notice: %1$s forms failed the import process. %2$s',
								$failed_forms_count,
								'kdnaforms'
							)
						),
						number_format_i18n( (int) $failed_forms_count ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						wp_kses_post( $failed_errors )
					)
				);
			}
			$edit_links = "<a href='admin.php?page=kdna_edit_forms&id={$form_ids}'>" . esc_html__( 'View imported forms.', 'kdnaforms' ) . '</a>';
			KDNACommon::add_message( sprintf( esc_html__( 'KDNA Forms imported %d %s successfully', 'kdnaforms' ), $count, _n( 'form', 'forms', $count, 'kdnaforms' ) ) . ". $edit_links" );
		} else {
			$edit_links = "<a href='admin.php?page=kdna_edit_forms&id={$forms_ids[0]}'>" . esc_html__( 'Edit form.', 'kdnaforms' ) . '</a>';
			KDNACommon::add_message( sprintf(esc_html__( 'KDNA Forms imported %d form successfully', 'kdnaforms' ), $count ) . ". $edit_links" );
		}
	}

	public static function import_form_page() {

		if ( ! KDNACommon::current_user_can_any( 'kdnaforms_edit_forms' ) ) {
			wp_die( 'You do not have permission to access this page' );
		}

		self::process_forms_import();
		self::page_header();
		?>
        <div class="gform-settings__content">
            <form method="post" enctype="multipart/form-data" class="kdnaform_settings_form">
                <?php wp_nonce_field( 'gf_import_forms', 'gf_import_forms_nonce' ); ?>
                <div class="gform-settings-panel gform-settings-panel--full">
                    <header class="gform-settings-panel__header"><legend class="gform-settings-panel__title"><?php esc_html_e('Import Forms', 'kdnaforms'); ?></legend></header>
                    <div class="gform-settings-panel__content">
                        <div class="gform-settings-description">
							<?php
							echo sprintf(
								esc_html__( 'Select the KDNA Forms export files you would like to import. Please make sure your files have the .json extension, and that they were generated by the %sKDNA Forms Export form%s tool. When you click the import button below, KDNA Forms will import the forms.', 'kdnaforms' ),
								'<a href="admin.php?page=kdna_export&view=export_form">',
								'</a>'
							);
							?>
                        </div>
                        <table class="form-table">
                            <tr valign="top">

                                <th scope="row">
                                    <label for="gf_import_file"><?php esc_html_e( 'Select Files', 'kdnaforms' ); ?></label> <?php kdnaform_tooltip( 'import_select_file' ) ?>
                                </th>
                                <td><input type="file" name="gf_import_file[]" id="gf_import_file" multiple /></td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="gf_import_media"><?php esc_html_e( 'Import Images', 'kdnaforms' ); ?></label> <?php kdnaform_tooltip( 'import_media' ) ?>
								</th>
								<td><input type="checkbox" name="gf_import_media" id="gf_import_media" /><?php esc_html_e( 'Import images used in this form into your media library.', 'kdnaforms' ); ?></td>
                            </tr>
                        </table>
                        <br /><br />
                        <input type="submit" value="<?php esc_html_e( 'Import', 'kdnaforms' ) ?>" name="import_forms" class="button large primary" />
                    </div>
                </div>
            </form>
        </div>
		<?php

		self::page_footer();

	}

	public static function export_form_page() {

		if ( ! KDNACommon::current_user_can_any( 'kdnaforms_edit_forms' ) ) {
			wp_die( 'You do not have permission to access this page' );
		}

		self::page_header();
		self::maybe_process_automated_export();
		?>
		<script type="text/javascript">

			( function( $, window, undefined ) {

				$( document ).on( 'click keypress', '#gf_export_forms_all', function( e ) {

					var checked  = e.target.checked,
					    label    = $( 'label[for="gf_export_forms_all"]' ),
					    formList = $( '#export_form_list' );

					// Set label.
					label.find( 'strong' ).html( checked ? label.data( 'deselect' ) : label.data( 'select' ) );

					// Change checkbox status.
					$( 'input[name]', formList ).prop( 'checked', checked );

				} );

			}( jQuery, window ));

		</script>

        <div class="gform-settings__content">
            <form method="post" id="kdnaform_export" class="kdnaform_settings_form">
	            <?php wp_nonce_field( 'gf_export_forms', 'gf_export_forms_nonce' ); ?>
                <div class="gform-settings-panel gform-settings-panel--full">
                    <header class="gform-settings-panel__header"><legend class="gform-settings-panel__title"><?php esc_html_e( 'Export Forms', 'kdnaforms' )?></legend></header>
                    <div class="gform-settings-panel__content">
                        <div class="gform-settings-description">
	                        <?php esc_html_e( 'Select the forms you would like to export. When you click the download button below, KDNA Forms will create a JSON file for you to save to your computer. Once you\'ve saved the download file, you can use the Import tool to import the forms.', 'kdnaforms' ); ?>
                        </div>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <label for="export_fields"><?php esc_html_e( 'Select Forms', 'kdnaforms' ); ?></label> <?php kdnaform_tooltip( 'export_select_forms' ) ?>
                                </th>
                                <td>
                                    <ul id="export_form_list">
                                        <li>
                                            <input type="checkbox" id="gf_export_forms_all" />
                                            <label for="gf_export_forms_all" data-deselect="<?php esc_attr_e( 'Deselect All', 'kdnaforms' ); ?>" data-select="<?php esc_attr_e( 'Select All', 'kdnaforms' ); ?>"><?php esc_html_e( 'Select All', 'kdnaforms' ); ?></label>
                                        </li>
					                    <?php
					                    $forms = KDNAFormsModel::get_forms( null, 'title' );

					                    /**
					                     * Modify list of forms available for export.
					                     *
					                     * @since 2.4.7
					                     *
					                     * @param array $forms Forms to display on Export Forms page.
					                     */
					                    $forms = apply_filters( 'kdnaform_export_forms_forms', $forms );

					                    foreach ( $forms as $form ) {
						                    ?>
                                            <li>
                                                <input type="checkbox" name="kdna_form_id[]" id="kdna_form_id_<?php echo absint( $form->id ) ?>" value="<?php echo absint( $form->id ) ?>" />
                                                <label for="kdna_form_id_<?php echo absint( $form->id ) ?>"><?php echo esc_html( $form->title ) ?></label>
                                            </li>
						                    <?php
					                    }
					                    ?>
                                    </ul>
                                </td>
                            </tr>
                        </table>

                        <br /><br />
						<input type="hidden" name="kdnaform_automatic_submit" id="kdnaform_automatic_submit" value="false" />
                        <input type="submit" value="<?php esc_attr_e( 'Download Export File', 'kdnaforms' ) ?>" name="export_forms" class="button large primary" />
                    </div>
                </div>
            </form>
        </div>
		<?php

		self::page_footer();

	}

	/**
	 * Checks if form ids are provided in query to be automatically exported.
	 *
	 * This method checks the checkboxes of the desired forms and simulates a click on the submit button.
	 *
	 * @since 2.6.2
	 *
	 * @return void
	 */
	public static function maybe_process_automated_export() {
		$export_ids       = rgget( 'export_form_ids' );
		$automatic_submit = rgpost( 'kdnaform_automatic_submit' );
		if ( $export_ids && ! $automatic_submit ) {
			?>
			<script>
				jQuery( document ).ready( function () {
					var export_ids = <?php echo json_encode( $export_ids ); // nosemgrep scanner.php.lang.security.xss.direct-reflected ?>;
					var clickSubmit = false;
					export_ids.split(',').forEach( ( id ) => {
						var formCheckbox = jQuery( '#kdna_form_id_' + id );
						if( formCheckbox.length ) {
							formCheckbox.prop( 'checked', true );
							clickSubmit = true;
						}
					});

					if ( clickSubmit ) {
						jQuery( '#kdnaform_automatic_submit' ).val( true );
						jQuery( '#kdnaform_export input[type="submit"]' ).click();
					}
				})
			</script>
			<?php
		}
	}

	public static function export_lead_page() {

		if ( ! KDNACommon::current_user_can_any( 'kdnaforms_export_entries' ) ) {
			wp_die( 'You do not have permission to access this page' );
		}

		self::page_header();

		?>

		<script type="text/javascript">

			var gfSpinner;

			<?php KDNACommon::gf_global(); ?>
			<?php KDNACommon::gf_vars(); ?>

			function SelectExportForm(formId) {

				if (!formId)
					return;

				gform.utils.trigger( { event: 'gform/page_loader/show' } );

				var mysack = new sack("<?php echo esc_js( esc_url_raw( admin_url( 'admin-ajax.php' ) ) )?>");
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar("action", "rg_select_export_form");
				mysack.setVar("rg_select_export_form", "<?php echo esc_js( wp_create_nonce( 'rg_select_export_form' ) ); ?>");
				mysack.setVar("form_id", formId);
				mysack.onError = function () {
					alert(<?php echo json_encode( __( 'Ajax error while selecting a form', 'kdnaforms' ) ); ?>)
				};
				mysack.runAJAX();

				return true;
			}

			function EndSelectExportForm(aryFields, filterSettings) {
				gform.utils.trigger( { event: 'gform/page_loader/hide' } );

				if (aryFields.length == 0) {
					jQuery("#export_field_container, #export_date_container, #export_submit_container").hide()
					return;
				}

				var fieldList = "<li><input id='select_all' type='checkbox' onclick=\"jQuery('.kdnaform_export_field').prop('checked', this.checked); jQuery('#kdnaform_export_check_all').html(this.checked ? '<strong><?php echo esc_js( __( 'Deselect All', 'kdnaforms' ) ); ?></strong>' : '<strong><?php echo esc_js( __( 'Select All', 'kdnaforms' ) ); ?></strong>'); \" onkeypress=\"jQuery('.kdnaform_export_field').prop('checked', this.checked); jQuery('#kdnaform_export_check_all').html(this.checked ? '<strong><?php echo esc_js( __( 'Deselect All', 'kdnaforms' ) ); ?></strong>' : '<strong><?php echo esc_js( __( 'Select All', 'kdnaforms' ) ); ?></strong>'); \"> <label id='kdnaform_export_check_all' for='select_all'><strong><?php esc_html_e( 'Select All', 'kdnaforms' ) ?></strong></label></li>";
				for (var i = 0; i < aryFields.length; i++) {
					fieldList += "<li><input type='checkbox' id='export_field_" + i + "' name='export_field[]' value='" + aryFields[i][0] + "' class='kdnaform_export_field'> <label for='export_field_" + i + "'>" + aryFields[i][1] + "</label></li>";
				}
				jQuery("#export_field_list").html(fieldList);
				jQuery("#export_date_start, #export_date_end").datepicker({dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true});

				jQuery("#export_field_container, #export_filter_container, #export_date_container, #export_submit_container").hide().show();

				gf_vars.filterAndAny = <?php echo json_encode( esc_html__( 'Export entries if {0} of the following match:', 'kdnaforms' ) ); ?>;
				jQuery("#export_filters").gfFilterUI(filterSettings);
			}

			( function( $, window, undefined ) {

				$(document).ready(function() {
					$("#submit_button").click(function () {
						if ($(".kdnaform_export_field:checked").length == 0) {
							alert(<?php echo json_encode( __( 'Please select the fields to be exported', 'kdnaforms' ) );  ?>);
							return false;
						}

						$(this).hide();
						$('#please_wait_container').show();
						process();

						return false;
					});

					$('#export_form').on('change', function() {
						SelectExportForm($(this).val());
					}).trigger('change');
				});

				function process( offset, exportId ) {

					if ( typeof offset == 'undefined' ) {
						offset = 0;
					}

					if ( typeof exportId == 'undefined' ) {
						exportId = 0;
					}

					var data = $('#kdnaform_export').serialize();

					data += '&action=gf_process_export';
					data += '&offset=' + offset;
					data += '&exportId='+ exportId;
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: data,
						dataType: 'json'
					}).done(function( response ){
							if ( response.status == 'in_progress' ) {
								$('#progress_container').text( response.progress );
								process( response.offset, response.exportId );
							} else if ( response.status == 'complete' ) {
								$('#progress_container').text('0%');
								$('#please_wait_container').hide();
								var formId = parseInt( $('#export_form').val() );
								var url = ajaxurl + '?action=gf_download_export&_wpnonce=<?php echo esc_js( wp_create_nonce( 'kdnaform_download_export' ) ); ?>&export-id=' + response.exportId + '&form-id=' + formId;
								$('#submit_button').fadeIn();
								document.location.href = url;
							}
						}
					);
				}

			}( jQuery, window ));


		</script>

        <div class="gform-settings__content">
            <form method="post" id="kdnaform_export" class="kdnaform_settings_form" data-js="page-loader">
	            <?php echo wp_nonce_field( 'rg_start_export', 'rg_start_export_nonce' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_nonce_field is safe ?>
                <div class="gform-settings-panel gform-settings-panel--full">
                    <header class="gform-settings-panel__header"><legend class="gform-settings-panel__title"><?php esc_html_e( 'Export Entries', 'kdnaforms' ) ;?></legend></header>
                    <div class="gform-settings-panel__content">
                        <div class="gform-settings-description">
	                        <?php esc_html_e( 'Select a form below to export entries. Once you have selected a form you may select the fields you would like to export and then define optional filters for field values and the date range. When you click the download button below, KDNA Forms will create a CSV file for you to save to your computer.', 'kdnaforms' ); ?>
                        </div>
                        <table class="form-table">
                            <tr valign="top">

                                <th scope="row">
                                    <label for="export_form"><?php esc_html_e( 'Select a Form', 'kdnaforms' ); ?></label> <?php kdnaform_tooltip( 'export_select_form' ) ?>
                                </th>
                                <td>

                                    <select id="export_form" name="export_form">
                                        <option value=""><?php esc_html_e( 'Select a form', 'kdnaforms' ); ?></option>
					                    <?php
					                    $forms = KDNAFormsModel::get_forms( null, 'title' );

					                    /**
					                     * Modify list of forms available to export entries from.
					                     *
					                     * @since 2.4.7
					                     *
					                     * @param array $forms Forms to display on Export Entries page.
					                     */
					                    $forms = apply_filters( 'kdnaform_export_entries_forms', $forms );

					                    foreach ( $forms as $form ) {
						                    ?>
                                            <option value="<?php echo absint( $form->id ) ?>" <?php selected( rgget( 'id' ), $form->id ); ?>><?php echo esc_html( $form->title ) ?></option>
						                    <?php
					                    }
					                    ?>
                                    </select>

                                </td>
                            </tr>
                            <tr id="export_field_container" valign="top" style="display: none;">
                                <th scope="row">
                                    <label for="export_fields"><?php esc_html_e( 'Select Fields', 'kdnaforms' ); ?></label> <?php kdnaform_tooltip( 'export_select_fields' ) ?>
                                </th>
                                <td>
                                    <ul id="export_field_list">
                                    </ul>
                                </td>
                            </tr>
                            <tr id="export_filter_container" valign="top" style="display: none;">
                                <th scope="row">
                                    <label><?php esc_html_e( 'Conditional Logic', 'kdnaforms' ); ?></label> <?php kdnaform_tooltip( 'export_conditional_logic' ) ?>
                                </th>
                                <td>
                                    <div id="export_filters" class="gform-settings-field__conditional-logic">
                                        <!--placeholder-->
                                    </div>
                                </td>
                            </tr>
                            <tr id="export_date_container" valign="top" style="display: none;">
                                <th scope="row">
                                    <label for="export_date"><?php esc_html_e( 'Select Date Range', 'kdnaforms' ); ?></label> <?php kdnaform_tooltip( 'export_date_range' ) ?>
                                </th>
                                <td>
                                    <div>
                                <span style="width:150px; float:left; ">
                                    <input type="text" id="export_date_start" name="export_date_start" style="width:90%" />
                                    <strong><label for="export_date_start" style="display:block;"><?php esc_html_e( 'Start', 'kdnaforms' ); ?></label></strong>
                                </span>

                                        <span style="width:150px; float:left;">
                                    <input type="text" id="export_date_end" name="export_date_end" style="width:90%" />
                                    <strong><label for="export_date_end" style="display:block;"><?php esc_html_e( 'End', 'kdnaforms' ); ?></label></strong>
                                </span>

                                        <div style="clear: both;"></div>
					                    <?php esc_html_e( 'Date Range is optional, if no date range is selected all entries will be exported.', 'kdnaforms' ); ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <ul>
                            <li id="export_submit_container" style="display:none; clear:both;">
                                <br /><br />
                                <button id="submit_button" class="button large primary"><?php esc_attr_e( 'Download Export File', 'kdnaforms' ); ?></button>
                                <span id="please_wait_container" style="display:none; margin-left:15px;">
                                    <i class='gficon-kdnaforms-spinner-icon gficon-spin'></i> <?php esc_html_e( 'Exporting entries. Progress:', 'kdnaforms' ); ?>
                                    <span id="progress_container">0%</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
		<?php
		self::page_footer();
	}

	public static function get_field_row_count( $form, $exported_field_ids, $entry_count ) {
		$list_fields = KDNAAPI::get_fields_by_type( $form, array( 'list' ), true );

		//only getting fields that have been exported
		$field_ids = array();
		foreach ( $list_fields as $field ) {
			if ( in_array( $field->id, $exported_field_ids ) && $field->enableColumns ) {
				$field_ids[] = $field->id;
			}
		}

		if ( empty( $field_ids ) ) {
			return array();
		}

		$field_ids = implode( ',', array_map( 'absint', $field_ids ) );

		$page_size = 200;
		$offset    = 0;

		$row_counts = array();
		global $wpdb;

		$go_to_next_page = true;

		while ( $go_to_next_page ) {

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( version_compare( KDNAFormsModel::get_database_version(), '2.3-dev-1', '<' ) ) {
				$sql = $wpdb->prepare( "SELECT d.field_number as field_id, d.value as value
                    FROM {$wpdb->prefix}rg_lead_detail d
                    WHERE d.form_id=%d AND cast(d.field_number as decimal) IN ({$field_ids})
                    LIMIT %d, %d",
					$form['id'],
					$offset,
					$page_size
				);
			} else {
				$sql = $wpdb->prepare( "SELECT d.meta_key as field_id, d.meta_value as value
                    FROM {$wpdb->prefix}gf_entry_meta d
                    WHERE d.form_id=%d AND d.meta_key IN ({$field_ids})
                    LIMIT %d, %d",
					$form['id'],
					$offset,
					$page_size
				);
			}
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			foreach ( $results as $result ) {
				$list              = unserialize( $result['value'] );
				$current_row_count = isset( $row_counts[ $result['field_id'] ] ) ? intval( $row_counts[ $result['field_id'] ] ) : 0;

				if ( is_array( $list ) && count( $list ) > $current_row_count ) {
					$row_counts[ $result['field_id'] ] = count( $list );
				}
			}

			$offset += $page_size;

			$go_to_next_page = count( $results ) == $page_size;
		}

		return $row_counts;
	}

	/**
	 * @deprecated No longer used.
	 * @remove-in 3.0
	 */
	public static function get_gmt_timestamp( $local_timestamp ) {
		_deprecated_function( 'KDNAExport::get_gmt_timestamp', '2.0.7', 'KDNACommon::get_gmt_timestamp' );

		return $local_timestamp - ( get_option( 'gmt_offset' ) * 3600 );
	}

	/**
	 * @deprecated No longer used.
	 * @remove-in 3.0
	 */
	public static function get_gmt_date( $local_date ) {
		_deprecated_function( 'KDNAExport::get_gmt_date', '2.0.7' );

		$local_timestamp = strtotime( $local_date );
		$gmt_timestamp   = self::get_gmt_timestamp( $local_timestamp );
		$date            = gmdate( 'Y-m-d H:i:s', $gmt_timestamp );

		return $date;
	}

	public static function start_export( $form, $offset = 0, $export_id = '' ) {

		$time_start = microtime( true );

		/***
		 * Allows the export max execution time to be changed.
		 *
		 * When the max execution time is reached, the export routine stop briefly and submit another AJAX request to continue exporting entries from the point it stopped.
		 *
		 * @since 2.0.3.10
		 *
		 * @param int   20    The amount of time, in seconds, that each request should run for.  Defaults to 20 seconds.
		 * @param array $form The Form Object
		 */
		$max_execution_time = apply_filters( 'kdnaform_export_max_execution_time', 20, $form ); // seconds
		$page_size          = 20;

		$form_id = $form['id'];
		$fields  = rgpost( 'export_field' );

		$start_date = rgpost( 'export_date_start' );
		$end_date   = rgpost( 'export_date_end' );

		$search_criteria['status']        = 'active';
		$search_criteria['field_filters'] = KDNACommon::get_field_filters_from_post( $form );
		if ( ! empty( $start_date ) ) {
			$search_criteria['start_date'] = $start_date;
		}

		if ( ! empty( $end_date ) ) {
			$search_criteria['end_date'] = $end_date;
		}

		//$sorting = array( 'key' => 'date_created', 'direction' => 'DESC', 'type' => 'info' );
		$sorting = array( 'key' => 'id', 'direction' => 'DESC', 'type' => 'info' );

		$form = self::add_default_export_fields( $form );

		/**
		 * Allows the search criteria to be filtered before exporting entries.
		 *
		 * @since 2.7
		 *
		 * @param array $search_criteria The search criteria array being filtered.
		 * @param int   $form_id         The current form ID.
		 */
		$search_criteria = apply_filters( 'kdnaform_search_criteria_export_entries', $search_criteria, $form_id );

		$total_entry_count     = KDNAAPI::count_entries( $form_id, $search_criteria );
		$remaining_entry_count = $offset == 0 ? $total_entry_count : $total_entry_count - $offset;

		$lines = '';

		// Set the separator
		$separator = gf_apply_filters( array( 'kdnaform_export_separator', $form_id ), ',', $form_id );

		$field_rows = self::get_field_row_count( $form, $fields, $remaining_entry_count );

		if ( $offset == 0 ) {
			KDNACommon::log_debug( __METHOD__ . '(): Processing request for form #' . $form_id );


			/**
			 * Allows the BOM character to be excluded from the beginning of entry export files.
			 *
			 * @since 2.1.1.21
			 *
			 * @param bool  $include_bom Whether or not to include the BOM characters. Defaults to true.
			 * @param array $form        The Form Object.
			 */
			$include_bom = apply_filters( 'kdnaform_include_bom_export_entries', true, $form );

			//Adding BOM marker for UTF-8
			$lines = $include_bom ? chr( 239 ) . chr( 187 ) . chr( 191 ) : '';

			//writing header
			$headers = array();
			foreach ( $fields as $field_id ) {
				$field = KDNAFormsModel::get_field( $form, $field_id );
				$label = gf_apply_filters( array( 'kdnaform_entries_field_header_pre_export', $form_id, $field_id ), KDNACommon::get_label( $field, $field_id ), $form, $field );
				$value = str_replace( '"', '""', $label );

				KDNACommon::log_debug( "KDNAExport::start_export(): Header for field ID {$field_id}: {$value}" );

				if ( strpos( $value, '=' ) === 0 ) {
					// Prevent Excel formulas
					$value = "'" . $value;
				}

				$headers[ $field_id ] = $value;

				$subrow_count = isset( $field_rows[ $field_id ] ) ? intval( $field_rows[ $field_id ] ) : 0;
				if ( $subrow_count == 0 ) {
					$lines .= '"' . $value . '"' . $separator;
				} else {
					for ( $i = 1; $i <= $subrow_count; $i ++ ) {
						$lines .= '"' . $value . ' ' . $i . '"' . $separator;
					}
				}

				//KDNACommon::log_debug( "KDNAExport::start_export(): Lines: {$lines}" );
			}
			$lines = substr( $lines, 0, strlen( $lines ) - 1 ) . "\n";

			if ( $remaining_entry_count == 0 ) {
				self::write_file( $lines, $export_id );
			}

			KDNACommon::log_debug( __METHOD__ . '(): search criteria: ' . print_r( $search_criteria, true ) );
			KDNACommon::log_debug( __METHOD__ . '(): sorting: ' . print_r( $sorting, true ) );
		}

		// Paging through results for memory issues
		while ( $remaining_entry_count > 0 ) {

			$paging = array(
				'offset'    => $offset,
				'page_size' => $page_size,
			);

			KDNACommon::log_debug( __METHOD__ . '(): paging: ' . print_r( $paging, true ) );

			$leads = KDNAAPI::get_entries( $form_id, $search_criteria, $sorting, $paging );

			$leads = gf_apply_filters( array( 'kdnaform_leads_before_export', $form_id ), $leads, $form, $paging );

			foreach ( $leads as $lead ) {
				$line = self::get_entry_export_line( $lead, $form, $fields, $field_rows, $separator );
				/**
				 * Filter the current line being exported.
				 *
				 * @since 2.4.11.5
				 *
				 * @param string   $line       The current line being exported.
				 * @param array    $form       The current form object.
				 * @param array|string    $fields     An array of field IDs to be exported.
				 * @param array    $field_rows An array of List fields
				 * @param array    $entry      The current entry.
				 * @param string   $separator  The separator
				 */
				$line = apply_filters( 'kdnaform_export_line', $line, $form, $fields, $field_rows, $lead, $separator );
				$lines .= "$line\n";
			}

			$offset += $page_size;
			$remaining_entry_count -= $page_size;

			if( function_exists( 'wp_is_valid_utf8' ) ) {
				// WP 6.9+
				$is_valid_utf8 = wp_is_valid_utf8( $lines );
			} else {
				$is_valid_utf8 = seems_utf8( $lines );
			}

			if ( ! $is_valid_utf8 ) {
				$lines = mb_convert_encoding( $lines, 'UTF-8', 'ISO-8859-1' );
			}

			$lines = apply_filters( 'kdnaform_export_lines', $lines );

			self::write_file( $lines, $export_id );

			$time_end       = microtime( true );
			$execution_time = ( $time_end - $time_start );

			if ( $execution_time >= $max_execution_time ) {
				break;
			}

			$lines = '';
		}

		$complete = $remaining_entry_count <= 0;

		if ( $complete ) {
			/**
			 * Fires after exporting all the entries in form
			 *
			 * @since 2.4.5.11 Added the $export_id param.
			 * @since 1.9.3
			 *
			 * @param array  $form       The Form object to get the entries from
			 * @param string $start_date The start date for when the export of entries should take place
			 * @param string $end_date   The end date for when the export of entries should stop
			 * @param array|string  $fields     The specified fields where the entries should be exported from
			 * @param string $export_id  A unique ID for the export.
			 */
			do_action( 'kdnaform_post_export_entries', $form, $start_date, $end_date, $fields, $export_id );
		}

		$offset = $complete ? 0 : $offset;

		$status = array(
			'status'   => $complete ? 'complete' : 'in_progress',
			'offset'   => $offset,
			'exportId' => $export_id,
			'progress' => $remaining_entry_count > 0 ? intval( 100 - ( $remaining_entry_count / $total_entry_count ) * 100 ) . '%' : '',
		);

		KDNACommon::log_debug( __METHOD__ . '(): Status: ' . print_r( $status, 1 ) );

		return $status;
	}

	/**
	 * Returns the content to be included in the export for the supplied entry.
	 *
	 * @since 2.4.5.11
	 *
	 * @param array  $entry      The entry being exported.
	 * @param array  $form       The form associated with the current entry.
	 * @param array  $fields     The IDs of the fields to be exported.
	 * @param array  $field_rows An array of List fields.
	 * @param string $separator  The character to be used as the column separator.
	 *
	 * @return string
	 */
	public static function get_entry_export_line( $entry, $form, $fields, $field_rows, $separator ) {
		KDNACommon::log_debug( __METHOD__ . '(): Processing entry #' . $entry['id'] );

		$line = '';

		foreach ( $fields as $field_id ) {
			switch ( $field_id ) {
				case 'date_created' :
				case 'payment_date' :
					$value = $entry[ $field_id ];
					if ( $value ) {
						$lead_gmt_time   = mysql2date( 'G', $value );
						$lead_local_time = KDNACommon::get_local_timestamp( $lead_gmt_time );
						$value           = date_i18n( 'Y-m-d H:i:s', $lead_local_time, true );
					}
					break;
				default :
					$field = KDNAAPI::get_field( $form, $field_id );

					$value = is_object( $field ) ? $field->get_value_export( $entry, $field_id, false, true ) : rgar( $entry, $field_id );
					$value = apply_filters( 'kdnaform_export_field_value', $value, $form['id'], $field_id, $entry );
					break;
			}

			if ( isset( $field_rows[ $field_id ] ) ) {
				$list = empty( $value ) ? array() : $value;

				foreach ( $list as $row ) {
					if ( is_array( $row ) ) {
						// Entry from a multi-column list field.
						$row_values = array_values( $row );
						$row_str    = implode( '|', $row_values );
					} else {
						// Entry from a standard list field.
						$row_str = $row;
					}

					if ( strpos( $row_str, '=' ) === 0 ) {
						// Prevent Excel formulas
						$row_str = "'" . $row_str;
					}

					$line .= '"' . str_replace( '"', '""', $row_str ) . '"' . $separator;
				}

				//filling missing subrow columns (if any)
				$missing_count = intval( $field_rows[ $field_id ] ) - count( $list );
				for ( $i = 0; $i < $missing_count; $i ++ ) {
					$line .= '""' . $separator;
				}
			} else {
				if ( is_array( $value ) ) {
					if ( ! empty( $value[0] ) && is_array( $value[0] ) ) {
						// Entry from a multi-column list field.
						$values = array();
						foreach ( $value as $item ) {
							$values[] = implode( '|', array_values( $item ) );
						}

						$value = implode( ',', $values );
					} else {
						// Entry from a standard list field.
						$value = implode( '|', $value );
					}
				}

				if ( ! empty( $value ) ) {
					if ( strpos( $value, '=' ) === 0 ) {
						// Prevent Excel formulas
						$value = "'" . $value;
					}

					$value = str_replace( '"', '""', $value );
				}

				$line .= '"' . $value . '"' . $separator;
			}
		}

		$line = substr( $line, 0, strlen( $line ) - 1 );

		return $line;
	}

	public static function add_default_export_fields( $form ) {

		//adding default fields
		array_push( $form['fields'], array( 'id' => 'created_by', 'label' => __( 'Created By (User Id)', 'kdnaforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'id', 'label' => __( 'Entry Id', 'kdnaforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'date_created', 'label' => __( 'Entry Date', 'kdnaforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'date_updated', 'label' => __( 'Date Updated', 'kdnaforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'source_url', 'label' => __( 'Source Url', 'kdnaforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'transaction_id', 'label' => __( 'Transaction Id', 'kdnaforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'payment_amount', 'label' => __( 'Payment Amount', 'kdnaforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'payment_date', 'label' => __( 'Payment Date', 'kdnaforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'payment_status', 'label' => __( 'Payment Status', 'kdnaforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'post_id', 'label' => __( 'Post Id', 'kdnaforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'user_agent', 'label' => __( 'User Agent', 'kdnaforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'ip', 'label' => __( 'User IP', 'kdnaforms' ) ) );
		$form = self::get_entry_meta( $form );

		$form = apply_filters( 'kdnaform_export_fields', $form );
		$form = KDNAFormsModel::convert_field_objects( $form );

		foreach ( $form['fields'] as $field ) {
			/* @var KDNA_Field $field */

			$field->set_context_property( 'use_admin_label', true );
		}

		return $form;
	}

	private static function get_entry_meta( $form ) {
		$entry_meta = KDNAFormsModel::get_entry_meta( $form['id'] );
		$keys       = array_keys( $entry_meta );
		foreach ( $keys as $key ) {
			array_push( $form['fields'], array( 'id' => $key, 'label' => $entry_meta[ $key ]['label'] ) );
		}

		return $form;
	}

	public static function page_header() {
        KDNAForms::admin_header( self::get_tabs(), false );
	}

	public static function page_footer() {
	    KDNAForms::admin_footer();
	}

	public static function get_tabs() {

		$setting_tabs = array();
		if ( KDNACommon::current_user_can_any( 'kdnaforms_export_entries' ) ) {
			$icon               = '<svg width="24" height="24" role="presentation" focusable="false" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>export entries</title><g fill="none" class="nc-icon-wrapper"><path stroke="#000" stroke-width="1.5" d="M8 19.25h7"/><path stroke="#000" stroke-width="1.5" d="M8 15.25h12"/><path stroke="#000" stroke-width="1.5" d="M4 19.25h2"/><path stroke="#000" stroke-width="1.5" d="M4 15.25h2"/><path d="M7.614 5L5 7.1M7.614 5v6m0-6L10 7.1" stroke="#1E1E1E" stroke-width="1.5"/></g></svg>';
			$setting_tabs['10'] = array(
				'name'  => 'export_entry',
				'label' => __( 'Export Entries', 'kdnaforms' ),
				'icon'  => $icon,
			);
		}

		if ( KDNACommon::current_user_can_any( 'kdnaforms_edit_forms' ) ) {
			$icon               = '<svg width="24" height="24" role="presentation" focusable="false"  viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>export form</title><g fill="none" class="nc-icon-wrapper"><path d="M5 3.75h14c.69 0 1.25.56 1.25 1.25v14c0 .69-.56 1.25-1.25 1.25H5c-.69 0-1.25-.56-1.25-1.25V5c0-.69.56-1.25 1.25-1.25z" stroke="#111111" stroke-width="1.5"/><path d="M9 4L5 8.5V4h4z" fill="#111111" stroke="#111111"/><path d="M15.286 11L12 8l-3 3" stroke="#111111" stroke-width="1.5"/><path fill="#111111" d="M11 9h2v8h-2z"/></g></svg>';
			$setting_tabs['20'] = array(
				'name'  => 'export_form',
				'label' => __( 'Export Forms', 'kdnaforms' ),
				'icon'  => $icon,
			);

			if ( KDNACommon::current_user_can_any( 'kdnaforms_create_form' ) ) {
				$icon               = '<svg width="24" height="24" role="presentation" focusable="false"  viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>import form</title><g fill="none" class="nc-icon-wrapper"><path d="M5 3.75h14c.69 0 1.25.56 1.25 1.25v14c0 .69-.56 1.25-1.25 1.25H5c-.69 0-1.25-.56-1.25-1.25V5c0-.69.56-1.25 1.25-1.25z" stroke="#111111" stroke-width="1.5"/><path d="M9 4L5 8.5V4h4z" fill="#111111" stroke="#111111"/><path d="M9 13l3.286 3 3-3" stroke="#111111" stroke-width="1.5"/><path d="M13.286 15h-2V7h2v8z" fill="#111111"/></g></svg>';
				$setting_tabs['30'] = array(
					'name'  => 'import_form',
					'label' => __( 'Import Forms', 'kdnaforms' ),
					'icon'  => $icon,
				);
			}
		}

		$setting_tabs = apply_filters( 'kdnaform_export_menu', $setting_tabs );
		ksort( $setting_tabs, SORT_NUMERIC );

		return $setting_tabs;
	}

	/**
	 * Handles the export request from the export entries page.
	 *
	 * @since 2.0.0
	 */
	public static function ajax_process_export() {
		check_admin_referer( 'rg_start_export', 'rg_start_export_nonce' );

		if ( ! KDNACommon::current_user_can_any( 'kdnaforms_export_entries' ) ) {
			die();
		}

		$offset = absint( rgpost( 'offset' ) );
		$export_id = sanitize_key( ( rgpost( 'exportId' ) ) );

		$form_id = rgpost( 'export_form' );
		$form    = KDNAFormsModel::get_form_meta( $form_id );

		if ( empty( $export_id ) ) {
			$export_id = wp_hash( uniqid( 'export', true ) );
			$export_id = sanitize_key( $export_id );
		}

		$status = self::start_export( $form, $offset, $export_id );

		echo json_encode( $status );
		die();
	}

	/**
	 * Appends lines to to the csv file for the given Export ID.
	 *
	 * @param string $lines
	 * @param string $export_id A unique ID for the export.
	 */
	public static function write_file( $lines, $export_id ) {

		$uploads_folder = KDNAFormsModel::get_upload_root();
		if ( ! is_dir( $uploads_folder ) ) {
			wp_mkdir_p( $uploads_folder );
		}

		$export_folder = $uploads_folder . 'export';
		if ( ! is_dir( $export_folder ) ) {
			wp_mkdir_p( $export_folder );
		}

		$export_folder = trailingslashit( $export_folder );

		self::maybe_create_htaccess_file( $export_folder );
		self::maybe_create_index_file( $export_folder );

		$file = $export_folder . sanitize_file_name( 'export-' . $export_id .'.csv' );

		KDNACommon::log_debug( __METHOD__ . '(): Writing to file.' );
		$result = file_put_contents( $file, $lines, FILE_APPEND );
		if ( $result === false ) {
			KDNACommon::log_error( __METHOD__ . '(): An issue occurred whilst writing to the file.' );
		} else {
			KDNACommon::log_debug( __METHOD__ . '(): Number of bytes written to the file: ' . print_r( $result, 1 ) );
		}

	}

	/**
	 * Creates an .htaccess file in the given path which will disable access to all files on Apache Web Servers.
	 *
	 * @since 2.0.0
	 *
	 * @param $path
	 */
	public static function maybe_create_htaccess_file( $path ) {
		$htaccess_file = $path . '.htaccess';
		if ( file_exists( $htaccess_file ) ) {
			return;
		}
		$txt = '# Disable access to files via Apache web servers.
deny from all';
		$rules = explode( "\n", $txt );

		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/misc.php' );
		}
		insert_with_markers( $htaccess_file, 'KDNA Forms', $rules );
	}

	/**
	 * Adds an empty index file in the given path if it doesn't exist already.
	 *
	 * @since 2.0.0
	 *
	 * @param $path
	 */
	public static function maybe_create_index_file( $path ) {
		$path = untrailingslashit( $path );
		$index_file = $path . '/index.html';
		if ( file_exists( $index_file ) ) {
			return;
		}
		KDNACommon::recursive_add_index_file( $path );
	}

	/**
	 * Handles the download request from the export entries page.
	 *
	 * @since 2.0.0
	 */
	public static function ajax_download_export() {
		check_admin_referer( 'kdnaform_download_export' );

		if ( ! function_exists( 'readfile' ) ) {
			KDNACommon::log_error( __METHOD__ . '(): Aborting. The PHP readfile function is not available.' );
			die( esc_html__( 'The PHP readfile function is not available, please contact the web host.', 'kdnaforms' ) );
		}

		if ( ! KDNACommon::current_user_can_any( 'kdnaforms_export_entries' ) ) {
			die();
		}

		$form_id = absint( rgget( 'form-id' ) );

		if ( empty( $form_id ) ) {
			die();
		}

		$form = KDNAAPI::get_form( $form_id );

		if ( empty( $form ) ) {
			die();
		}

		$filename = sanitize_title_with_dashes( $form['title'] ) . '-' . gmdate( 'Y-m-d', KDNACommon::get_local_timestamp( time() ) ) . '.csv';

		KDNACommon::log_debug( __METHOD__ . '(): Starting download of file: ' . $filename );

		$charset = get_option( 'blog_charset' );
		header( 'Content-Description: File Transfer' );
		header( "Content-Disposition: attachment; filename=$filename" );
		header( 'Content-Type: text/csv; charset=' . $charset, true );
		$buffer_length = ob_get_length(); //length or false if no buffer
		if ( $buffer_length > 1 ) {
			ob_clean();
		}

		if ( has_filter( 'sanitize_file_name' ) ) {
			KDNACommon::log_debug( __METHOD__ . '(): The WordPress sanitize_file_name filter has been detected.' );
		}

		$export_folder = KDNAFormsModel::get_upload_root() . 'export/';
		$export_id     = rgget( 'export-id' );
		$file          = $export_folder . sanitize_file_name( 'export-' . $export_id . '.csv' );
		$result        = readfile( $file );

		if ( $result === false ) {
			KDNACommon::log_error( __METHOD__ . '(): An issue occurred whilst reading the file.' );
		} else {
			@unlink( $file );
			KDNACommon::log_debug( __METHOD__ . '(): Number of bytes read from the file: ' . print_r( $result, 1 ) );
		}

		exit;
	}

	public static function prepare_forms_for_export( $forms ) {
		// clean up a bit before exporting
		foreach ( $forms as &$form ) {

			foreach ( $form['fields'] as &$field ) {
				$inputType = KDNAFormsModel::get_input_type( $field );

				if ( isset( $field->pageNumber ) ) {
					unset( $field->pageNumber );
				}

				if ( $inputType != 'address' ) {
					unset( $field->addressType );
				}

				if ( $inputType != 'date' ) {
					unset( $field->calendarIconType );
					unset( $field->dateType );
				}

				if ( $inputType != 'creditcard' ) {
					unset( $field->creditCards );
				}

				if ( $field->type == $field->inputType ) {
					unset( $field->inputType );
				}

				// convert associative array to indexed
				if ( isset( $form['confirmations'] ) ) {
					$form['confirmations'] = array_values( $form['confirmations'] );
				}

				if ( isset( $form['notifications'] ) ) {
					$form['notifications'] = array_values( $form['notifications'] );
				}
			}

			/**
			 * Allows you to filter and modify the Export Form
			 *
			 * @param array $form Assign which Gravity Form to change the export form for
			 */
			$form = gf_apply_filters( array( 'kdnaform_export_form', $form['id'] ), $form );

		}

		$forms['version'] = KDNAForms::$version;

		return $forms;
	}
}
