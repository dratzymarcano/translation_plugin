/**
 * MultiLingual AI Translator Pro - Admin Scripts
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize sortable languages
		initSortable();
		
		// Language toggle handlers
		initLanguageToggle();
		
		// Set default language
		initSetDefault();
		
		// Delete language
		initDeleteLanguage();
		
		// Add language form
		initAddLanguage();
		
		// API key visibility toggle
		initApiKeyToggle();
	});

	/**
	 * Initialize sortable languages list
	 */
	function initSortable() {
		$('#mat-languages-sortable').sortable({
			handle: '.mat-lang-drag',
			placeholder: 'mat-language-item ui-sortable-placeholder',
			update: function(event, ui) {
				var order = [];
				$('.mat-language-item').each(function() {
					order.push($(this).data('id'));
				});
				
				$.ajax({
					url: matAdmin.ajaxUrl,
					type: 'POST',
					data: {
						action: 'mat_reorder_languages',
						nonce: matAdmin.nonce,
						order: order
					},
					success: function(response) {
						if (response.success) {
							showNotice('Languages reordered successfully', 'success');
						}
					}
				});
			}
		});
	}

	/**
	 * Language active toggle
	 */
	function initLanguageToggle() {
		$(document).on('change', '.mat-toggle-active', function() {
			var $toggle = $(this);
			var id = $toggle.data('id');
			var $item = $toggle.closest('.mat-language-item');
			
			$.ajax({
				url: matAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'mat_toggle_language',
					nonce: matAdmin.nonce,
					id: id
				},
				success: function(response) {
					if (response.success) {
						if (response.data.is_active) {
							$item.removeClass('mat-inactive').addClass('mat-active');
						} else {
							$item.removeClass('mat-active').addClass('mat-inactive');
						}
						showNotice('Language status updated', 'success');
					}
				},
				error: function() {
					// Revert toggle
					$toggle.prop('checked', !$toggle.prop('checked'));
					showNotice('Error updating language status', 'error');
				}
			});
		});
	}

	/**
	 * Set default language
	 */
	function initSetDefault() {
		$(document).on('click', '.mat-set-default', function(e) {
			e.preventDefault();
			
			var $btn = $(this);
			var id = $btn.data('id');
			
			if (!confirm('Set this as the default language?')) {
				return;
			}
			
			$.ajax({
				url: matAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'mat_set_default_language',
					nonce: matAdmin.nonce,
					id: id
				},
				success: function(response) {
					if (response.success) {
						location.reload();
					}
				},
				error: function() {
					showNotice('Error setting default language', 'error');
				}
			});
		});
	}

	/**
	 * Delete language
	 */
	function initDeleteLanguage() {
		$(document).on('click', '.mat-delete-lang', function(e) {
			e.preventDefault();
			
			var $btn = $(this);
			var id = $btn.data('id');
			var $item = $btn.closest('.mat-language-item');
			
			if (!confirm('Are you sure you want to delete this language? This cannot be undone.')) {
				return;
			}
			
			$.ajax({
				url: matAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'mat_delete_language',
					nonce: matAdmin.nonce,
					id: id
				},
				success: function(response) {
					if (response.success) {
						$item.fadeOut(300, function() {
							$(this).remove();
						});
						showNotice('Language deleted', 'success');
					} else {
						showNotice(response.data || 'Error deleting language', 'error');
					}
				},
				error: function() {
					showNotice('Error deleting language', 'error');
				}
			});
		});
	}

	/**
	 * Add language form
	 */
	function initAddLanguage() {
		$('#mat-add-language-form').on('submit', function(e) {
			e.preventDefault();
			
			var $form = $(this);
			var $btn = $form.find('button[type="submit"]');
			var originalText = $btn.html();
			
			$btn.prop('disabled', true).html('<span class="dashicons dashicons-update mat-spin"></span> Adding...');
			
			$.ajax({
				url: matAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'mat_add_language',
					nonce: matAdmin.nonce,
					code: $('#lang-code').val(),
					name: $('#lang-name').val(),
					native_name: $('#lang-native').val(),
					flag_code: $('#lang-flag').val()
				},
				success: function(response) {
					if (response.success) {
						location.reload();
					} else {
						showNotice(response.data || 'Error adding language', 'error');
						$btn.prop('disabled', false).html(originalText);
					}
				},
				error: function() {
					showNotice('Error adding language', 'error');
					$btn.prop('disabled', false).html(originalText);
				}
			});
		});
	}

	/**
	 * API key visibility toggle
	 */
	function initApiKeyToggle() {
		$('#mat-toggle-api-key').on('click', function() {
			var $input = $('#mat_openrouter_api_key');
			var $icon = $(this).find('.dashicons');
			
			if ($input.attr('type') === 'password') {
				$input.attr('type', 'text');
				$icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
			} else {
				$input.attr('type', 'password');
				$icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
			}
		});
	}

	/**
	 * Show notice
	 */
	function showNotice(message, type) {
		var $notice = $('<div class="notice notice-' + type + ' is-dismissible mat-notice"><p>' + message + '</p></div>');
		
		$('.mat-admin-header').after($notice);
		
		setTimeout(function() {
			$notice.fadeOut(300, function() {
				$(this).remove();
			});
		}, 3000);
	}

})(jQuery);

// Add spin animation
var style = document.createElement('style');
style.textContent = '@keyframes mat-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } } .mat-spin { animation: mat-spin 1s linear infinite; }';
document.head.appendChild(style);
