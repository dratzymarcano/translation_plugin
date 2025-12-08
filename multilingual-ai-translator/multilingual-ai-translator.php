<?php
/**
 * Plugin Name: MultiLingual AI Translator Pro
 * Plugin URI:  https://example.com/multilingual-ai-translator-pro
 * Description: Professional AI-powered translations via OpenRouter API. TranslatePress/Polylang-style workflow with SEO-friendly URLs, editable translations, and per-language SEO metadata.
 * Version:     2.08
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: multilingual-ai-translator
 * Domain Path: /languages
 * License:     GPL-2.0+
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.9
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'MAT_VERSION', '2.08' );
define( 'MAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MAT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'MAT_PLUGIN_FILE', __FILE__ );

/**
 * Activation hook.
 */
function activate_multilingual_ai_translator() {
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		wp_die( esc_html__( 'MultiLingual AI Translator Pro requires PHP 7.4+.', 'multilingual-ai-translator' ) );
	}
	if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
		wp_die( esc_html__( 'MultiLingual AI Translator Pro requires WordPress 6.0+.', 'multilingual-ai-translator' ) );
	}
	if ( ! extension_loaded( 'mbstring' ) ) {
		wp_die( esc_html__( 'MultiLingual AI Translator Pro requires the mbstring PHP extension.', 'multilingual-ai-translator' ) );
	}

	require_once MAT_PLUGIN_DIR . 'includes/class-database-handler.php';
	MAT_Database_Handler::activate();

	// Check table structure and seed default language based on site locale
	MAT_Database_Handler::check_tables_exist();

	flush_rewrite_rules();
}

/**
 * Deactivation hook.
 */
function deactivate_multilingual_ai_translator() {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'activate_multilingual_ai_translator' );
register_deactivation_hook( __FILE__, 'deactivate_multilingual_ai_translator' );

// Load core
require_once MAT_PLUGIN_DIR . 'includes/class-plugin-core.php';

/**
 * Initialize plugin.
 */
function run_multilingual_ai_translator() {
	$plugin = new MAT_Plugin_Core();
	$plugin->run();
}
add_action( 'plugins_loaded', 'run_multilingual_ai_translator' );
