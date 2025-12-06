<?php
/**
 * Language Switcher Handler
 *
 * Handles language switcher display on frontend including menu integration,
 * shortcode rendering, and floating widget.
 *
 * @package MultiLingual_AI_Translator
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class MAT_Language_Switcher
 *
 * Manages all language switching functionality on the frontend
 */
class MAT_Language_Switcher {

    /**
     * Switcher settings
     *
     * @var array
     */
    private $settings;

    /**
     * Available languages
     *
     * @var array
     */
    private $languages;

    /**
     * Current language
     *
     * @var string
     */
    private $current_language;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option( 'mat_switcher_settings', array() );
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
        
        // Determine current language
        $this->current_language = $this->get_current_language();
    }

    /**
     * Get current language from URL or cookie
     *
     * @return string Language code
     */
    private function get_current_language() {
        // Check URL parameter first
        if ( isset( $_GET['lang'] ) ) {
            $lang = sanitize_text_field( $_GET['lang'] );
            if ( $this->is_valid_language( $lang ) ) {
                // Set cookie for persistence
                setcookie( 'mat_language', $lang, time() + ( 365 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
                return $lang;
            }
        }
        
        // Check cookie
        if ( isset( $_COOKIE['mat_language'] ) ) {
            $lang = sanitize_text_field( $_COOKIE['mat_language'] );
            if ( $this->is_valid_language( $lang ) ) {
                return $lang;
            }
        }
        
        // Return default language
        return $this->get_default_language();
    }

    /**
     * Check if language code is valid
     *
     * @param string $code Language code
     * @return bool
     */
    private function is_valid_language( $code ) {
        foreach ( $this->languages as $lang ) {
            if ( $lang['code'] === $code ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get default language code
     *
     * @return string
     */
    private function get_default_language() {
        foreach ( $this->languages as $lang ) {
            if ( isset( $lang['is_default'] ) && $lang['is_default'] ) {
                return $lang['code'];
            }
        }
        // Fallback to first language or 'en'
        return ! empty( $this->languages ) ? $this->languages[0]['code'] : 'en';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Shortcode
        add_shortcode( 'mat_language_switcher', array( $this, 'render_shortcode' ) );
        add_shortcode( 'mat_switcher', array( $this, 'render_shortcode' ) );
        
        // Menu integration
        add_filter( 'wp_nav_menu_items', array( $this, 'add_to_menu' ), 10, 2 );
        
        // Floating widget
        add_action( 'wp_footer', array( $this, 'render_floating_widget' ) );
        
        // Enqueue frontend assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_mat_switch_language', array( $this, 'ajax_switch_language' ) );
        add_action( 'wp_ajax_nopriv_mat_switch_language', array( $this, 'ajax_switch_language' ) );
    }

    /**
     * Enqueue frontend CSS and JS
     */
    public function enqueue_assets() {
        // Check if switcher should be displayed
        if ( ! $this->should_display() ) {
            return;
        }

        wp_enqueue_style(
            'mat-language-switcher',
            MAT_PLUGIN_URL . 'public/css/language-switcher.css',
            array(),
            MAT_VERSION
        );

        wp_enqueue_script(
            'mat-language-switcher',
            MAT_PLUGIN_URL . 'public/js/language-switcher.js',
            array( 'jquery' ),
            MAT_VERSION,
            true
        );

        wp_localize_script( 'mat-language-switcher', 'matSwitcher', array(
            'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'mat_switch_language' ),
            'currentLanguage' => $this->current_language,
        ) );
    }

    /**
     * Check if switcher should be displayed
     *
     * @return bool
     */
    private function should_display() {
        $position = isset( $this->settings['position'] ) ? $this->settings['position'] : 'menu';
        
        // Always load assets if position is not 'none'
        return $position !== 'none' && ! empty( $this->languages );
    }

    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'style'      => '', // dropdown, inline, flags-only
            'show_flags' => '', // yes, no
            'show_names' => '', // yes, no
            'class'      => '',
        ), $atts, 'mat_language_switcher' );

        // Use shortcode attributes or fall back to settings
        $style      = ! empty( $atts['style'] ) ? $atts['style'] : ( isset( $this->settings['style'] ) ? $this->settings['style'] : 'dropdown' );
        $show_flags = ! empty( $atts['show_flags'] ) ? ( $atts['show_flags'] === 'yes' ) : ( isset( $this->settings['show_flags'] ) ? (bool) $this->settings['show_flags'] : true );
        $show_names = ! empty( $atts['show_names'] ) ? ( $atts['show_names'] === 'yes' ) : ( isset( $this->settings['show_names'] ) ? (bool) $this->settings['show_names'] : true );

        return $this->render_switcher( $style, $show_flags, $show_names, $atts['class'] );
    }

    /**
     * Render the language switcher HTML
     *
     * @param string $style      Switcher style (dropdown, inline, flags-only)
     * @param bool   $show_flags Show flag icons
     * @param bool   $show_names Show language names
     * @param string $extra_class Additional CSS class
     * @return string HTML output
     */
    public function render_switcher( $style = 'dropdown', $show_flags = true, $show_names = true, $extra_class = '' ) {
        if ( empty( $this->languages ) ) {
            return '';
        }

        $classes = array( 'mat-language-switcher', 'mat-switcher-' . $style );
        if ( ! empty( $extra_class ) ) {
            $classes[] = sanitize_html_class( $extra_class );
        }

        $current_lang = $this->get_current_language_data();
        
        ob_start();
        
        switch ( $style ) {
            case 'inline':
                $this->render_inline_style( $classes, $show_flags, $show_names );
                break;
            
            case 'flags-only':
                $this->render_flags_only_style( $classes );
                break;
            
            case 'dropdown':
            default:
                $this->render_dropdown_style( $classes, $show_flags, $show_names, $current_lang );
                break;
        }
        
        return ob_get_clean();
    }

    /**
     * Render dropdown style switcher
     *
     * @param array  $classes     CSS classes
     * @param bool   $show_flags  Show flags
     * @param bool   $show_names  Show names
     * @param array  $current_lang Current language data
     */
    private function render_dropdown_style( $classes, $show_flags, $show_names, $current_lang ) {
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <button type="button" class="mat-switcher-toggle" aria-expanded="false" aria-haspopup="true">
                <?php if ( $show_flags && ! empty( $current_lang['flag'] ) ) : ?>
                    <span class="mat-flag"><?php echo esc_html( $current_lang['flag'] ); ?></span>
                <?php endif; ?>
                <?php if ( $show_names ) : ?>
                    <span class="mat-lang-name"><?php echo esc_html( $current_lang['native_name'] ); ?></span>
                <?php endif; ?>
                <svg class="mat-dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>
            <ul class="mat-switcher-dropdown" role="menu">
                <?php foreach ( $this->languages as $lang ) : ?>
                    <li role="none">
                        <a href="<?php echo esc_url( $this->get_language_url( $lang['code'] ) ); ?>" 
                           role="menuitem"
                           class="mat-lang-item <?php echo $lang['code'] === $this->current_language ? 'mat-active' : ''; ?>"
                           data-lang="<?php echo esc_attr( $lang['code'] ); ?>">
                            <?php if ( $show_flags && ! empty( $lang['flag'] ) ) : ?>
                                <span class="mat-flag"><?php echo esc_html( $lang['flag'] ); ?></span>
                            <?php endif; ?>
                            <?php if ( $show_names ) : ?>
                                <span class="mat-lang-name"><?php echo esc_html( $lang['native_name'] ); ?></span>
                            <?php endif; ?>
                            <?php if ( $lang['code'] === $this->current_language ) : ?>
                                <svg class="mat-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 6L9 17l-5-5"/>
                                </svg>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Render inline style switcher
     *
     * @param array $classes    CSS classes
     * @param bool  $show_flags Show flags
     * @param bool  $show_names Show names
     */
    private function render_inline_style( $classes, $show_flags, $show_names ) {
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <ul class="mat-switcher-inline">
                <?php foreach ( $this->languages as $index => $lang ) : ?>
                    <?php if ( $index > 0 ) : ?>
                        <li class="mat-separator">|</li>
                    <?php endif; ?>
                    <li>
                        <a href="<?php echo esc_url( $this->get_language_url( $lang['code'] ) ); ?>" 
                           class="mat-lang-item <?php echo $lang['code'] === $this->current_language ? 'mat-active' : ''; ?>"
                           data-lang="<?php echo esc_attr( $lang['code'] ); ?>">
                            <?php if ( $show_flags && ! empty( $lang['flag'] ) ) : ?>
                                <span class="mat-flag"><?php echo esc_html( $lang['flag'] ); ?></span>
                            <?php endif; ?>
                            <?php if ( $show_names ) : ?>
                                <span class="mat-lang-name"><?php echo esc_html( $lang['native_name'] ); ?></span>
                            <?php elseif ( ! $show_flags ) : ?>
                                <span class="mat-lang-code"><?php echo esc_html( strtoupper( $lang['code'] ) ); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Render flags-only style switcher
     *
     * @param array $classes CSS classes
     */
    private function render_flags_only_style( $classes ) {
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <ul class="mat-switcher-flags">
                <?php foreach ( $this->languages as $lang ) : ?>
                    <li>
                        <a href="<?php echo esc_url( $this->get_language_url( $lang['code'] ) ); ?>" 
                           class="mat-flag-item <?php echo $lang['code'] === $this->current_language ? 'mat-active' : ''; ?>"
                           data-lang="<?php echo esc_attr( $lang['code'] ); ?>"
                           title="<?php echo esc_attr( $lang['native_name'] ); ?>">
                            <span class="mat-flag"><?php echo esc_html( ! empty( $lang['flag'] ) ? $lang['flag'] : strtoupper( substr( $lang['code'], 0, 2 ) ) ); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Get current language data
     *
     * @return array
     */
    private function get_current_language_data() {
        foreach ( $this->languages as $lang ) {
            if ( $lang['code'] === $this->current_language ) {
                return $lang;
            }
        }
        
        // Fallback to first language
        return ! empty( $this->languages ) ? $this->languages[0] : array(
            'code'        => 'en',
            'name'        => 'English',
            'native_name' => 'English',
            'flag'        => '🇬🇧',
        );
    }

    /**
     * Get URL for switching to a language
     *
     * @param string $lang_code Language code
     * @return string URL
     */
    private function get_language_url( $lang_code ) {
        $current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        // Remove existing lang parameter
        $current_url = remove_query_arg( 'lang', $current_url );
        
        // Add new lang parameter
        return add_query_arg( 'lang', $lang_code, $current_url );
    }

    /**
     * Add switcher to navigation menu
     *
     * @param string $items Menu items HTML
     * @param object $args  Menu arguments
     * @return string Modified menu HTML
     */
    public function add_to_menu( $items, $args ) {
        $position     = isset( $this->settings['position'] ) ? $this->settings['position'] : 'menu';
        $menu_location = isset( $this->settings['menu_location'] ) ? $this->settings['menu_location'] : 'primary';
        
        // Check if this is the right position and menu
        if ( $position !== 'menu' || empty( $this->languages ) ) {
            return $items;
        }
        
        // Check menu location - support common theme locations
        $target_locations = array( $menu_location );
        if ( $menu_location === 'primary' ) {
            $target_locations = array( 'primary', 'primary-menu', 'main-menu', 'header-menu', 'header', 'main', 'primary_navigation' );
        }
        
        if ( ! isset( $args->theme_location ) || ! in_array( $args->theme_location, $target_locations ) ) {
            return $items;
        }
        
        $style      = isset( $this->settings['style'] ) ? $this->settings['style'] : 'dropdown';
        $show_flags = isset( $this->settings['show_flags'] ) ? (bool) $this->settings['show_flags'] : true;
        $show_names = isset( $this->settings['show_names'] ) ? (bool) $this->settings['show_names'] : true;
        
        $switcher = $this->render_switcher( $style, $show_flags, $show_names, 'mat-menu-switcher' );
        
        // Wrap in menu item
        $menu_item = '<li class="menu-item mat-menu-item">' . $switcher . '</li>';
        
        return $items . $menu_item;
    }

    /**
     * Render floating widget
     */
    public function render_floating_widget() {
        $position = isset( $this->settings['position'] ) ? $this->settings['position'] : 'menu';
        
        if ( $position !== 'floating' || empty( $this->languages ) ) {
            return;
        }
        
        $float_position = isset( $this->settings['float_position'] ) ? $this->settings['float_position'] : 'bottom-right';
        $style          = isset( $this->settings['style'] ) ? $this->settings['style'] : 'dropdown';
        $show_flags     = isset( $this->settings['show_flags'] ) ? (bool) $this->settings['show_flags'] : true;
        $show_names     = isset( $this->settings['show_names'] ) ? (bool) $this->settings['show_names'] : true;
        
        $current_lang = $this->get_current_language_data();
        ?>
        <div class="mat-floating-widget mat-float-<?php echo esc_attr( $float_position ); ?>">
            <button type="button" class="mat-floating-toggle" aria-expanded="false" aria-label="<?php esc_attr_e( 'Select Language', 'multilingual-ai-translator' ); ?>">
                <?php if ( ! empty( $current_lang['flag'] ) ) : ?>
                    <span class="mat-flag"><?php echo esc_html( $current_lang['flag'] ); ?></span>
                <?php else : ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                <?php endif; ?>
            </button>
            <div class="mat-floating-dropdown">
                <div class="mat-floating-header">
                    <span><?php esc_html_e( 'Select Language', 'multilingual-ai-translator' ); ?></span>
                    <button type="button" class="mat-floating-close" aria-label="<?php esc_attr_e( 'Close', 'multilingual-ai-translator' ); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <ul class="mat-floating-list">
                    <?php foreach ( $this->languages as $lang ) : ?>
                        <li>
                            <a href="<?php echo esc_url( $this->get_language_url( $lang['code'] ) ); ?>" 
                               class="mat-floating-item <?php echo $lang['code'] === $this->current_language ? 'mat-active' : ''; ?>"
                               data-lang="<?php echo esc_attr( $lang['code'] ); ?>">
                                <?php if ( $show_flags && ! empty( $lang['flag'] ) ) : ?>
                                    <span class="mat-flag"><?php echo esc_html( $lang['flag'] ); ?></span>
                                <?php endif; ?>
                                <span class="mat-lang-info">
                                    <span class="mat-lang-native"><?php echo esc_html( $lang['native_name'] ); ?></span>
                                    <?php if ( $lang['name'] !== $lang['native_name'] ) : ?>
                                        <span class="mat-lang-english"><?php echo esc_html( $lang['name'] ); ?></span>
                                    <?php endif; ?>
                                </span>
                                <?php if ( $lang['code'] === $this->current_language ) : ?>
                                    <svg class="mat-check" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for language switching
     */
    public function ajax_switch_language() {
        check_ajax_referer( 'mat_switch_language', 'nonce' );
        
        $lang = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : '';
        
        if ( empty( $lang ) || ! $this->is_valid_language( $lang ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid language', 'multilingual-ai-translator' ) ) );
        }
        
        // Set cookie
        setcookie( 'mat_language', $lang, time() + ( 365 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
        
        wp_send_json_success( array(
            'language' => $lang,
            'message'  => __( 'Language switched successfully', 'multilingual-ai-translator' ),
        ) );
    }

    /**
     * Get current language code (public method)
     *
     * @return string
     */
    public function get_current_lang() {
        return $this->current_language;
    }

    /**
     * Get all active languages (public method)
     *
     * @return array
     */
    public function get_languages() {
        return $this->languages;
    }
}
