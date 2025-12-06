<?php
/**
 * SEO Metabox Handler
 *
 * Provides per-page, per-language SEO keywords/meta fields in the post editor.
 *
 * @package MultiLingual_AI_Translator
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class MAT_SEO_Metabox
 *
 * Adds SEO meta fields (keywords, title, description) for each language
 */
class MAT_SEO_Metabox {

    /**
     * Available languages
     *
     * @var array
     */
    private $languages;

    /**
     * Supported post types
     *
     * @var array
     */
    private $post_types;

    /**
     * Constructor
     */
    public function __construct() {
        $this->post_types = apply_filters( 'mat_seo_post_types', array( 'post', 'page', 'product' ) );
        $this->load_languages();
        $this->init_hooks();
    }

    /**
     * Load active languages from database
     */
    private function load_languages() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mat_languages';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
            $this->languages = array();
            return;
        }
        
        $this->languages = $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE is_active = 1 ORDER BY sort_order ASC",
            ARRAY_A
        );
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add metabox to supported post types
        add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
        
        // Save metabox data
        add_action( 'save_post', array( $this, 'save_metabox' ), 10, 2 );
        
        // Enqueue metabox assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        
        // Output SEO meta tags in frontend
        add_action( 'wp_head', array( $this, 'output_seo_meta' ), 1 );
        
        // Filter document title
        add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ), 20 );
        add_filter( 'document_title_parts', array( $this, 'filter_title_parts' ), 20 );
    }

    /**
     * Add the SEO metabox
     */
    public function add_metabox() {
        if ( empty( $this->languages ) ) {
            return;
        }

        foreach ( $this->post_types as $post_type ) {
            add_meta_box(
                'mat_seo_metabox',
                __( '🌍 Multilingual SEO Keywords', 'multilingual-ai-translator' ),
                array( $this, 'render_metabox' ),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render the metabox content
     *
     * @param WP_Post $post Current post object
     */
    public function render_metabox( $post ) {
        // Nonce for security
        wp_nonce_field( 'mat_seo_metabox', 'mat_seo_nonce' );
        
        // Get saved SEO data
        $seo_data = $this->get_seo_data( $post->ID );
        ?>
        <div class="mat-seo-metabox">
            <p class="mat-seo-description">
                <?php esc_html_e( 'Set unique SEO meta data for each language. This allows you to optimize your content for different markets with language-specific keywords.', 'multilingual-ai-translator' ); ?>
            </p>
            
            <div class="mat-seo-tabs">
                <nav class="mat-seo-tabs-nav">
                    <?php foreach ( $this->languages as $index => $lang ) : ?>
                        <button type="button" 
                                class="mat-seo-tab-btn <?php echo $index === 0 ? 'mat-active' : ''; ?>" 
                                data-tab="mat-seo-tab-<?php echo esc_attr( $lang['code'] ); ?>">
                            <?php if ( ! empty( $lang['flag'] ) ) : ?>
                                <span class="mat-flag"><?php echo esc_html( $lang['flag'] ); ?></span>
                            <?php endif; ?>
                            <span class="mat-lang-name"><?php echo esc_html( $lang['native_name'] ); ?></span>
                            <?php if ( ! empty( $lang['is_default'] ) ) : ?>
                                <span class="mat-default-badge"><?php esc_html_e( 'Default', 'multilingual-ai-translator' ); ?></span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </nav>
                
                <div class="mat-seo-tabs-content">
                    <?php foreach ( $this->languages as $index => $lang ) : 
                        $lang_data = isset( $seo_data[ $lang['code'] ] ) ? $seo_data[ $lang['code'] ] : array();
                        ?>
                        <div class="mat-seo-tab-pane <?php echo $index === 0 ? 'mat-active' : ''; ?>" 
                             id="mat-seo-tab-<?php echo esc_attr( $lang['code'] ); ?>">
                            
                            <div class="mat-seo-field">
                                <label for="mat_seo_title_<?php echo esc_attr( $lang['code'] ); ?>">
                                    <?php esc_html_e( 'SEO Title', 'multilingual-ai-translator' ); ?>
                                    <span class="mat-char-count">
                                        <span class="mat-current">0</span>/60
                                    </span>
                                </label>
                                <input type="text" 
                                       id="mat_seo_title_<?php echo esc_attr( $lang['code'] ); ?>"
                                       name="mat_seo[<?php echo esc_attr( $lang['code'] ); ?>][title]"
                                       value="<?php echo esc_attr( isset( $lang_data['title'] ) ? $lang_data['title'] : '' ); ?>"
                                       class="mat-seo-input mat-title-input"
                                       maxlength="70"
                                       placeholder="<?php esc_attr_e( 'Enter SEO title for this language...', 'multilingual-ai-translator' ); ?>">
                                <p class="mat-field-hint">
                                    <?php esc_html_e( 'Optimal length: 50-60 characters. This will be used as the page title in search results.', 'multilingual-ai-translator' ); ?>
                                </p>
                            </div>
                            
                            <div class="mat-seo-field">
                                <label for="mat_seo_description_<?php echo esc_attr( $lang['code'] ); ?>">
                                    <?php esc_html_e( 'Meta Description', 'multilingual-ai-translator' ); ?>
                                    <span class="mat-char-count">
                                        <span class="mat-current">0</span>/160
                                    </span>
                                </label>
                                <textarea id="mat_seo_description_<?php echo esc_attr( $lang['code'] ); ?>"
                                          name="mat_seo[<?php echo esc_attr( $lang['code'] ); ?>][description]"
                                          class="mat-seo-textarea mat-description-input"
                                          rows="3"
                                          maxlength="200"
                                          placeholder="<?php esc_attr_e( 'Enter meta description for this language...', 'multilingual-ai-translator' ); ?>"><?php echo esc_textarea( isset( $lang_data['description'] ) ? $lang_data['description'] : '' ); ?></textarea>
                                <p class="mat-field-hint">
                                    <?php esc_html_e( 'Optimal length: 120-160 characters. This appears below the title in search results.', 'multilingual-ai-translator' ); ?>
                                </p>
                            </div>
                            
                            <div class="mat-seo-field">
                                <label for="mat_seo_keywords_<?php echo esc_attr( $lang['code'] ); ?>">
                                    <?php esc_html_e( 'Focus Keywords', 'multilingual-ai-translator' ); ?>
                                </label>
                                <input type="text" 
                                       id="mat_seo_keywords_<?php echo esc_attr( $lang['code'] ); ?>"
                                       name="mat_seo[<?php echo esc_attr( $lang['code'] ); ?>][keywords]"
                                       value="<?php echo esc_attr( isset( $lang_data['keywords'] ) ? $lang_data['keywords'] : '' ); ?>"
                                       class="mat-seo-input mat-keywords-input"
                                       placeholder="<?php esc_attr_e( 'keyword1, keyword2, keyword3...', 'multilingual-ai-translator' ); ?>">
                                <p class="mat-field-hint">
                                    <?php esc_html_e( 'Separate keywords with commas. These help with content optimization and can be used for translation context.', 'multilingual-ai-translator' ); ?>
                                </p>
                            </div>
                            
                            <div class="mat-seo-field">
                                <label for="mat_seo_og_title_<?php echo esc_attr( $lang['code'] ); ?>">
                                    <?php esc_html_e( 'Open Graph Title', 'multilingual-ai-translator' ); ?>
                                    <span class="mat-optional"><?php esc_html_e( '(optional)', 'multilingual-ai-translator' ); ?></span>
                                </label>
                                <input type="text" 
                                       id="mat_seo_og_title_<?php echo esc_attr( $lang['code'] ); ?>"
                                       name="mat_seo[<?php echo esc_attr( $lang['code'] ); ?>][og_title]"
                                       value="<?php echo esc_attr( isset( $lang_data['og_title'] ) ? $lang_data['og_title'] : '' ); ?>"
                                       class="mat-seo-input"
                                       placeholder="<?php esc_attr_e( 'Social media share title (defaults to SEO title)', 'multilingual-ai-translator' ); ?>">
                            </div>
                            
                            <div class="mat-seo-field">
                                <label for="mat_seo_og_description_<?php echo esc_attr( $lang['code'] ); ?>">
                                    <?php esc_html_e( 'Open Graph Description', 'multilingual-ai-translator' ); ?>
                                    <span class="mat-optional"><?php esc_html_e( '(optional)', 'multilingual-ai-translator' ); ?></span>
                                </label>
                                <textarea id="mat_seo_og_description_<?php echo esc_attr( $lang['code'] ); ?>"
                                          name="mat_seo[<?php echo esc_attr( $lang['code'] ); ?>][og_description]"
                                          class="mat-seo-textarea"
                                          rows="2"
                                          placeholder="<?php esc_attr_e( 'Social media share description (defaults to meta description)', 'multilingual-ai-translator' ); ?>"><?php echo esc_textarea( isset( $lang_data['og_description'] ) ? $lang_data['og_description'] : '' ); ?></textarea>
                            </div>
                            
                            <!-- Search Preview -->
                            <div class="mat-seo-preview">
                                <h4><?php esc_html_e( 'Search Preview', 'multilingual-ai-translator' ); ?></h4>
                                <div class="mat-preview-card">
                                    <div class="mat-preview-url"><?php echo esc_url( get_permalink( $post->ID ) ); ?></div>
                                    <div class="mat-preview-title">
                                        <?php echo ! empty( $lang_data['title'] ) ? esc_html( $lang_data['title'] ) : esc_html( get_the_title( $post->ID ) ); ?>
                                    </div>
                                    <div class="mat-preview-description">
                                        <?php 
                                        if ( ! empty( $lang_data['description'] ) ) {
                                            echo esc_html( $lang_data['description'] );
                                        } else {
                                            echo esc_html( wp_trim_words( get_the_excerpt( $post->ID ), 20 ) );
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <style>
            .mat-seo-metabox {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .mat-seo-description {
                color: #666;
                margin-bottom: 20px;
                padding: 12px 16px;
                background: #f0f6fc;
                border-left: 4px solid #4f46e5;
                border-radius: 4px;
            }
            .mat-seo-tabs-nav {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                border-bottom: 2px solid #e5e7eb;
                padding-bottom: 0;
                margin-bottom: 20px;
            }
            .mat-seo-tab-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 10px 16px;
                background: transparent;
                border: none;
                border-bottom: 2px solid transparent;
                margin-bottom: -2px;
                cursor: pointer;
                font-size: 14px;
                color: #6b7280;
                transition: all 0.2s;
            }
            .mat-seo-tab-btn:hover {
                color: #4f46e5;
                background: #f9fafb;
            }
            .mat-seo-tab-btn.mat-active {
                color: #4f46e5;
                border-bottom-color: #4f46e5;
                font-weight: 500;
            }
            .mat-seo-tab-btn .mat-flag {
                font-size: 1.2em;
            }
            .mat-default-badge {
                font-size: 10px;
                padding: 2px 6px;
                background: #dcfce7;
                color: #166534;
                border-radius: 9999px;
                font-weight: 500;
            }
            .mat-seo-tab-pane {
                display: none;
            }
            .mat-seo-tab-pane.mat-active {
                display: block;
            }
            .mat-seo-field {
                margin-bottom: 20px;
            }
            .mat-seo-field label {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 6px;
                font-weight: 500;
                color: #374151;
            }
            .mat-char-count {
                font-size: 12px;
                font-weight: normal;
                color: #9ca3af;
            }
            .mat-char-count .mat-current {
                font-weight: 500;
            }
            .mat-char-count.mat-warning .mat-current {
                color: #f59e0b;
            }
            .mat-char-count.mat-danger .mat-current {
                color: #ef4444;
            }
            .mat-optional {
                font-weight: normal;
                color: #9ca3af;
                font-size: 12px;
            }
            .mat-seo-input,
            .mat-seo-textarea {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                font-size: 14px;
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            .mat-seo-input:focus,
            .mat-seo-textarea:focus {
                outline: none;
                border-color: #4f46e5;
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            }
            .mat-field-hint {
                margin: 6px 0 0;
                font-size: 12px;
                color: #9ca3af;
            }
            .mat-seo-preview {
                margin-top: 24px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
            }
            .mat-seo-preview h4 {
                margin: 0 0 12px;
                font-size: 14px;
                color: #374151;
            }
            .mat-preview-card {
                padding: 16px;
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                max-width: 600px;
            }
            .mat-preview-url {
                font-size: 12px;
                color: #188038;
                margin-bottom: 4px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .mat-preview-title {
                font-size: 18px;
                color: #1a0dab;
                line-height: 1.3;
                margin-bottom: 4px;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .mat-preview-description {
                font-size: 13px;
                color: #545454;
                line-height: 1.5;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.mat-seo-tab-btn').on('click', function() {
                const tabId = $(this).data('tab');
                
                $('.mat-seo-tab-btn').removeClass('mat-active');
                $(this).addClass('mat-active');
                
                $('.mat-seo-tab-pane').removeClass('mat-active');
                $('#' + tabId).addClass('mat-active');
            });
            
            // Character counter
            function updateCharCount($input) {
                const $counter = $input.closest('.mat-seo-field').find('.mat-char-count');
                if ($counter.length === 0) return;
                
                const length = $input.val().length;
                const $current = $counter.find('.mat-current');
                $current.text(length);
                
                // Get max from label
                const max = parseInt($counter.text().split('/')[1]);
                const optimal = max * 0.8;
                
                $counter.removeClass('mat-warning mat-danger');
                if (length > max) {
                    $counter.addClass('mat-danger');
                } else if (length > optimal) {
                    $counter.addClass('mat-warning');
                }
            }
            
            // Live preview update
            function updatePreview($pane) {
                const title = $pane.find('.mat-title-input').val() || '<?php echo esc_js( get_the_title( $post->ID ) ); ?>';
                const desc = $pane.find('.mat-description-input').val() || '<?php echo esc_js( wp_trim_words( get_the_excerpt( $post->ID ), 20 ) ); ?>';
                
                $pane.find('.mat-preview-title').text(title);
                $pane.find('.mat-preview-description').text(desc);
            }
            
            // Init counters
            $('.mat-title-input, .mat-description-input').each(function() {
                updateCharCount($(this));
            });
            
            // Bind input events
            $('.mat-title-input, .mat-description-input').on('input', function() {
                updateCharCount($(this));
                updatePreview($(this).closest('.mat-seo-tab-pane'));
            });
        });
        </script>
        <?php
    }

    /**
     * Save metabox data
     *
     * @param int     $post_id Post ID
     * @param WP_Post $post    Post object
     */
    public function save_metabox( $post_id, $post ) {
        // Security checks
        if ( ! isset( $_POST['mat_seo_nonce'] ) || ! wp_verify_nonce( $_POST['mat_seo_nonce'], 'mat_seo_metabox' ) ) {
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
        
        // Save SEO data
        if ( isset( $_POST['mat_seo'] ) && is_array( $_POST['mat_seo'] ) ) {
            $seo_data = array();
            
            foreach ( $_POST['mat_seo'] as $lang_code => $fields ) {
                $lang_code = sanitize_key( $lang_code );
                
                $seo_data[ $lang_code ] = array(
                    'title'          => sanitize_text_field( $fields['title'] ?? '' ),
                    'description'    => sanitize_textarea_field( $fields['description'] ?? '' ),
                    'keywords'       => sanitize_text_field( $fields['keywords'] ?? '' ),
                    'og_title'       => sanitize_text_field( $fields['og_title'] ?? '' ),
                    'og_description' => sanitize_textarea_field( $fields['og_description'] ?? '' ),
                );
            }
            
            update_post_meta( $post_id, '_mat_seo_data', $seo_data );
            
            // Also save to custom table for advanced queries
            $this->save_to_database( $post_id, $seo_data );
        }
    }

    /**
     * Get SEO data for a post
     *
     * @param int $post_id Post ID
     * @return array
     */
    private function get_seo_data( $post_id ) {
        $data = get_post_meta( $post_id, '_mat_seo_data', true );
        return is_array( $data ) ? $data : array();
    }

    /**
     * Save SEO data to database table
     *
     * @param int   $post_id  Post ID
     * @param array $seo_data SEO data array
     */
    private function save_to_database( $post_id, $seo_data ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mat_seo_meta';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
            return;
        }
        
        foreach ( $seo_data as $lang_code => $fields ) {
            // Check if record exists
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE post_id = %d AND language_code = %s",
                $post_id,
                $lang_code
            ) );
            
            if ( $existing ) {
                // Update
                $wpdb->update(
                    $table_name,
                    array(
                        'meta_title'       => $fields['title'],
                        'meta_description' => $fields['description'],
                        'meta_keywords'    => $fields['keywords'],
                        'og_title'         => $fields['og_title'],
                        'og_description'   => $fields['og_description'],
                        'updated_at'       => current_time( 'mysql' ),
                    ),
                    array(
                        'post_id'       => $post_id,
                        'language_code' => $lang_code,
                    ),
                    array( '%s', '%s', '%s', '%s', '%s', '%s' ),
                    array( '%d', '%s' )
                );
            } else {
                // Insert
                $wpdb->insert(
                    $table_name,
                    array(
                        'post_id'          => $post_id,
                        'language_code'    => $lang_code,
                        'meta_title'       => $fields['title'],
                        'meta_description' => $fields['description'],
                        'meta_keywords'    => $fields['keywords'],
                        'og_title'         => $fields['og_title'],
                        'og_description'   => $fields['og_description'],
                        'created_at'       => current_time( 'mysql' ),
                        'updated_at'       => current_time( 'mysql' ),
                    ),
                    array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
                );
            }
        }
    }

    /**
     * Enqueue metabox assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }
        
        global $post_type;
        if ( ! in_array( $post_type, $this->post_types ) ) {
            return;
        }
        
        // Assets are inline in the metabox for simplicity
    }

    /**
     * Output SEO meta tags in frontend
     */
    public function output_seo_meta() {
        if ( ! is_singular() ) {
            return;
        }
        
        global $post;
        
        // Get current language
        $current_lang = $this->get_current_language();
        
        // Get SEO data
        $seo_data = $this->get_seo_data( $post->ID );
        
        if ( empty( $seo_data[ $current_lang ] ) ) {
            return;
        }
        
        $meta = $seo_data[ $current_lang ];
        
        // Meta description
        if ( ! empty( $meta['description'] ) ) {
            echo '<meta name="description" content="' . esc_attr( $meta['description'] ) . '">' . "\n";
        }
        
        // Meta keywords
        if ( ! empty( $meta['keywords'] ) ) {
            echo '<meta name="keywords" content="' . esc_attr( $meta['keywords'] ) . '">' . "\n";
        }
        
        // Open Graph tags
        $og_title = ! empty( $meta['og_title'] ) ? $meta['og_title'] : $meta['title'];
        $og_description = ! empty( $meta['og_description'] ) ? $meta['og_description'] : $meta['description'];
        
        if ( ! empty( $og_title ) ) {
            echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
        }
        
        if ( ! empty( $og_description ) ) {
            echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '">' . "\n";
        }
        
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_permalink( $post->ID ) ) . '">' . "\n";
        echo '<meta property="og:locale" content="' . esc_attr( $this->get_locale_for_language( $current_lang ) ) . '">' . "\n";
        
        // Twitter Card
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        if ( ! empty( $og_title ) ) {
            echo '<meta name="twitter:title" content="' . esc_attr( $og_title ) . '">' . "\n";
        }
        if ( ! empty( $og_description ) ) {
            echo '<meta name="twitter:description" content="' . esc_attr( $og_description ) . '">' . "\n";
        }
        
        // Alternate language links (hreflang)
        $this->output_hreflang_tags( $post->ID );
    }

    /**
     * Output hreflang tags for alternate languages
     *
     * @param int $post_id Post ID
     */
    private function output_hreflang_tags( $post_id ) {
        if ( empty( $this->languages ) ) {
            return;
        }
        
        $permalink = get_permalink( $post_id );
        
        foreach ( $this->languages as $lang ) {
            $lang_url = add_query_arg( 'lang', $lang['code'], $permalink );
            $locale = $this->get_locale_for_language( $lang['code'] );
            
            echo '<link rel="alternate" hreflang="' . esc_attr( $lang['code'] ) . '" href="' . esc_url( $lang_url ) . '">' . "\n";
        }
        
        // x-default
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $permalink ) . '">' . "\n";
    }

    /**
     * Filter document title
     *
     * @param string $title Current title
     * @return string
     */
    public function filter_document_title( $title ) {
        if ( ! is_singular() ) {
            return $title;
        }
        
        global $post;
        
        $current_lang = $this->get_current_language();
        $seo_data = $this->get_seo_data( $post->ID );
        
        if ( ! empty( $seo_data[ $current_lang ]['title'] ) ) {
            return $seo_data[ $current_lang ]['title'];
        }
        
        return $title;
    }

    /**
     * Filter title parts
     *
     * @param array $title_parts Title parts array
     * @return array
     */
    public function filter_title_parts( $title_parts ) {
        if ( ! is_singular() ) {
            return $title_parts;
        }
        
        global $post;
        
        $current_lang = $this->get_current_language();
        $seo_data = $this->get_seo_data( $post->ID );
        
        if ( ! empty( $seo_data[ $current_lang ]['title'] ) ) {
            $title_parts['title'] = $seo_data[ $current_lang ]['title'];
        }
        
        return $title_parts;
    }

    /**
     * Get current language
     *
     * @return string
     */
    private function get_current_language() {
        // Check URL parameter
        if ( isset( $_GET['lang'] ) ) {
            return sanitize_text_field( $_GET['lang'] );
        }
        
        // Check cookie
        if ( isset( $_COOKIE['mat_language'] ) ) {
            return sanitize_text_field( $_COOKIE['mat_language'] );
        }
        
        // Return default
        foreach ( $this->languages as $lang ) {
            if ( ! empty( $lang['is_default'] ) ) {
                return $lang['code'];
            }
        }
        
        return 'en';
    }

    /**
     * Get locale for language code
     *
     * @param string $lang_code Language code
     * @return string
     */
    private function get_locale_for_language( $lang_code ) {
        $locales = array(
            'en' => 'en_US',
            'de' => 'de_DE',
            'fr' => 'fr_FR',
            'es' => 'es_ES',
            'it' => 'it_IT',
            'pt' => 'pt_PT',
            'nl' => 'nl_NL',
            'pl' => 'pl_PL',
            'sv' => 'sv_SE',
            'da' => 'da_DK',
            'fi' => 'fi_FI',
            'no' => 'nb_NO',
            'cs' => 'cs_CZ',
            'sk' => 'sk_SK',
            'hu' => 'hu_HU',
            'ro' => 'ro_RO',
            'bg' => 'bg_BG',
            'el' => 'el_GR',
            'hr' => 'hr_HR',
            'sl' => 'sl_SI',
            'et' => 'et_EE',
            'lv' => 'lv_LV',
            'lt' => 'lt_LT',
            'mt' => 'mt_MT',
            'ga' => 'ga_IE',
        );
        
        return isset( $locales[ $lang_code ] ) ? $locales[ $lang_code ] : $lang_code . '_' . strtoupper( $lang_code );
    }

    /**
     * Get SEO data for a specific post and language (public method)
     *
     * @param int    $post_id   Post ID
     * @param string $lang_code Language code
     * @return array
     */
    public function get_post_seo( $post_id, $lang_code = null ) {
        if ( is_null( $lang_code ) ) {
            $lang_code = $this->get_current_language();
        }
        
        $seo_data = $this->get_seo_data( $post_id );
        
        return isset( $seo_data[ $lang_code ] ) ? $seo_data[ $lang_code ] : array();
    }
}
