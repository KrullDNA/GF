function loadBillingLength(setting_name){
    var intervals = window[setting_name + "_intervals"]
    if(!intervals)
        return;

    var unit = jQuery("#" + setting_name + "_unit").val();
    var min =  kform.utils.escapeHtml( intervals[unit]["min"] );
    var max =  kform.utils.escapeHtml( intervals[unit]["max"] );
    var lengthField = jQuery("#" + setting_name + "_length");
    var length = lengthField.val();
    var str = "";
    for(var i=min; i<=max; i++){
        var selected = length == i ? "selected='selected'" : "";
        str += "<option value='" + i + "' " + selected + ">" + i + "</option>";
    }
    lengthField.html( str );
}

function cancel_subscription( entryId ) {

	if ( !confirm( kaddon_payment_strings.subscriptionCancelWarning ) )
		return;

	jQuery( "#subscription_cancel_spinner" ).show();
	jQuery( "#cancelsub" ).prop( "disabled", true );
	jQuery.post(
		ajaxurl,
		{
			action:                     "kaddon_cancel_subscription",
			entry_id:                   entryId,
			kaddon_cancel_subscription: kaddon_payment_strings.subscriptionCancelNonce
		},
		function ( response ) {
			jQuery( "#subscription_cancel_spinner" ).hide();
			if ( response.success === true ) {
				jQuery( "#kdnaform_payment_status" ).html( kform.utils.escapeHtml( kaddon_payment_strings.subscriptionCanceled ) );
				jQuery( "#cancelsub" ).hide();
			} else {
				jQuery( "#cancelsub" ).prop( "disabled", false );
				if ( response.success === false ) {
					alert( kaddon_payment_strings.subscriptionError );
				}
			}
		}
	);
}
