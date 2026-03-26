/**
 * Apply legacy options to DatePickers within Legacy Forms.
 */
gform.addFilter( 'kdnaform_datepicker_options_pre_init', function( optionsObj, formId, inputId, $element ) {
	var kdna_legacy = window.kdna_legacy_multi;

	if ( ! kdna_legacy ) {
		return optionsObj;
	}
	if ( !kdna_legacy[ formId ] || kdna_legacy[ formId ] !== '1' ) {
		return optionsObj;
	}

	var $ = window.jQuery;
	var isPreview = $( '#preview_form_container' ).length > 0;
	var isRTL = window.getComputedStyle( $element[ 0 ], null ).getPropertyValue( 'direction' ) === 'rtl';
	var overrides = {
		showOtherMonths: false,
		beforeShow: function( input, inst ) {
			inst.dpDiv[0].classList.remove( 'kdnaform-theme-datepicker' );
			inst.dpDiv[0].classList.remove( 'gravity-theme' );
			inst.dpDiv[0].classList.remove( 'kdnaform-theme' );
			inst.dpDiv[0].classList.remove( 'kdnaform-legacy-datepicker' );
			inst.dpDiv[0].classList.remove( 'kdnaform-theme--framework' );
			inst.dpDiv[0].classList.remove( 'kdnaform-theme--foundation' );
			inst.dpDiv[0].classList.remove( 'kdnaform-theme--orbital' );
			inst.dpDiv[0].classList.add( 'kdnaform-legacy-datepicker' );

			if ( isRTL && isPreview ) {
				var $inputContainer = $( input ).closest( '.gfield' );
				var rightOffset = $( document ).outerWidth() - ( $inputContainer.offset().left + $inputContainer.outerWidth() );
				inst.dpDiv[ 0 ].style.right = rightOffset + 'px';
			}

			if ( isPreview ) {
				inst.dpDiv[0].classList.add( 'kdnaform-preview-datepicker' );
			}
			return ! this.suppressDatePicker;
		}
	};

	return Object.assign( optionsObj, overrides );
}, -10 );
