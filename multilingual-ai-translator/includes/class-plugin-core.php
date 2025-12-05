<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MAT_Plugin_Core {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'multilingual-ai-translator';
		$this->version = MAT_VERSION;

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		$base_path = plugin_dir_path( dirname( __FILE__ ) );
		$files = array(
			'includes/class-database-handler.php',
			'includes/class-openrouter-api.php',
			'includes/class-translation-manager.php',
			'includes/class-language-switcher.php',
			'includes/class-seo-handler.php',
			'includes/class-url-handler.php',
			'includes/class-admin-settings.php',
			'admin/class-admin-menu.php',
			'admin/class-translation-editor.php',
			'admin/class-seo-metabox.php',
			'public/class-frontend-handler.php',
		);

		foreach ( $files as $file ) {
			if ( file_exists( $base_path . $file ) ) {
				require_once $base_path . $file;
			}
		}
	}

	private function define_admin_hooks() {
		if ( class_exists( 'MAT_Admin_Menu' ) ) {
			$plugin_admin = new MAT_Admin_Menu( $this->get_plugin_name(), $this->get_version() );
			// Add actions/filters for admin
			add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
		}

		if ( class_exists( 'MAT_Admin_Settings' ) ) {
			new MAT_Admin_Settings();
		}
	}

	private function define_public_hooks() {
		if ( class_exists( 'MAT_Frontend_Handler' ) ) {
			$plugin_public = new MAT_Frontend_Handler( $this->get_plugin_name(), $this->get_version() );
			// Add actions/filters for public
		}
	}

	public function run() {
		// Run the loader if we had one, or just let the hooks fire
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}
}
