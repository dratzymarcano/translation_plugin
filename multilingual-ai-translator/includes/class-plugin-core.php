<?php
/**
 * Plugin Core - Main orchestrator class.
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

    public function __construct() {
        $this->plugin_name = 'multilingual-ai-translator';
        $this->version     = MAT_VERSION;
    }

    /**
     * Run the plugin.
     */
    public function run() {
        $this->load_dependencies();
        $this->init_admin();
        $this->init_public();
    }

    /**
     * Load all dependencies.
     */
    private function load_dependencies() {
        $files = array(
            'includes/class-database-handler.php',
            'includes/class-openrouter-api.php',
            'includes/class-language-switcher.php',
            'includes/class-seo-metabox.php',
            'admin/class-admin-menu.php',
        );

        foreach ( $files as $file ) {
            $path = MAT_PLUGIN_DIR . $file;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }

        // Ensure tables exist
        if ( class_exists( 'MAT_Database_Handler' ) ) {
            MAT_Database_Handler::check_tables_exist();
        }
    }

    /**
     * Initialize admin functionality.
     */
    private function init_admin() {
        if ( ! is_admin() ) {
            return;
        }

        if ( class_exists( 'MAT_Admin_Menu' ) ) {
            new MAT_Admin_Menu( $this->plugin_name, $this->version );
        }

        if ( class_exists( 'MAT_SEO_Metabox' ) ) {
            new MAT_SEO_Metabox();
        }

        // Add settings link to plugins page
        add_filter( 'plugin_action_links_' . MAT_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
    }

    /**
     * Initialize public functionality.
     */
    private function init_public() {
        if ( class_exists( 'MAT_Language_Switcher' ) ) {
            new MAT_Language_Switcher();
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

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}
