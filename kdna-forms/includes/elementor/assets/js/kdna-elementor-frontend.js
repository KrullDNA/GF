/**
 * KDNA Forms Elementor Frontend Script
 *
 * Handles popup compatibility and form reinitialization.
 */
(function($) {
	'use strict';

	// Re-init KDNA Forms inside Elementor popups
	$(document).on('elementor/popup/show', function(event, id, instance) {
		if (!instance) return;

		var $popup;
		try {
			$popup = instance.getElements('$element');
		} catch(e) {
			$popup = instance.$element || null;
		}

		if (!$popup || !$popup.length) return;

		var $forms = $popup.find('.kdnaform_wrapper');
		if (!$forms.length) return;

		$forms.each(function() {
			var $wrapper = $(this);
			var formIdAttr = $wrapper.attr('id') || '';
			var numericId = formIdAttr.replace('kdnaform_wrapper_', '');

			if (!numericId) return;

			// Re-trigger form render event for add-ons and conditional logic
			$(document).trigger('kdnaform_post_render', [numericId, 0]);

			// Reinitialize conditional logic
			if (typeof window['kdnaform_conditional_logic'] !== 'undefined' &&
				typeof window['kdnaform_conditional_logic'][numericId] !== 'undefined') {
				try {
					window.kdnaform_apply_rules(numericId);
				} catch(e) {}
			}

			// Re-apply input masks
			if ($.fn.mask) {
				$wrapper.find('input[data-mask]').each(function() {
					var mask = $(this).data('mask');
					if (mask) $(this).mask(mask);
				});
			}

			// Reinitialize datepickers
			if ($.fn.datepicker) {
				$wrapper.find('.datepicker:not(.hasDatepicker)').datepicker();
			}

			// Trigger resize for responsive recalculation
			$(window).trigger('resize');
		});
	});

	// Handle Elementor frontend widget initialization
	if (typeof elementorFrontend !== 'undefined') {
		$(window).on('elementor/frontend/init', function() {
			elementorFrontend.hooks.addAction('frontend/element_ready/kdna-forms.default', function($scope) {
				var $forms = $scope.find('.kdnaform_wrapper');
				$forms.each(function() {
					var $wrapper = $(this);
					var formIdAttr = $wrapper.attr('id') || '';
					var numericId = formIdAttr.replace('kdnaform_wrapper_', '');
					if (numericId) {
						$(document).trigger('kdnaform_post_render', [numericId, 0]);
					}
				});
			});
		});
	}

})(jQuery);
