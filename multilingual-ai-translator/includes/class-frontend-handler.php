<?php
/**
 * Frontend Handler - Displays translated content on the frontend
 *
 * @package MultiLingual_AI_Translator
 * @since 2.02
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MAT_Frontend_Handler {

    /**
     * URL Handler instance
     */
    private $url_handler;

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
        // Wait for URL handler to be initialized
        add_action( 'wp', array( $this, 'init' ), 1 );
    }

    /**
     * Initialize frontend handling
     */
    public function init() {
        global $mat_url_handler;
        
        if ( ! isset( $mat_url_handler ) ) {
            return;
        }
        
        $this->url_handler = $mat_url_handler;
        $this->current_lang = $this->url_handler->get_current_language();
        $this->default_lang = $this->url_handler->get_default_language();
        
        // Only filter content for non-default languages
        if ( $this->current_lang !== $this->default_lang ) {
            // Filter post content
            add_filter( 'the_title', array( $this, 'filter_title' ), 10, 2 );
            add_filter( 'the_content', array( $this, 'filter_content' ), 10 );
            add_filter( 'the_excerpt', array( $this, 'filter_excerpt' ), 10 );
            add_filter( 'single_post_title', array( $this, 'filter_single_title' ), 10, 2 );
            
            // Filter document title
            add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ), 20 );
            add_filter( 'document_title_parts', array( $this, 'filter_title_parts' ), 20 );
        }
        
        // SEO meta tags (always)
        add_action( 'wp_head', array( $this, 'output_seo_meta' ), 1 );
        add_action( 'wp_head', array( $this, 'output_hreflang' ), 2 );
        
        // Open Graph
        add_action( 'wp_head', array( $this, 'output_og_tags' ), 5 );
    }

    /**
     * Filter post title
     */
    public function filter_title( $title, $post_id = 0 ) {
        if ( ! $post_id || is_admin() ) {
            return $title;
        }
        
        $translation = MAT_Database_Handler::get_post_translation( $post_id, $this->current_lang );
        
        if ( $translation && ! empty( $translation['translated_title'] ) ) {
            return $translation['translated_title'];
        }
        
        return $title;
    }

    /**
     * Filter single post title
     */
    public function filter_single_title( $title, $post ) {
        if ( ! $post || is_admin() ) {
            return $title;
        }
        
        $translation = MAT_Database_Handler::get_post_translation( $post->ID, $this->current_lang );
        
        if ( $translation && ! empty( $translation['translated_title'] ) ) {
            return $translation['translated_title'];
        }
        
        return $title;
    }

    /**
     * Filter post content
     */
    public function filter_content( $content ) {
        if ( is_admin() || ! is_singular() ) {
            return $content;
        }
        
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return $content;
        }
        
        $translation = MAT_Database_Handler::get_post_translation( $post_id, $this->current_lang );
        
        if ( $translation && ! empty( $translation['translated_content'] ) ) {
            return $translation['translated_content'];
        }
        
        return $content;
    }

    /**
     * Filter post excerpt
     */
    public function filter_excerpt( $excerpt ) {
        if ( is_admin() ) {
            return $excerpt;
        }
        
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return $excerpt;
        }
        
        $translation = MAT_Database_Handler::get_post_translation( $post_id, $this->current_lang );
        
        if ( $translation && ! empty( $translation['translated_excerpt'] ) ) {
            return $translation['translated_excerpt'];
        }
        
        return $excerpt;
    }

    /**
     * Filter document title
     */
    public function filter_document_title( $title ) {
        if ( ! is_singular() ) {
            return $title;
        }
        
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            return $title;
        }
        
        $translation = MAT_Database_Handler::get_post_translation( $post_id, $this->current_lang );
        
        if ( $translation && ! empty( $translation['meta_title'] ) ) {
            return $translation['meta_title'];
        }
        
        if ( $translation && ! empty( $translation['translated_title'] ) ) {
            return $translation['translated_title'];
        }
        
        return $title;
    }

    /**
     * Filter title parts
     */
    public function filter_title_parts( $title_parts ) {
        if ( ! is_singular() ) {
            return $title_parts;
        }
        
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            return $title_parts;
        }
        
        $translation = MAT_Database_Handler::get_post_translation( $post_id, $this->current_lang );
        
        if ( $translation && ! empty( $translation['meta_title'] ) ) {
            $title_parts['title'] = $translation['meta_title'];
        } elseif ( $translation && ! empty( $translation['translated_title'] ) ) {
            $title_parts['title'] = $translation['translated_title'];
        }
        
        return $title_parts;
    }

    /**
     * Output SEO meta tags
     */
    public function output_seo_meta() {
        if ( ! is_singular() ) {
            return;
        }
        
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            return;
        }
        
        $translation = MAT_Database_Handler::get_post_translation( $post_id, $this->current_lang );
        
        if ( ! $translation ) {
            return;
        }
        
        // Meta description
        if ( ! empty( $translation['meta_description'] ) ) {
            echo '<meta name="description" content="' . esc_attr( $translation['meta_description'] ) . '">' . "\n";
        }
        
        // Meta keywords
        if ( ! empty( $translation['meta_keywords'] ) ) {
            echo '<meta name="keywords" content="' . esc_attr( $translation['meta_keywords'] ) . '">' . "\n";
        }
    }

    /**
     * Output hreflang tags
     */
    public function output_hreflang() {
        if ( ! is_singular() ) {
            // For homepage and archives
            $this->output_homepage_hreflang();
            return;
        }
        
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            return;
        }
        
        $languages = MAT_Database_Handler::get_active_languages();
        
        foreach ( $languages as $lang ) {
            $url = $this->url_handler->get_language_url( $lang['code'], $post_id );
            echo '<link rel="alternate" hreflang="' . esc_attr( $lang['code'] ) . '" href="' . esc_url( $url ) . '">' . "\n";
        }
        
        // x-default (default language)
        $default_url = $this->url_handler->get_language_url( $this->default_lang, $post_id );
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $default_url ) . '">' . "\n";
    }

    /**
     * Output homepage hreflang
     */
    private function output_homepage_hreflang() {
        if ( ! is_front_page() && ! is_home() ) {
            return;
        }
        
        $languages = MAT_Database_Handler::get_active_languages();
        
        foreach ( $languages as $lang ) {
            if ( $lang['code'] === $this->default_lang ) {
                $url = home_url( '/' );
            } else {
                $url = home_url( '/' . $lang['code'] . '/' );
            }
            echo '<link rel="alternate" hreflang="' . esc_attr( $lang['code'] ) . '" href="' . esc_url( $url ) . '">' . "\n";
        }
        
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( home_url( '/' ) ) . '">' . "\n";
    }

    /**
     * Output Open Graph tags
     */
    public function output_og_tags() {
        if ( ! is_singular() ) {
            return;
        }
        
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            return;
        }
        
        $translation = MAT_Database_Handler::get_post_translation( $post_id, $this->current_lang );
        $post = get_post( $post_id );
        
        // OG Title
        $og_title = '';
        if ( $translation && ! empty( $translation['og_title'] ) ) {
            $og_title = $translation['og_title'];
        } elseif ( $translation && ! empty( $translation['meta_title'] ) ) {
            $og_title = $translation['meta_title'];
        } elseif ( $translation && ! empty( $translation['translated_title'] ) ) {
            $og_title = $translation['translated_title'];
        } else {
            $og_title = get_the_title( $post_id );
        }
        
        // OG Description
        $og_desc = '';
        if ( $translation && ! empty( $translation['og_description'] ) ) {
            $og_desc = $translation['og_description'];
        } elseif ( $translation && ! empty( $translation['meta_description'] ) ) {
            $og_desc = $translation['meta_description'];
        } elseif ( $translation && ! empty( $translation['translated_excerpt'] ) ) {
            $og_desc = wp_trim_words( strip_tags( $translation['translated_excerpt'] ), 30 );
        }
        
        // Output tags
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( $this->url_handler->get_language_url( $this->current_lang, $post_id ) ) . '">' . "\n";
        
        if ( $og_title ) {
            echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
        }
        
        if ( $og_desc ) {
            echo '<meta property="og:description" content="' . esc_attr( $og_desc ) . '">' . "\n";
        }
        
        // OG Image
        if ( has_post_thumbnail( $post_id ) ) {
            echo '<meta property="og:image" content="' . esc_url( get_the_post_thumbnail_url( $post_id, 'large' ) ) . '">' . "\n";
        }
        
        // OG Locale
        $locale = $this->get_og_locale( $this->current_lang );
        echo '<meta property="og:locale" content="' . esc_attr( $locale ) . '">' . "\n";
        
        // Alternate locales
        $languages = MAT_Database_Handler::get_active_languages();
        foreach ( $languages as $lang ) {
            if ( $lang['code'] !== $this->current_lang ) {
                echo '<meta property="og:locale:alternate" content="' . esc_attr( $this->get_og_locale( $lang['code'] ) ) . '">' . "\n";
            }
        }
        
        // Twitter Card
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        if ( $og_title ) {
            echo '<meta name="twitter:title" content="' . esc_attr( $og_title ) . '">' . "\n";
        }
        if ( $og_desc ) {
            echo '<meta name="twitter:description" content="' . esc_attr( $og_desc ) . '">' . "\n";
        }
    }

    /**
     * Get OG locale from language code
     */
    private function get_og_locale( $lang_code ) {
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
        
        return isset( $locales[ $lang_code ] ) ? $locales[ $lang_code ] : $lang_code . '_' . strtoupper( $lang_code );
    }

    /**
     * Get current language
     */
    public function get_current_language() {
        return $this->current_lang;
    }
}
