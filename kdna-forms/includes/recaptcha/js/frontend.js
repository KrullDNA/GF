/**
 * KDNA Forms reCAPTCHA Frontend
 *
 * Handles reCAPTCHA token generation for form submissions.
 */
(function($) {
    'use strict';

    if (typeof kdna_recaptcha_strings === 'undefined') {
        return;
    }

    var siteKey = kdna_recaptcha_strings.site_key;
    var connectionType = kdna_recaptcha_strings.connection_type;

    function getRecaptchaToken(formElement) {
        var responseInput = $(formElement).find('.kdnafield_recaptcha_response');
        if (!responseInput.length) {
            return;
        }

        if (connectionType === 'enterprise') {
            if (typeof grecaptcha === 'undefined' || typeof grecaptcha.enterprise === 'undefined') {
                return;
            }
            grecaptcha.enterprise.ready(function() {
                grecaptcha.enterprise.execute(siteKey, { action: 'submit' }).then(function(token) {
                    responseInput.val(token);
                });
            });
        } else {
            if (typeof grecaptcha === 'undefined') {
                return;
            }
            grecaptcha.ready(function() {
                grecaptcha.execute(siteKey, { action: 'submit' }).then(function(token) {
                    responseInput.val(token);
                });
            });
        }
    }

    // Initialize on form render
    $(document).on('kdnaform_post_render', function(event, formId) {
        var form = document.getElementById('kdnaform_' + formId);
        if (form) {
            getRecaptchaToken(form);
        }
    });

    // Refresh token on page change (multi-page forms)
    $(document).on('kdnaform_page_loaded', function(event, formId) {
        var form = document.getElementById('kdnaform_' + formId);
        if (form) {
            getRecaptchaToken(form);
        }
    });

    // Handle Elementor popup form initialization
    $(document).on('elementor/popup/show', function(event, id, instance) {
        var popup = instance ? instance.$element : null;
        if (popup) {
            popup.find('.kdnafield_recaptcha_response').each(function() {
                getRecaptchaToken($(this).closest('form'));
            });
        }
    });

    // Initialize all forms on page load
    $(document).ready(function() {
        $('form[id^="kdnaform_"]').each(function() {
            getRecaptchaToken(this);
        });
    });

})(jQuery);
