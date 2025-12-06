/**
 * Translation Editor JavaScript
 * Handles AJAX translation and tab switching
 */
(function($) {
    'use strict';

    var TranslationEditor = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Tab switching
            $(document).on('click', '.mat-tab-btn', this.switchTab);
            
            // Single language translate
            $(document).on('click', '.mat-translate-btn', this.translateSingle);
            
            // Translate all languages
            $(document).on('click', '.mat-translate-all-btn', this.translateAll);
            
            // Character counters
            $(document).on('input', '.mat-field-meta-title', this.updateTitleCounter);
            $(document).on('input', '.mat-field-meta-desc', this.updateDescCounter);
            
            // Auto-generate slug from title
            $(document).on('blur', '.mat-field-title', this.generateSlug);
        },

        switchTab: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var tab = $btn.data('tab');
            
            // Update button states
            $('.mat-tab-btn').removeClass('mat-active');
            $btn.addClass('mat-active');
            
            // Show corresponding pane
            $('.mat-tab-pane').removeClass('mat-active');
            $('.mat-tab-pane[data-lang="' + tab + '"]').addClass('mat-active');
        },

        translateSingle: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var lang = $btn.data('lang');
            var $pane = $btn.closest('.mat-tab-pane');
            var $status = $pane.find('.mat-translate-status');
            
            // Get post content from main editor
            var postTitle = $('#title').val() || '';
            var postContent = '';
            
            // Try to get content from various editors
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                // Gutenberg
                postContent = wp.data.select('core/editor').getEditedPostContent();
            } else if ($('#content').length) {
                // Classic editor
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                    postContent = tinyMCE.get('content').getContent();
                } else {
                    postContent = $('#content').val();
                }
            }
            
            if (!postTitle && !postContent) {
                alert('Please add some content to translate.');
                return;
            }
            
            $btn.prop('disabled', true);
            $status.text(matTranslation.translating).addClass('mat-translating');
            
            $.ajax({
                url: matTranslation.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mat_translate_content',
                    nonce: matTranslation.nonce,
                    post_id: matTranslation.postId,
                    target_lang: lang,
                    title: postTitle,
                    content: postContent
                },
                success: function(response) {
                    if (response.success) {
                        // Fill in the fields
                        $pane.find('.mat-field-title').val(response.data.title || '');
                        $pane.find('.mat-field-content').val(response.data.content || '');
                        $pane.find('.mat-field-slug').val(response.data.slug || '');
                        $pane.find('.mat-field-excerpt').val(response.data.excerpt || '');
                        
                        // SEO fields
                        if (response.data.meta_title) {
                            $pane.find('.mat-field-meta-title').val(response.data.meta_title);
                        }
                        if (response.data.meta_description) {
                            $pane.find('.mat-field-meta-desc').val(response.data.meta_description);
                        }
                        
                        $status.text(matTranslation.translated).removeClass('mat-translating').addClass('mat-success');
                        
                        // Update tab badge
                        var $tabBtn = $('.mat-tab-btn[data-tab="' + lang + '"]');
                        $tabBtn.find('.mat-badge').removeClass('mat-badge-pending').addClass('mat-badge-translated').text('✓');
                        
                        setTimeout(function() {
                            $status.text('').removeClass('mat-success');
                        }, 3000);
                    } else {
                        $status.text(response.data || matTranslation.error).removeClass('mat-translating').addClass('mat-error');
                    }
                },
                error: function() {
                    $status.text(matTranslation.error).removeClass('mat-translating').addClass('mat-error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        translateAll: function(e) {
            e.preventDefault();
            var $btn = $(this);
            
            if (!confirm('This will translate content to all languages. Continue?')) {
                return;
            }
            
            var originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update mat-spin"></span> Translating...');
            
            // Get all non-default language tabs
            var $tabs = $('.mat-tab-pane').not(':first');
            var total = $tabs.length;
            var completed = 0;
            
            $tabs.each(function(index) {
                var $pane = $(this);
                var lang = $pane.data('lang');
                var $translateBtn = $pane.find('.mat-translate-btn');
                
                // Delay each request to avoid overwhelming the API
                setTimeout(function() {
                    $translateBtn.trigger('click');
                    completed++;
                    
                    if (completed >= total) {
                        setTimeout(function() {
                            $btn.prop('disabled', false).html(originalText);
                        }, 1000);
                    }
                }, index * 2000); // 2 second delay between each
            });
        },

        generateSlug: function() {
            var $input = $(this);
            var $pane = $input.closest('.mat-tab-pane');
            var $slug = $pane.find('.mat-field-slug');
            
            if (!$slug.val()) {
                var title = $input.val();
                var slug = title.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                $slug.val(slug);
            }
        },

        updateTitleCounter: function() {
            var len = $(this).val().length;
            var $counter = $(this).closest('.mat-field').find('.mat-char-count .mat-current');
            $counter.text(len);
            
            var $wrapper = $counter.closest('.mat-char-count');
            $wrapper.removeClass('mat-warning mat-error');
            if (len > 60) {
                $wrapper.addClass('mat-error');
            } else if (len > 50) {
                $wrapper.addClass('mat-warning');
            }
        },

        updateDescCounter: function() {
            var len = $(this).val().length;
            var $counter = $(this).closest('.mat-field').find('.mat-char-count .mat-current');
            $counter.text(len);
            
            var $wrapper = $counter.closest('.mat-char-count');
            $wrapper.removeClass('mat-warning mat-error');
            if (len > 160) {
                $wrapper.addClass('mat-error');
            } else if (len > 140) {
                $wrapper.addClass('mat-warning');
            }
        }
    };

    $(document).ready(function() {
        TranslationEditor.init();
    });

})(jQuery);
