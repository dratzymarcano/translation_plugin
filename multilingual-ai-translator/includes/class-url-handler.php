<?php
/**
 * URL Handler - Manages SEO-friendly multilingual URLs
 *
 * Creates URL structure like: /de/page-slug/ or /fr/article-title/
 *
 * @package MultiLingual_AI_Translator
 * @since 2.02
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MAT_URL_Handler {

    /**
     * Current language code
     */
    private $current_lang;

    /**
     * Default language code
     */
    private $default_lang;

    /**
     * Active languages
     */
    private $languages;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize
     */
    private function init() {
        // Load languages
        $this->languages = MAT_Database_Handler::get_active_languages();
        
        $default = MAT_Database_Handler::get_default_language();
        $this->default_lang = $default ? $default['code'] : 'en';
        
        // Detect current language from URL
        $this->current_lang = $this->detect_language();

        // Add rewrite rules
        add_action( 'init', array( $this, 'add_rewrite_rules' ), 1 );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        
        // Parse request to handle language
        add_action( 'parse_request', array( $this, 'parse_request' ), 1 );
        
        // Filter permalinks
        add_filter( 'post_link', array( $this, 'filter_post_permalink' ), 10, 2 );
        add_filter( 'page_link', array( $this, 'filter_page_permalink' ), 10, 2 );
        add_filter( 'post_type_link', array( $this, 'filter_post_permalink' ), 10, 2 );
        
        // Home URL filter
        add_filter( 'home_url', array( $this, 'filter_home_url' ), 10, 2 );
        
        // Redirect canonical
        add_action( 'template_redirect', array( $this, 'handle_redirects' ) );
        
        // Set locale
        add_filter( 'locale', array( $this, 'filter_locale' ) );
    }

    /**
     * Detect language from URL
     */
    private function detect_language() {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
        $path = trim( parse_url( $request_uri, PHP_URL_PATH ), '/' );
        
        // Remove site subdirectory if exists
        $home_path = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
        if ( $home_path && strpos( $path, $home_path ) === 0 ) {
            $path = substr( $path, strlen( $home_path ) );
            $path = ltrim( $path, '/' );
        }
        
        // Check if first segment is a language code
        $segments = explode( '/', $path );
        if ( ! empty( $segments[0] ) ) {
            $potential_lang = $segments[0];
            foreach ( $this->languages as $lang ) {
                if ( $lang['code'] === $potential_lang ) {
                    return $potential_lang;
                }
            }
        }
        
        // Check query parameter
        if ( isset( $_GET['lang'] ) ) {
            $lang = sanitize_text_field( $_GET['lang'] );
            foreach ( $this->languages as $l ) {
                if ( $l['code'] === $lang ) {
                    return $lang;
                }
            }
        }
        
        // Check cookie
        if ( isset( $_COOKIE['mat_language'] ) ) {
            $lang = sanitize_text_field( $_COOKIE['mat_language'] );
            foreach ( $this->languages as $l ) {
                if ( $l['code'] === $lang ) {
                    return $lang;
                }
            }
        }
        
        return $this->default_lang;
    }

    /**
     * Add rewrite rules for language prefixes
     */
    public function add_rewrite_rules() {
        foreach ( $this->languages as $lang ) {
            $code = $lang['code'];
            
            // v3.0.0: Add rules for ALL languages including default
            // This prevents 404s if someone visits /en/ manually
            
            // Language homepage: /de/ or /en/
            add_rewrite_rule(
                '^' . $code . '/?$',
                'index.php?mat_lang=' . $code,
                'top'
            );
            
            // Language + slug: /de/sample-page/
            add_rewrite_rule(
                '^' . $code . '/(.+?)/?$',
                'index.php?mat_lang=' . $code . '&mat_slug=$matches[1]',
                'top'
            );
        }
    }

    /**
     * Add custom query vars
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'mat_lang';
        $vars[] = 'mat_slug';
        return $vars;
    }

    /**
     * Parse request to handle translated URLs
     */
    public function parse_request( $wp ) {
        $lang = isset( $wp->query_vars['mat_lang'] ) ? $wp->query_vars['mat_lang'] : '';
        $slug = isset( $wp->query_vars['mat_slug'] ) ? $wp->query_vars['mat_slug'] : '';
        
        if ( $lang ) {
            $this->current_lang = $lang;
            $this->set_language_cookie( $lang );
        }
        
        if ( $slug && $lang ) {
            // Try to find post by translated slug
            $translation = MAT_Database_Handler::get_post_by_translated_slug( $slug, $lang );
            
            if ( $translation ) {
                $wp->query_vars['p'] = $translation['post_id'];
                $wp->query_vars['post_type'] = get_post_type( $translation['post_id'] );
                unset( $wp->query_vars['mat_slug'] );
                unset( $wp->query_vars['pagename'] );
                unset( $wp->query_vars['name'] );
            } else {
                // Try original slug
                $post = get_page_by_path( $slug, OBJECT, array( 'post', 'page' ) );
                if ( $post ) {
                    $wp->query_vars['p'] = $post->ID;
                    $wp->query_vars['post_type'] = $post->post_type;
                }
            }
        }
    }

    /**
     * Filter post permalinks
     */
    public function filter_post_permalink( $permalink, $post ) {
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $permalink;
        }
        
        $lang = $this->get_current_language();
        
        // If default language, return original permalink
        if ( $lang === $this->default_lang ) {
            return $permalink;
        }
        
        // Get translated slug if available
        $translation = MAT_Database_Handler::get_post_translation( $post->ID, $lang );
        $slug = ( $translation && ! empty( $translation['translated_slug'] ) ) 
            ? $translation['translated_slug'] 
            : $post->post_name;
        
        // Build translated URL
        return home_url( '/' . $lang . '/' . $slug . '/' );
    }

    /**
     * Filter page permalinks
     */
    public function filter_page_permalink( $permalink, $page_id ) {
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $permalink;
        }
        
        $lang = $this->get_current_language();
        
        if ( $lang === $this->default_lang ) {
            return $permalink;
        }
        
        $post = get_post( $page_id );
        if ( ! $post ) {
            return $permalink;
        }
        
        $translation = MAT_Database_Handler::get_post_translation( $page_id, $lang );
        $slug = ( $translation && ! empty( $translation['translated_slug'] ) ) 
            ? $translation['translated_slug'] 
            : $post->post_name;
        
        return home_url( '/' . $lang . '/' . $slug . '/' );
    }

    /**
     * Filter home URL for language prefix
     */
    public function filter_home_url( $url, $path ) {
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $url;
        }
        
        $lang = $this->get_current_language();
        
        if ( $lang === $this->default_lang ) {
            return $url;
        }
        
        // Don't modify if path already has language
        if ( strpos( $path, '/' . $lang . '/' ) !== false ) {
            return $url;
        }
        
        // Add language prefix to path
        if ( empty( $path ) || $path === '/' ) {
            return trailingslashit( $url ) . $lang . '/';
        }
        
        return $url;
    }

    /**
     * Handle redirects
     */
    public function handle_redirects() {
        // Store language in cookie
        $this->set_language_cookie( $this->current_lang );

        // v3.0.0: Canonical Redirect for Default Language
        // If we are on default language (e.g. 'en') but URL has /en/, redirect to /
        if ( $this->current_lang === $this->default_lang && ! is_admin() ) {
            $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
            
            // Check if URL starts with /en/
            if ( preg_match( '#^/' . preg_quote( $this->default_lang, '#' ) . '(/|$)#', $request_uri ) ) {
                
                // Remove /en/ from the start
                $new_uri = preg_replace( '#^/' . preg_quote( $this->default_lang, '#' ) . '/?#', '/', $request_uri );
                
                // Ensure we don't end up with double slashes or empty string
                if ( empty( $new_uri ) ) {
                    $new_uri = '/';
                }
                
                wp_redirect( home_url( $new_uri ), 301 );
                exit;
            }
        }
    }

    /**
     * Set language cookie
     */
    private function set_language_cookie( $lang ) {
        if ( ! headers_sent() ) {
            setcookie( 'mat_language', $lang, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        }
    }

    /**
     * Filter locale based on current language
     */
    public function filter_locale( $locale ) {
        $lang = $this->get_current_language();
        
        $locales = array(
            'en' => 'en_US',
            'de' => 'de_DE',
            'fr' => 'fr_FR',
            'es' => 'es_ES',
            'it' => 'it_IT',
            'pt' => 'pt_PT',
            'nl' => 'nl_NL',
            'pl' => 'pl_PL',
            'ro' => 'ro_RO',
            'el' => 'el_GR',
            'sv' => 'sv_SE',
            'hu' => 'hu_HU',
            'cs' => 'cs_CZ',
            'da' => 'da_DK',
            'fi' => 'fi_FI',
            'sk' => 'sk_SK',
            'bg' => 'bg_BG',
            'hr' => 'hr_HR',
            'lt' => 'lt_LT',
            'lv' => 'lv_LV',
            'sl' => 'sl_SI',
            'et' => 'et_EE',
            'mt' => 'mt_MT',
            'ga' => 'ga_IE',
        );
        
        return isset( $locales[ $lang ] ) ? $locales[ $lang ] : $locale;
    }

    /**
     * Get current language
     */
    public function get_current_language() {
        return $this->current_lang;
    }

    /**
     * Get default language
     */
    public function get_default_language() {
        return $this->default_lang;
    }

    /**
     * Get URL for specific language
     */
    public function get_language_url( $lang_code, $post_id = null ) {
        if ( ! $post_id ) {
            $post_id = get_queried_object_id();
        }
        
        if ( $lang_code === $this->default_lang ) {
            if ( $post_id ) {
                return get_permalink( $post_id );
            }
            return home_url( '/' );
        }
        
        if ( ! $post_id ) {
            return home_url( '/' . $lang_code . '/' );
        }
        
        // Get translated slug
        $translation = MAT_Database_Handler::get_post_translation( $post_id, $lang_code );
        $post = get_post( $post_id );
        
        if ( ! $post ) {
            return home_url( '/' . $lang_code . '/' );
        }
        
        $slug = ( $translation && ! empty( $translation['translated_slug'] ) )
            ? $translation['translated_slug']
            : $post->post_name;
        
        return home_url( '/' . $lang_code . '/' . $slug . '/' );
    }

    /**
     * Get all language URLs for current page (for hreflang)
     */
    public function get_all_language_urls( $post_id = null ) {
        if ( ! $post_id ) {
            $post_id = get_queried_object_id();
        }
        
        $urls = array();
        
        foreach ( $this->languages as $lang ) {
            $urls[ $lang['code'] ] = $this->get_language_url( $lang['code'], $post_id );
        }
        
        return $urls;
    }

    /**
     * Check if language is active
     */
    public function is_language_active( $code ) {
        foreach ( $this->languages as $lang ) {
            if ( $lang['code'] === $code && $lang['is_active'] ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Flush rewrite rules
     */
    public static function flush_rules() {
        flush_rewrite_rules();
    }
}
