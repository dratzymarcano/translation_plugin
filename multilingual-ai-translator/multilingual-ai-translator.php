<?php
/**
 * Plugin Name: MultiLingual AI Translator Pro
 * Plugin URI:  https://example.com/multilingual-ai-translator-pro
 * Description: AI-powered translations via OpenRouter API with SEO optimization.
 * Version:     1.0.2
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: multilingual-ai-translator
 * Domain Path: /languages
 * License:     GPL-2.0+
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.9
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'MAT_VERSION', '1.0.2' );

/**
 * The code that runs during plugin activation.
 */
function activate_multilingual_ai_translator() {
	// Minimum PHP version check
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		wp_die( esc_html__( 'MultiLingual AI Translator Pro requires PHP version 7.4 or higher.', 'multilingual-ai-translator' ) );
	}

	// Minimum WordPress version check
	if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
		wp_die( esc_html__( 'MultiLingual AI Translator Pro requires WordPress version 6.0 or higher.', 'multilingual-ai-translator' ) );
	}

	// Check for mbstring extension (crucial for multilingual support)
	if ( ! extension_loaded( 'mbstring' ) ) {
		wp_die( esc_html__( 'MultiLingual AI Translator Pro requires the mbstring PHP extension to function correctly.', 'multilingual-ai-translator' ) );
	}

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-database-handler.php';
	MAT_Database_Handler::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_multilingual_ai_translator() {
	// Deactivation logic
}

register_activation_hook( __FILE__, 'activate_multilingual_ai_translator' );
register_deactivation_hook( __FILE__, 'deactivate_multilingual_ai_translator' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-plugin-core.php';

/**
 * Begins execution of the plugin.
 */
function run_multilingual_ai_translator() {
	$plugin = new MAT_Plugin_Core();
	$plugin->run();
}
run_multilingual_ai_translator();
