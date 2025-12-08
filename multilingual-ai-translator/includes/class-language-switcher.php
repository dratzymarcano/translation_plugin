<?php
/**
 * Language Switcher - Displays language switching UI on frontend
 *
 * @package MultiLingual_AI_Translator
 * @since 2.02
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MAT_Language_Switcher {

    /**
     * Settings
     */
    private $settings;

    /**
     * Languages
     */
    private $languages;

    /**
     * Current language
     */
    private $current_lang;

    /**
     * Default language
     */
    private $default_lang;

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'register_shortcodes' ) );
    }

    /**
     * Register shortcodes early
     */
    public function register_shortcodes() {
        add_shortcode( 'mat_language_switcher', array( $this, 'render_shortcode' ) );
        add_shortcode( 'mat_switcher', array( $this, 'render_shortcode' ) );
    }

    /**
     * Initialize after WP is loaded
     */
    public function init() {
        global $mat_url_handler;
        
        $this->settings = get_option( 'mat_switcher_settings', array(
            'position'      => 'menu',
            'style'         => 'dropdown',
            'show_flags'    => 1,
            'show_names'    => 1,
            'menu_location' => 'primary',
        ) );
        
        $this->languages = MAT_Database_Handler::get_active_languages();
        
        if ( isset( $mat_url_handler ) ) {
            $this->current_lang = $mat_url_handler->get_current_language();
            $this->default_lang = $mat_url_handler->get_default_language();
        } else {
            $default = MAT_Database_Handler::get_default_language();
            $this->default_lang = $default ? $default['code'] : 'en';
            $this->current_lang = $this->default_lang;
        }
        
        // Hooks
        add_filter( 'wp_nav_menu_items', array( $this, 'add_to_menu' ), 20, 2 );
        add_action( 'wp_footer', array( $this, 'render_floating_widget' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'mat-switcher',
            MAT_PLUGIN_URL . 'public/css/language-switcher.css',
            array(),
            MAT_VERSION
        );

        wp_enqueue_script(
            'mat-switcher',
            MAT_PLUGIN_URL . 'public/js/language-switcher.js',
            array( 'jquery' ),
            MAT_VERSION,
            true
        );
    }

    /**
     * Render shortcode
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'style'      => '',
            'show_flags' => '',
            'show_names' => '',
            'class'      => '',
        ), $atts );

        // Load settings if not loaded
        if ( empty( $this->languages ) ) {
            $this->languages = MAT_Database_Handler::get_active_languages();
            $default = MAT_Database_Handler::get_default_language();
            $this->default_lang = $default ? $default['code'] : 'en';
            $this->current_lang = isset( $_COOKIE['mat_language'] ) ? sanitize_text_field( $_COOKIE['mat_language'] ) : $this->default_lang;
        }

        $style = ! empty( $atts['style'] ) ? $atts['style'] : ( $this->settings['style'] ?? 'dropdown' );
        $show_flags = $atts['show_flags'] !== '' ? ( $atts['show_flags'] === 'yes' ) : ( $this->settings['show_flags'] ?? true );
        $show_names = $atts['show_names'] !== '' ? ( $atts['show_names'] === 'yes' ) : ( $this->settings['show_names'] ?? true );

        return $this->render_switcher( $style, $show_flags, $show_names, $atts['class'] );
    }

    /**
     * Render switcher HTML
     */
    public function render_switcher( $style = 'dropdown', $show_flags = true, $show_names = true, $extra_class = '' ) {
        if ( empty( $this->languages ) || ! is_array( $this->languages ) ) {
            return '';
        }

        $classes = array( 'mat-language-switcher', 'mat-switcher-' . $style );
        if ( $extra_class ) {
            $classes[] = sanitize_html_class( $extra_class );
        }

        ob_start();

        try {
            switch ( $style ) {
                case 'inline':
                    $this->render_inline( $classes, $show_flags, $show_names );
                    break;
                case 'flags':
                case 'flags-only':
                    $this->render_flags_only( $classes );
                    break;
                default:
                    $this->render_dropdown( $classes, $show_flags, $show_names );
                    break;
            }
        } catch ( Exception $e ) {
            ob_end_clean();
            if ( is_admin() ) {
                return '<div class="error"><p>' . esc_html( 'Error rendering switcher: ' . $e->getMessage() ) . '</p></div>';
            }
            return '';
        } catch ( Error $e ) {
            ob_end_clean();
            if ( is_admin() ) {
                return '<div class="error"><p>' . esc_html( 'Fatal error rendering switcher: ' . $e->getMessage() ) . '</p></div>';
            }
            return '';
        }

        return ob_get_clean();
    }

    /**
     * Render dropdown style
     */
    private function render_dropdown( $classes, $show_flags, $show_names ) {
        $current = $this->get_current_lang_data();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <button type="button" class="mat-switcher-toggle" aria-expanded="false">
                <?php if ( $show_flags && ! empty( $current['flag'] ) ) : ?>
                    <span class="mat-flag"><?php echo $this->render_flag( $current['flag'], $current['name'] ); ?></span>
                <?php endif; ?>
                <?php if ( $show_names ) : ?>
                    <span class="mat-lang-name"><?php echo esc_html( $current['native_name'] ?? $current['name'] ); ?></span>
                <?php endif; ?>
                <svg class="mat-dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>
            <ul class="mat-switcher-dropdown" role="menu">
                <?php foreach ( $this->languages as $lang ) : ?>
                    <li role="none">
                        <a href="<?php echo esc_url( $this->get_switch_url( $lang['code'] ) ); ?>" 
                           role="menuitem"
                           class="mat-lang-item <?php echo $lang['code'] === $this->current_lang ? 'mat-active' : ''; ?>"
                           data-lang="<?php echo esc_attr( $lang['code'] ); ?>">
                            <?php if ( $show_flags && ! empty( $lang['flag'] ) ) : ?>
                                <span class="mat-flag"><?php echo $this->render_flag( $lang['flag'], $lang['name'] ?? '' ); ?></span>
                            <?php endif; ?>
                            <?php if ( $show_names ) : ?>
                                <span class="mat-lang-name"><?php echo esc_html( $lang['native_name'] ?? $lang['name'] ); ?></span>
                            <?php endif; ?>
                            <?php if ( $lang['code'] === $this->current_lang ) : ?>
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
     * Render inline style
     */
    private function render_inline( $classes, $show_flags, $show_names ) {
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <ul class="mat-switcher-inline">
                <?php foreach ( $this->languages as $i => $lang ) : ?>
                    <?php if ( $i > 0 ) : ?>
                        <li class="mat-separator">|</li>
                    <?php endif; ?>
                    <li>
                        <a href="<?php echo esc_url( $this->get_switch_url( $lang['code'] ) ); ?>" 
                           class="mat-lang-item <?php echo $lang['code'] === $this->current_lang ? 'mat-active' : ''; ?>">
                            <?php if ( $show_flags && ! empty( $lang['flag'] ) ) : ?>
                                <span class="mat-flag"><?php echo $this->render_flag( $lang['flag'], $lang['name'] ?? '' ); ?></span>
                            <?php endif; ?>
                            <?php if ( $show_names ) : ?>
                                <span class="mat-lang-name"><?php echo esc_html( $lang['native_name'] ?? $lang['name'] ); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Render flags only style
     */
    private function render_flags_only( $classes ) {
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <ul class="mat-switcher-flags">
                <?php foreach ( $this->languages as $lang ) : ?>
                    <li>
                        <a href="<?php echo esc_url( $this->get_switch_url( $lang['code'] ) ); ?>" 
                           class="mat-flag-item <?php echo $lang['code'] === $this->current_lang ? 'mat-active' : ''; ?>"
                           title="<?php echo esc_attr( $lang['native_name'] ?? $lang['name'] ); ?>">
                            <span class="mat-flag"><?php echo $this->render_flag( $lang['flag'], $lang['name'] ?? '' ); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Add to navigation menu
     */
    public function add_to_menu( $items, $args ) {
        $position = $this->settings['position'] ?? 'menu';
        
        if ( $position !== 'menu' || empty( $this->languages ) ) {
            return $items;
        }
        
        $target_locations = array( 'primary', 'primary-menu', 'main-menu', 'header-menu', 'header', 'main', 'menu-1' );
        $menu_location = $this->settings['menu_location'] ?? 'primary';
        
        if ( $menu_location !== 'all' ) {
            $target_locations = array( $menu_location );
        }
        
        if ( ! isset( $args->theme_location ) || ! in_array( $args->theme_location, $target_locations, true ) ) {
            return $items;
        }
        
        $style = $this->settings['style'] ?? 'dropdown';
        $show_flags = $this->settings['show_flags'] ?? true;
        $show_names = $this->settings['show_names'] ?? true;
        
        $switcher = $this->render_switcher( $style, $show_flags, $show_names, 'mat-menu-switcher' );
        
        return $items . '<li class="menu-item mat-menu-item">' . $switcher . '</li>';
    }

    /**
     * Render floating widget
     */
    public function render_floating_widget() {
        $position = $this->settings['position'] ?? 'menu';
        
        if ( $position !== 'floating' || empty( $this->languages ) ) {
            return;
        }
        
        $float_pos = $this->settings['float_position'] ?? 'bottom-right';
        $show_flags = $this->settings['show_flags'] ?? true;
        $current = $this->get_current_lang_data();
        ?>
        <div class="mat-floating-widget mat-float-<?php echo esc_attr( $float_pos ); ?>">
            <button type="button" class="mat-floating-toggle" aria-expanded="false">
                <?php if ( ! empty( $current['flag'] ) ) : ?>
                    <span class="mat-flag"><?php echo $this->render_flag( $current['flag'], $current['name'] ?? '' ); ?></span>
                <?php else : ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                <?php endif; ?>
            </button>
            <div class="mat-floating-dropdown">
                <div class="mat-floating-header">
                    <span><?php esc_html_e( 'Select Language', 'multilingual-ai-translator' ); ?></span>
                    <button type="button" class="mat-floating-close">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <ul class="mat-floating-list">
                    <?php foreach ( $this->languages as $lang ) : ?>
                        <li>
                            <a href="<?php echo esc_url( $this->get_switch_url( $lang['code'] ) ); ?>" 
                               class="mat-floating-item <?php echo $lang['code'] === $this->current_lang ? 'mat-active' : ''; ?>">
                                <?php if ( $show_flags && ! empty( $lang['flag'] ) ) : ?>
                                    <span class="mat-flag"><?php echo $this->render_flag( $lang['flag'], $lang['name'] ?? '' ); ?></span>
                                <?php endif; ?>
                                <span class="mat-lang-info">
                                    <span class="mat-lang-native"><?php echo esc_html( $lang['native_name'] ?? $lang['name'] ); ?></span>
                                    <?php if ( isset( $lang['name'] ) && isset( $lang['native_name'] ) && $lang['name'] !== $lang['native_name'] ) : ?>
                                        <span class="mat-lang-english"><?php echo esc_html( $lang['name'] ); ?></span>
                                    <?php endif; ?>
                                </span>
                                <?php if ( $lang['code'] === $this->current_lang ) : ?>
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
     * Get current language data
     */
    private function get_current_lang_data() {
        if ( ! empty( $this->languages ) ) {
            foreach ( $this->languages as $lang ) {
                if ( isset( $lang['code'] ) && $lang['code'] === $this->current_lang ) {
                    return $lang;
                }
            }
            // Return first language if current not found
            return $this->languages[0];
        }
        
        // Default fallback
        return array(
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'flag' => 'gb',
        );
    }

    /**
     * Get URL for switching language
     */
    private function get_switch_url( $lang_code ) {
        global $mat_url_handler;
        
        // In admin, just return a placeholder URL
        if ( is_admin() ) {
            return '#' . $lang_code;
        }
        
        $post_id = get_queried_object_id();
        
        if ( isset( $mat_url_handler ) ) {
            return $mat_url_handler->get_language_url( $lang_code, $post_id );
        }
        
        // Fallback: query parameter
        if ( $lang_code === $this->default_lang ) {
            if ( $post_id ) {
                return get_permalink( $post_id );
            }
            return home_url( '/' );
        }
        
        return home_url( '/' . $lang_code . '/' );
    }

    /**
     * Render flag - handles both ISO codes and emoji
     */
    private function render_flag( $flag, $alt = '' ) {
        if ( empty( $flag ) ) {
            return '';
        }
        
        // If it's a short ISO code (2-3 chars), render as image
        if ( strlen( $flag ) <= 3 && preg_match( '/^[a-zA-Z]+$/', $flag ) ) {
            return '<img src="https://flagcdn.com/w20/' . esc_attr( strtolower( $flag ) ) . '.png" alt="' . esc_attr( $alt ) . '" class="mat-flag-img" style="width:20px;height:15px;vertical-align:middle;">';
        }
        
        // Otherwise treat as emoji
        return esc_html( $flag );
    }

    /**
     * Get current language code
     */
    public function get_current_language() {
        return $this->current_lang;
    }
}
