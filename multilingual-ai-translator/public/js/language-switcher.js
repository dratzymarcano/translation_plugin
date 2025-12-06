/**
 * MultiLingual AI Translator - Frontend Language Switcher JS
 * Handles interactions for language switcher components
 *
 * @package MultiLingual_AI_Translator
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Language Switcher Handler
     */
    const MATSwitcher = {
        /**
         * Initialize all switchers
         */
        init: function() {
            this.bindDropdownEvents();
            this.bindFloatingWidgetEvents();
            this.bindLanguageLinks();
            this.handleClickOutside();
        },

        /**
         * Bind dropdown switcher events
         */
        bindDropdownEvents: function() {
            const self = this;

            // Toggle dropdown on button click
            $(document).on('click', '.mat-switcher-dropdown .mat-switcher-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $switcher = $(this).closest('.mat-language-switcher');
                const isOpen = $switcher.hasClass('mat-open');
                
                // Close all other dropdowns
                self.closeAllDropdowns();
                
                // Toggle current dropdown
                if (!isOpen) {
                    $switcher.addClass('mat-open');
                    $(this).attr('aria-expanded', 'true');
                }
            });

            // Keyboard navigation for dropdown
            $(document).on('keydown', '.mat-switcher-dropdown .mat-switcher-toggle', function(e) {
                const $switcher = $(this).closest('.mat-language-switcher');
                
                if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    $switcher.addClass('mat-open');
                    $(this).attr('aria-expanded', 'true');
                    $switcher.find('.mat-lang-item').first().focus();
                } else if (e.key === 'Escape') {
                    self.closeAllDropdowns();
                }
            });

            // Arrow key navigation within dropdown
            $(document).on('keydown', '.mat-switcher-dropdown .mat-lang-item', function(e) {
                const $items = $(this).closest('ul').find('.mat-lang-item');
                const currentIndex = $items.index(this);
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const nextIndex = (currentIndex + 1) % $items.length;
                    $items.eq(nextIndex).focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevIndex = (currentIndex - 1 + $items.length) % $items.length;
                    $items.eq(prevIndex).focus();
                } else if (e.key === 'Escape') {
                    self.closeAllDropdowns();
                    $(this).closest('.mat-language-switcher').find('.mat-switcher-toggle').focus();
                }
            });
        },

        /**
         * Bind floating widget events
         */
        bindFloatingWidgetEvents: function() {
            const self = this;

            // Toggle floating widget
            $(document).on('click', '.mat-floating-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $widget = $(this).closest('.mat-floating-widget');
                const isOpen = $widget.hasClass('mat-open');
                
                if (isOpen) {
                    self.closeFloatingWidget($widget);
                } else {
                    self.openFloatingWidget($widget);
                }
            });

            // Close button in floating widget
            $(document).on('click', '.mat-floating-close', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $widget = $(this).closest('.mat-floating-widget');
                self.closeFloatingWidget($widget);
            });

            // Keyboard support for floating widget
            $(document).on('keydown', '.mat-floating-toggle', function(e) {
                const $widget = $(this).closest('.mat-floating-widget');
                
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if ($widget.hasClass('mat-open')) {
                        self.closeFloatingWidget($widget);
                    } else {
                        self.openFloatingWidget($widget);
                    }
                } else if (e.key === 'Escape') {
                    self.closeFloatingWidget($widget);
                }
            });

            // Arrow key navigation in floating list
            $(document).on('keydown', '.mat-floating-item', function(e) {
                const $items = $(this).closest('.mat-floating-list').find('.mat-floating-item');
                const currentIndex = $items.index(this);
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const nextIndex = (currentIndex + 1) % $items.length;
                    $items.eq(nextIndex).focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevIndex = (currentIndex - 1 + $items.length) % $items.length;
                    $items.eq(prevIndex).focus();
                } else if (e.key === 'Escape') {
                    const $widget = $(this).closest('.mat-floating-widget');
                    self.closeFloatingWidget($widget);
                    $widget.find('.mat-floating-toggle').focus();
                }
            });
        },

        /**
         * Open floating widget
         */
        openFloatingWidget: function($widget) {
            $widget.addClass('mat-open');
            $widget.find('.mat-floating-toggle').attr('aria-expanded', 'true');
            
            // Focus first item
            setTimeout(function() {
                $widget.find('.mat-floating-item').first().focus();
            }, 100);
        },

        /**
         * Close floating widget
         */
        closeFloatingWidget: function($widget) {
            $widget.removeClass('mat-open');
            $widget.find('.mat-floating-toggle').attr('aria-expanded', 'false');
        },

        /**
         * Bind language link clicks
         */
        bindLanguageLinks: function() {
            // Add loading indicator on click
            $(document).on('click', '.mat-lang-item, .mat-flag-item, .mat-floating-item', function(e) {
                const $link = $(this);
                
                // Skip if already active
                if ($link.hasClass('mat-active')) {
                    e.preventDefault();
                    return;
                }
                
                // Add loading class
                $link.addClass('mat-loading');
                
                // Let the link navigate normally (page reload)
            });
        },

        /**
         * Handle click outside to close dropdowns
         */
        handleClickOutside: function() {
            const self = this;
            
            $(document).on('click', function(e) {
                const $target = $(e.target);
                
                // Close dropdowns if click outside
                if (!$target.closest('.mat-language-switcher').length) {
                    self.closeAllDropdowns();
                }
                
                // Close floating widget if click outside
                if (!$target.closest('.mat-floating-widget').length) {
                    $('.mat-floating-widget.mat-open').each(function() {
                        self.closeFloatingWidget($(this));
                    });
                }
            });
        },

        /**
         * Close all dropdown switchers
         */
        closeAllDropdowns: function() {
            $('.mat-language-switcher.mat-open').removeClass('mat-open');
            $('.mat-switcher-toggle').attr('aria-expanded', 'false');
        },

        /**
         * Switch language via AJAX (optional - for SPA-like experience)
         */
        switchLanguage: function(langCode, callback) {
            if (typeof matSwitcher === 'undefined') {
                console.warn('MAT Switcher: Config not found');
                return;
            }

            $.ajax({
                url: matSwitcher.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mat_switch_language',
                    nonce: matSwitcher.nonce,
                    lang: langCode
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof callback === 'function') {
                            callback(response.data);
                        }
                    } else {
                        console.error('MAT Switcher: Failed to switch language', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('MAT Switcher: AJAX error', error);
                }
            });
        },

        /**
         * Get current language
         */
        getCurrentLanguage: function() {
            if (typeof matSwitcher !== 'undefined') {
                return matSwitcher.currentLanguage;
            }
            return null;
        }
    };

    /**
     * Initialize on DOM ready
     */
    $(document).ready(function() {
        MATSwitcher.init();
    });

    /**
     * Expose to global scope for external use
     */
    window.MATSwitcher = MATSwitcher;

})(jQuery);
