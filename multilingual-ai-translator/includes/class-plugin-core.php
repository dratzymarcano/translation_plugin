<?php
/**
 * Plugin Core - Main orchestrator class.
 *
 * TranslatePress/Polylang-style multilingual plugin with:
 * - SEO-friendly URLs (/{lang}/{slug}/)
 * - OpenRouter AI translations saved to database
 * - Editable translations in admin
 * - Per-language SEO meta (title, description, keywords, slug)
 * - Frontend language switcher
 *
 * @package MultiLingual_AI_Translator
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MAT_Plugin_Core {

    private $plugin_name;
    private $version;
    
    /** @var MAT_Database_Handler */
    private $database;
    
    /** @var MAT_URL_Handler */
    private $url_handler;
    
    /** @var MAT_Frontend_Handler */
    private $frontend;
    
    /** @var MAT_Language_Switcher */
    private $switcher;
    
    /** @var MAT_Translation_Editor */
    private $editor;
    
    /** @var MAT_OpenRouter_API */
    private $api;

    public function __construct() {
        $this->plugin_name = 'multilingual-ai-translator';
        $this->version     = MAT_VERSION;
    }

    /**
     * Run the plugin.
     */
    public function run() {
        $this->load_dependencies();
        $this->init_components();
        $this->init_admin();
        $this->init_public();
        $this->register_hooks();
    }

    /**
     * Load all dependencies.
     */
    private function load_dependencies() {
        $files = array(
            // Core classes
            'includes/class-database-handler.php',
            'includes/class-openrouter-api.php',
            
            // URL and Frontend handling
            'includes/class-url-handler.php',
            'includes/class-frontend-handler.php',
            
            // Frontend switcher
            'includes/class-language-switcher.php',
            
            // Admin
            'includes/class-translation-editor.php',
            'admin/class-admin-menu.php',
        );

        foreach ( $files as $file ) {
            $path = MAT_PLUGIN_DIR . $file;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }
    }

    /**
     * Initialize core components.
     */
    private function init_components() {
        // Database handler - ensure tables exist
        if ( class_exists( 'MAT_Database_Handler' ) ) {
            MAT_Database_Handler::check_tables_exist();
        }
        
        // OpenRouter API
        if ( class_exists( 'MAT_OpenRouter_API' ) ) {
            $this->api = new MAT_OpenRouter_API();
        }
        
        // URL Handler for SEO-friendly URLs
        if ( class_exists( 'MAT_URL_Handler' ) ) {
            $this->url_handler = new MAT_URL_Handler();
            // Make URL handler globally accessible for Frontend and Switcher
            global $mat_url_handler;
            $mat_url_handler = $this->url_handler;
        }
    }

    /**
     * Initialize admin functionality.
     */
    private function init_admin() {
        if ( ! is_admin() ) {
            return;
        }

        // Admin menu
        if ( class_exists( 'MAT_Admin_Menu' ) ) {
            new MAT_Admin_Menu( $this->plugin_name, $this->version );
        }

        // Translation Editor (Polylang-style metabox)
        if ( class_exists( 'MAT_Translation_Editor' ) ) {
            $this->editor = new MAT_Translation_Editor();
        }

        // Add settings link to plugins page
        add_filter( 'plugin_action_links_' . MAT_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
        
        // Enqueue admin assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Initialize public functionality.
     */
    private function init_public() {
        // Frontend content handler
        if ( class_exists( 'MAT_Frontend_Handler' ) ) {
            $this->frontend = new MAT_Frontend_Handler();
        }
        
        // Language Switcher
        if ( class_exists( 'MAT_Language_Switcher' ) ) {
            $this->switcher = new MAT_Language_Switcher();
        }
    }

    /**
     * Register plugin hooks.
     */
    private function register_hooks() {
        // Activation hook
        register_activation_hook( MAT_PLUGIN_FILE, array( $this, 'activate' ) );
        
        // Deactivation hook
        register_deactivation_hook( MAT_PLUGIN_FILE, array( $this, 'deactivate' ) );
        
        // Flush rewrite rules after settings saved
        add_action( 'update_option_mat_settings', array( $this, 'schedule_flush_rewrite' ) );
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Create database tables
        if ( class_exists( 'MAT_Database_Handler' ) ) {
            MAT_Database_Handler::create_tables();
            MAT_Database_Handler::seed_eu_languages();
        }
        
        // Setup default options
        $this->setup_default_options();
        
        // Flush rewrite rules
        if ( class_exists( 'MAT_URL_Handler' ) ) {
            $url_handler = new MAT_URL_Handler();
            $url_handler->add_rewrite_rules();
        }
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Schedule flush rewrite rules.
     */
    public function schedule_flush_rewrite() {
        update_option( 'mat_flush_rewrite', true );
    }

    /**
     * Setup default options.
     */
    private function setup_default_options() {
        $defaults = array(
            'api_key'           => '',
            'default_model'     => 'openai/gpt-4o-mini',
            'default_language'  => 'en',
            'auto_translate'    => 'off',
            'seo_urls'          => 'on',
            'hreflang_tags'     => 'on',
            'switcher_position' => 'bottom-right',
            'switcher_style'    => 'dropdown',
            'show_flags'        => 'on',
        );
        
        $existing = get_option( 'mat_settings', array() );
        $merged = wp_parse_args( $existing, $defaults );
        update_option( 'mat_settings', $merged );
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets( $hook ) {
        // Translation editor CSS
        if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            wp_enqueue_style(
                'mat-translation-editor',
                MAT_PLUGIN_URL . 'admin/css/translation-editor.css',
                array(),
                $this->version
            );
        }
        
        // Main admin CSS on plugin pages
        if ( strpos( $hook, 'multilingual-ai-translator' ) !== false || strpos( $hook, 'mat-' ) !== false ) {
            wp_enqueue_style(
                'mat-admin',
                MAT_PLUGIN_URL . 'admin/css/admin.css',
                array(),
                $this->version
            );
            
            wp_enqueue_script(
                'mat-admin',
                MAT_PLUGIN_URL . 'admin/js/admin.js',
                array( 'jquery' ),
                $this->version,
                true
            );
            
            wp_localize_script( 'mat-admin', 'mat_admin', array(
                'ajax_url'    => admin_url( 'admin-ajax.php' ),
                'nonce'       => wp_create_nonce( 'mat_admin_nonce' ),
                'plugin_url'  => MAT_PLUGIN_URL,
                'strings'     => array(
                    'translating' => __( 'Translating...', 'multilingual-ai-translator' ),
                    'translated'  => __( 'Translation complete!', 'multilingual-ai-translator' ),
                    'error'       => __( 'Translation failed', 'multilingual-ai-translator' ),
                    'saving'      => __( 'Saving...', 'multilingual-ai-translator' ),
                    'saved'       => __( 'Saved!', 'multilingual-ai-translator' ),
                ),
            ) );
        }
    }

    /**
     * Add settings link.
     */
    public function add_settings_link( $links ) {
        $settings_link = '<a href="admin.php?page=' . $this->plugin_name . '">' . __( 'Settings', 'multilingual-ai-translator' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Get plugin name.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Get version.
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Get database handler instance.
     */
    public function get_database() {
        return $this->database;
    }
    
    /**
     * Get API instance.
     */
    public function get_api() {
        return $this->api;
    }
    
    /**
     * Get URL handler instance.
     */
    public function get_url_handler() {
        return $this->url_handler;
    }
}
