<?php
/**
 * Translation Editor - Polylang-style translation management in post editor
 *
 * @package MultiLingual_AI_Translator
 * @since 2.02
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MAT_Translation_Editor {

    /**
     * Languages
     */
    private $languages;

    /**
     * Post types to translate
     */
    private $post_types = array( 'post', 'page', 'product' );

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_translations' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_mat_translate_content', array( $this, 'ajax_translate_content' ) );
        add_action( 'wp_ajax_mat_translate_all', array( $this, 'ajax_translate_all' ) );
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        $this->languages = MAT_Database_Handler::get_active_languages();
        
        if ( empty( $this->languages ) ) {
            return;
        }

        foreach ( $this->post_types as $post_type ) {
            add_meta_box(
                'mat_translations',
                __( '🌍 Translations', 'multilingual-ai-translator' ),
                array( $this, 'render_meta_box' ),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }

        global $post;
        if ( ! $post || ! in_array( $post->post_type, $this->post_types ) ) {
            return;
        }

        wp_enqueue_style(
            'mat-translation-editor',
            MAT_PLUGIN_URL . 'admin/css/translation-editor.css',
            array(),
            MAT_VERSION
        );

        wp_enqueue_script(
            'mat-translation-editor',
            MAT_PLUGIN_URL . 'admin/js/translation-editor.js',
            array( 'jquery' ),
            MAT_VERSION,
            true
        );

        wp_localize_script( 'mat-translation-editor', 'matTranslation', array(
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'mat_translate' ),
            'postId'      => $post->ID,
            'translating' => __( 'Translating...', 'multilingual-ai-translator' ),
            'translated'  => __( 'Translated!', 'multilingual-ai-translator' ),
            'error'       => __( 'Translation failed', 'multilingual-ai-translator' ),
        ) );
    }

    /**
     * Render meta box
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'mat_translations', 'mat_translations_nonce' );
        
        $languages = MAT_Database_Handler::get_active_languages();
        $default = MAT_Database_Handler::get_default_language();
        $default_code = $default ? $default['code'] : 'en';
        
        // Get existing translations
        $translations = array();
        foreach ( $languages as $lang ) {
            $translations[ $lang['code'] ] = MAT_Database_Handler::get_post_translation( $post->ID, $lang['code'] );
        }
        ?>
        <div class="mat-translation-editor">
            <!-- Toolbar -->
            <div class="mat-editor-toolbar">
                <button type="button" class="button mat-translate-all-btn">
                    <span class="dashicons dashicons-translation"></span>
                    <?php esc_html_e( 'Auto-Translate All Languages', 'multilingual-ai-translator' ); ?>
                </button>
                <span class="mat-toolbar-info">
                    <?php 
                    $api_settings = get_option( 'mat_api_settings', array() );
                    $api_key = isset( $api_settings['api_key'] ) ? $api_settings['api_key'] : get_option( 'mat_openrouter_api_key', '' );
                    if ( empty( $api_key ) ) {
                        echo '<span class="mat-warning">⚠️ ' . esc_html__( 'API key not configured. Go to Settings → API Settings.', 'multilingual-ai-translator' ) . '</span>';
                    } else {
                        echo '<span class="mat-success">✓ ' . esc_html__( 'API configured', 'multilingual-ai-translator' ) . '</span>';
                    }
                    ?>
                </span>
            </div>
            
            <!-- Language Tabs -->
            <div class="mat-lang-tabs">
                <nav class="mat-tabs-nav">
                    <?php foreach ( $languages as $i => $lang ) : 
                        $has_translation = ! empty( $translations[ $lang['code'] ]['translated_title'] );
                        $is_default = $lang['code'] === $default_code;
                        ?>
                        <button type="button" 
                                class="mat-tab-btn <?php echo $i === 0 ? 'mat-active' : ''; ?>"
                                data-tab="<?php echo esc_attr( $lang['code'] ); ?>">
                            <span class="mat-flag"><?php echo esc_html( $lang['flag'] ); ?></span>
                            <span class="mat-tab-name"><?php echo esc_html( $lang['native_name'] ); ?></span>
                            <?php if ( $is_default ) : ?>
                                <span class="mat-badge mat-badge-default"><?php esc_html_e( 'Default', 'multilingual-ai-translator' ); ?></span>
                            <?php elseif ( $has_translation ) : ?>
                                <span class="mat-badge mat-badge-translated">✓</span>
                            <?php else : ?>
                                <span class="mat-badge mat-badge-pending">○</span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </nav>
                
                <!-- Tab Content -->
                <div class="mat-tabs-content">
                    <?php foreach ( $languages as $i => $lang ) :
                        $trans = $translations[ $lang['code'] ];
                        $is_default = $lang['code'] === $default_code;
                        ?>
                        <div class="mat-tab-pane <?php echo $i === 0 ? 'mat-active' : ''; ?>" 
                             data-lang="<?php echo esc_attr( $lang['code'] ); ?>">
                            
                            <?php if ( $is_default ) : ?>
                                <div class="mat-default-notice">
                                    <p><?php esc_html_e( 'This is the default language. Content is pulled from the main post editor above.', 'multilingual-ai-translator' ); ?></p>
                                </div>
                            <?php else : ?>
                                <!-- Translate Button -->
                                <div class="mat-translate-bar">
                                    <button type="button" class="button button-primary mat-translate-btn" data-lang="<?php echo esc_attr( $lang['code'] ); ?>">
                                        <span class="dashicons dashicons-translation"></span>
                                        <?php printf( esc_html__( 'Auto-Translate to %s', 'multilingual-ai-translator' ), esc_html( $lang['native_name'] ) ); ?>
                                    </button>
                                    <span class="mat-translate-status"></span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Title -->
                            <div class="mat-field">
                                <label><?php esc_html_e( 'Title', 'multilingual-ai-translator' ); ?></label>
                                <?php if ( $is_default ) : ?>
                                    <input type="text" value="<?php echo esc_attr( $post->post_title ); ?>" disabled class="mat-input">
                                <?php else : ?>
                                    <input type="text" 
                                           name="mat_trans[<?php echo esc_attr( $lang['code'] ); ?>][title]"
                                           value="<?php echo esc_attr( $trans['translated_title'] ?? '' ); ?>"
                                           class="mat-input mat-field-title"
                                           placeholder="<?php esc_attr_e( 'Translated title...', 'multilingual-ai-translator' ); ?>">
                                <?php endif; ?>
                            </div>
                            
                            <!-- Slug -->
                            <div class="mat-field">
                                <label><?php esc_html_e( 'URL Slug', 'multilingual-ai-translator' ); ?></label>
                                <?php if ( $is_default ) : ?>
                                    <input type="text" value="<?php echo esc_attr( $post->post_name ); ?>" disabled class="mat-input">
                                <?php else : ?>
                                    <input type="text" 
                                           name="mat_trans[<?php echo esc_attr( $lang['code'] ); ?>][slug]"
                                           value="<?php echo esc_attr( $trans['translated_slug'] ?? '' ); ?>"
                                           class="mat-input mat-field-slug"
                                           placeholder="<?php esc_attr_e( 'translated-slug', 'multilingual-ai-translator' ); ?>">
                                    <p class="mat-hint"><?php printf( esc_html__( 'URL: %s', 'multilingual-ai-translator' ), '<code>' . home_url( '/' . $lang['code'] . '/<slug>/' ) . '</code>' ); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Content -->
                            <div class="mat-field">
                                <label><?php esc_html_e( 'Content', 'multilingual-ai-translator' ); ?></label>
                                <?php if ( $is_default ) : ?>
                                    <textarea disabled class="mat-textarea"><?php echo esc_textarea( $post->post_content ); ?></textarea>
                                <?php else : ?>
                                    <textarea name="mat_trans[<?php echo esc_attr( $lang['code'] ); ?>][content]"
                                              class="mat-textarea mat-field-content"
                                              rows="10"
                                              placeholder="<?php esc_attr_e( 'Translated content...', 'multilingual-ai-translator' ); ?>"><?php echo esc_textarea( $trans['translated_content'] ?? '' ); ?></textarea>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Excerpt -->
                            <div class="mat-field">
                                <label><?php esc_html_e( 'Excerpt', 'multilingual-ai-translator' ); ?></label>
                                <?php if ( $is_default ) : ?>
                                    <textarea disabled class="mat-textarea-small"><?php echo esc_textarea( $post->post_excerpt ); ?></textarea>
                                <?php else : ?>
                                    <textarea name="mat_trans[<?php echo esc_attr( $lang['code'] ); ?>][excerpt]"
                                              class="mat-textarea-small mat-field-excerpt"
                                              rows="3"
                                              placeholder="<?php esc_attr_e( 'Translated excerpt...', 'multilingual-ai-translator' ); ?>"><?php echo esc_textarea( $trans['translated_excerpt'] ?? '' ); ?></textarea>
                                <?php endif; ?>
                            </div>
                            
                            <!-- SEO Section -->
                            <div class="mat-seo-section">
                                <h4><?php esc_html_e( 'SEO Settings', 'multilingual-ai-translator' ); ?></h4>
                                
                                <!-- Meta Title -->
                                <div class="mat-field">
                                    <label>
                                        <?php esc_html_e( 'Meta Title', 'multilingual-ai-translator' ); ?>
                                        <span class="mat-char-count"><span class="mat-current">0</span>/60</span>
                                    </label>
                                    <?php if ( $is_default ) : ?>
                                        <input type="text" value="<?php echo esc_attr( $post->post_title ); ?>" disabled class="mat-input">
                                    <?php else : ?>
                                        <input type="text" 
                                               name="mat_trans[<?php echo esc_attr( $lang['code'] ); ?>][meta_title]"
                                               value="<?php echo esc_attr( $trans['meta_title'] ?? '' ); ?>"
                                               class="mat-input mat-field-meta-title mat-count-input"
                                               maxlength="70"
                                               placeholder="<?php esc_attr_e( 'SEO title for search engines...', 'multilingual-ai-translator' ); ?>">
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Meta Description -->
                                <div class="mat-field">
                                    <label>
                                        <?php esc_html_e( 'Meta Description', 'multilingual-ai-translator' ); ?>
                                        <span class="mat-char-count"><span class="mat-current">0</span>/160</span>
                                    </label>
                                    <?php if ( $is_default ) : ?>
                                        <textarea disabled class="mat-textarea-small"><?php echo esc_textarea( wp_trim_words( $post->post_content, 30 ) ); ?></textarea>
                                    <?php else : ?>
                                        <textarea name="mat_trans[<?php echo esc_attr( $lang['code'] ); ?>][meta_description]"
                                                  class="mat-textarea-small mat-field-meta-description mat-count-input"
                                                  rows="2"
                                                  maxlength="200"
                                                  placeholder="<?php esc_attr_e( 'Meta description for search engines...', 'multilingual-ai-translator' ); ?>"><?php echo esc_textarea( $trans['meta_description'] ?? '' ); ?></textarea>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Meta Keywords -->
                                <div class="mat-field">
                                    <label><?php esc_html_e( 'Focus Keywords', 'multilingual-ai-translator' ); ?></label>
                                    <?php if ( ! $is_default ) : ?>
                                        <input type="text" 
                                               name="mat_trans[<?php echo esc_attr( $lang['code'] ); ?>][meta_keywords]"
                                               value="<?php echo esc_attr( $trans['meta_keywords'] ?? '' ); ?>"
                                               class="mat-input mat-field-keywords"
                                               placeholder="<?php esc_attr_e( 'keyword1, keyword2, keyword3', 'multilingual-ai-translator' ); ?>">
                                        <p class="mat-hint"><?php esc_html_e( 'Separate keywords with commas', 'multilingual-ai-translator' ); ?></p>
                                    <?php else : ?>
                                        <input type="text" disabled class="mat-input" placeholder="<?php esc_attr_e( 'Set keywords in translated languages', 'multilingual-ai-translator' ); ?>">
                                    <?php endif; ?>
                                </div>
                                
                                <!-- OG Title -->
                                <div class="mat-field">
                                    <label><?php esc_html_e( 'Open Graph Title', 'multilingual-ai-translator' ); ?> <span class="mat-optional">(optional)</span></label>
                                    <?php if ( ! $is_default ) : ?>
                                        <input type="text" 
                                               name="mat_trans[<?php echo esc_attr( $lang['code'] ); ?>][og_title]"
                                               value="<?php echo esc_attr( $trans['og_title'] ?? '' ); ?>"
                                               class="mat-input"
                                               placeholder="<?php esc_attr_e( 'Social media title (defaults to meta title)', 'multilingual-ai-translator' ); ?>">
                                    <?php else : ?>
                                        <input type="text" disabled class="mat-input">
                                    <?php endif; ?>
                                </div>
                                
                                <!-- OG Description -->
                                <div class="mat-field">
                                    <label><?php esc_html_e( 'Open Graph Description', 'multilingual-ai-translator' ); ?> <span class="mat-optional">(optional)</span></label>
                                    <?php if ( ! $is_default ) : ?>
                                        <textarea name="mat_trans[<?php echo esc_attr( $lang['code'] ); ?>][og_description]"
                                                  class="mat-textarea-small"
                                                  rows="2"
                                                  placeholder="<?php esc_attr_e( 'Social media description', 'multilingual-ai-translator' ); ?>"><?php echo esc_textarea( $trans['og_description'] ?? '' ); ?></textarea>
                                    <?php else : ?>
                                        <textarea disabled class="mat-textarea-small"></textarea>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ( ! $is_default && ! empty( $trans['updated_at'] ) ) : ?>
                                <div class="mat-translation-meta">
                                    <small>
                                        <?php printf( 
                                            esc_html__( 'Last updated: %s', 'multilingual-ai-translator' ),
                                            date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $trans['updated_at'] ) )
                                        ); ?>
                                        <?php if ( ! empty( $trans['ai_model'] ) ) : ?>
                                            | <?php printf( esc_html__( 'Model: %s', 'multilingual-ai-translator' ), esc_html( $trans['ai_model'] ) ); ?>
                                        <?php endif; ?>
                                        | <?php printf( esc_html__( 'Status: %s', 'multilingual-ai-translator' ), esc_html( ucfirst( $trans['translation_status'] ?? 'pending' ) ) ); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save translations
     */
    public function save_translations( $post_id, $post ) {
        if ( ! isset( $_POST['mat_translations_nonce'] ) || ! wp_verify_nonce( $_POST['mat_translations_nonce'], 'mat_translations' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( ! in_array( $post->post_type, $this->post_types ) ) {
            return;
        }

        if ( ! isset( $_POST['mat_trans'] ) || ! is_array( $_POST['mat_trans'] ) ) {
            return;
        }

        foreach ( $_POST['mat_trans'] as $lang_code => $data ) {
            // Skip if all fields empty
            $has_data = false;
            foreach ( $data as $value ) {
                if ( ! empty( $value ) ) {
                    $has_data = true;
                    break;
                }
            }
            
            if ( ! $has_data ) {
                continue;
            }

            $data['status'] = 'edited';
            MAT_Database_Handler::save_post_translation( $post_id, sanitize_key( $lang_code ), $data );
        }
    }

    /**
     * AJAX: Translate content
     */
    public function ajax_translate_content() {
        check_ajax_referer( 'mat_translate', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'multilingual-ai-translator' ) ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $target_lang = isset( $_POST['target_lang'] ) ? sanitize_key( $_POST['target_lang'] ) : '';

        if ( ! $post_id || ! $target_lang ) {
            wp_send_json_error( array( 'message' => __( 'Missing parameters', 'multilingual-ai-translator' ) ) );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found', 'multilingual-ai-translator' ) ) );
        }

        // Get default language
        $default = MAT_Database_Handler::get_default_language();
        $source_lang = $default ? $default['code'] : 'en';

        // Initialize API
        $api = new MAT_OpenRouter_API();
        
        if ( ! $api->is_configured() ) {
            wp_send_json_error( array( 'message' => __( 'API not configured. Please add your OpenRouter API key in settings.', 'multilingual-ai-translator' ) ) );
        }

        // Translate title
        $title_result = $api->translate( $post->post_title, $source_lang, $target_lang );
        $translated_title = is_wp_error( $title_result ) ? $post->post_title : $title_result['translation'];

        // Translate content
        $content_result = $api->translate_html( $post->post_content, $source_lang, $target_lang );
        $translated_content = is_wp_error( $content_result ) ? $post->post_content : $content_result['translation'];

        // Translate excerpt
        $translated_excerpt = '';
        if ( ! empty( $post->post_excerpt ) ) {
            $excerpt_result = $api->translate( $post->post_excerpt, $source_lang, $target_lang );
            $translated_excerpt = is_wp_error( $excerpt_result ) ? $post->post_excerpt : $excerpt_result['translation'];
        }

        // Generate slug
        $translated_slug = sanitize_title( $translated_title );

        // Generate meta title and description
        $meta_title = wp_trim_words( $translated_title, 10, '' );
        $meta_description = wp_trim_words( strip_tags( $translated_content ), 25, '...' );

        // Get API model
        $api_settings = get_option( 'mat_api_settings', array() );
        $model = isset( $api_settings['model'] ) ? $api_settings['model'] : get_option( 'mat_ai_model', 'anthropic/claude-3.5-sonnet' );

        // Save translation
        $data = array(
            'title'            => $translated_title,
            'content'          => $translated_content,
            'excerpt'          => $translated_excerpt,
            'slug'             => $translated_slug,
            'meta_title'       => $meta_title,
            'meta_description' => $meta_description,
            'status'           => 'auto',
            'ai_model'         => $model,
        );

        MAT_Database_Handler::save_post_translation( $post_id, $target_lang, $data );

        wp_send_json_success( array(
            'message'     => __( 'Translation completed!', 'multilingual-ai-translator' ),
            'translation' => $data,
        ) );
    }

    /**
     * AJAX: Translate all languages
     */
    public function ajax_translate_all() {
        check_ajax_referer( 'mat_translate', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'multilingual-ai-translator' ) ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing post ID', 'multilingual-ai-translator' ) ) );
        }

        $languages = MAT_Database_Handler::get_active_languages();
        $default = MAT_Database_Handler::get_default_language();
        $default_code = $default ? $default['code'] : 'en';

        $results = array();
        $errors = array();

        foreach ( $languages as $lang ) {
            if ( $lang['code'] === $default_code ) {
                continue;
            }

            // Add to queue for background processing
            MAT_Database_Handler::add_to_queue( $post_id, $lang['code'], 5 );
            $results[] = $lang['code'];
        }

        // Process first item immediately
        $this->process_queue_item();

        wp_send_json_success( array(
            'message'   => sprintf( __( 'Queued %d languages for translation', 'multilingual-ai-translator' ), count( $results ) ),
            'languages' => $results,
        ) );
    }

    /**
     * Process one queue item
     */
    private function process_queue_item() {
        $item = MAT_Database_Handler::get_next_queue_item();
        
        if ( ! $item ) {
            return false;
        }

        MAT_Database_Handler::update_queue_status( $item['id'], 'processing' );

        $post = get_post( $item['post_id'] );
        if ( ! $post ) {
            MAT_Database_Handler::update_queue_status( $item['id'], 'failed', 'Post not found' );
            return false;
        }

        $default = MAT_Database_Handler::get_default_language();
        $source_lang = $default ? $default['code'] : 'en';
        $target_lang = $item['language_code'];

        $api = new MAT_OpenRouter_API();
        
        if ( ! $api->is_configured() ) {
            MAT_Database_Handler::update_queue_status( $item['id'], 'failed', 'API not configured' );
            return false;
        }

        // Translate
        $title_result = $api->translate( $post->post_title, $source_lang, $target_lang );
        $content_result = $api->translate_html( $post->post_content, $source_lang, $target_lang );

        if ( is_wp_error( $title_result ) || is_wp_error( $content_result ) ) {
            $error = is_wp_error( $title_result ) ? $title_result->get_error_message() : $content_result->get_error_message();
            MAT_Database_Handler::update_queue_status( $item['id'], 'failed', $error );
            return false;
        }

        $api_settings = get_option( 'mat_api_settings', array() );
        $model = isset( $api_settings['model'] ) ? $api_settings['model'] : get_option( 'mat_ai_model', 'anthropic/claude-3.5-sonnet' );
        
        $data = array(
            'title'            => $title_result['translation'],
            'content'          => $content_result['translation'],
            'slug'             => sanitize_title( $title_result['translation'] ),
            'meta_title'       => wp_trim_words( $title_result['translation'], 10, '' ),
            'meta_description' => wp_trim_words( strip_tags( $content_result['translation'] ), 25, '...' ),
            'status'           => 'auto',
            'ai_model'         => $model,
        );

        MAT_Database_Handler::save_post_translation( $item['post_id'], $target_lang, $data );
        MAT_Database_Handler::update_queue_status( $item['id'], 'completed' );

        return true;
    }
}
