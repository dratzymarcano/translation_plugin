<?php
/**
 * Admin Menu - Professional dashboard interface.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MAT_Admin_Menu {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'wp_ajax_mat_toggle_language', array( $this, 'ajax_toggle_language' ) );
		add_action( 'wp_ajax_mat_set_default_language', array( $this, 'ajax_set_default_language' ) );
		add_action( 'wp_ajax_mat_delete_language', array( $this, 'ajax_delete_language' ) );
		add_action( 'wp_ajax_mat_add_language', array( $this, 'ajax_add_language' ) );
		add_action( 'wp_ajax_mat_reorder_languages', array( $this, 'ajax_reorder_languages' ) );
		add_action( 'wp_ajax_mat_repair_database', array( $this, 'ajax_repair_database' ) );
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'AI Translator', 'multilingual-ai-translator' ),
			__( 'AI Translator', 'multilingual-ai-translator' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'render_dashboard' ),
			'dashicons-translation',
			30
		);

		add_submenu_page(
			$this->plugin_name,
			__( 'Dashboard', 'multilingual-ai-translator' ),
			__( 'Dashboard', 'multilingual-ai-translator' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			$this->plugin_name,
			__( 'Languages', 'multilingual-ai-translator' ),
			__( 'Languages', 'multilingual-ai-translator' ),
			'manage_options',
			$this->plugin_name . '-languages',
			array( $this, 'render_languages' )
		);

		add_submenu_page(
			$this->plugin_name,
			__( 'Switcher', 'multilingual-ai-translator' ),
			__( 'Switcher', 'multilingual-ai-translator' ),
			'manage_options',
			$this->plugin_name . '-switcher',
			array( $this, 'render_switcher' )
		);

		add_submenu_page(
			$this->plugin_name,
			__( 'API Settings', 'multilingual-ai-translator' ),
			__( 'API Settings', 'multilingual-ai-translator' ),
			'manage_options',
			$this->plugin_name . '-api',
			array( $this, 'render_api_settings' )
		);
	}

	/**
	 * Enqueue admin assets.
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, $this->plugin_name ) === false ) {
			return;
		}

		wp_enqueue_style(
			'mat-admin-v3-css',
			MAT_PLUGIN_URL . 'assets/css/admin-v3.css',
			array(),
			'3.0.0'
		);

		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'mat-admin-js',
			MAT_PLUGIN_URL . 'admin/js/admin-script.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			$this->version,
			true
		);

		wp_localize_script( 'mat-admin-js', 'matAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'mat_admin_nonce' ),
		) );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		// Switcher settings
		register_setting( 'mat_switcher_settings', 'mat_switcher_type' );
		register_setting( 'mat_switcher_settings', 'mat_switcher_show_flags' );
		register_setting( 'mat_switcher_settings', 'mat_switcher_show_names' );
		register_setting( 'mat_switcher_settings', 'mat_switcher_show_native' );
		register_setting( 'mat_switcher_settings', 'mat_switcher_position' );

		// API settings
		register_setting( 'mat_api_settings', 'mat_openrouter_api_key' );
		register_setting( 'mat_api_settings', 'mat_ai_model' );
		register_setting( 'mat_api_settings', 'mat_auto_translate' );
	}

	/**
	 * Handle form actions.
	 */
	public function handle_actions() {
		// Handle non-AJAX form submissions if needed
	}

	/**
	 * Render Dashboard page.
	 */
	public function render_dashboard() {
		$languages       = MAT_Database_Handler::get_all_languages();
		$active_count    = count( array_filter( $languages, function( $l ) {
			return isset( $l['is_active'] ) ? $l['is_active'] : ( isset( $l->is_active ) ? $l->is_active : 0 );
		} ) );
		$default_lang    = MAT_Database_Handler::get_default_language();
		$api_settings    = get_option( 'mat_api_settings', array() );
		$api_key_set     = ! empty( $api_settings['api_key'] ) || ! empty( get_option( 'mat_openrouter_api_key' ) );
		$switcher_active = get_option( 'mat_switcher_position', 'none' ) !== 'none';

		include MAT_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}

	/**
	 * Render Languages page.
	 */
	public function render_languages() {
		$languages = MAT_Database_Handler::get_all_languages();
		include MAT_PLUGIN_DIR . 'templates/admin/languages.php';
	}

	/**
	 * Render Switcher settings page.
	 */
	public function render_switcher() {
		$languages = MAT_Database_Handler::get_active_languages();
		include MAT_PLUGIN_DIR . 'templates/admin/switcher.php';
	}

	/**
	 * Render API settings page.
	 */
	public function render_api_settings() {
		include MAT_PLUGIN_DIR . 'templates/admin/api-settings.php';
	}

	// ========== AJAX Handlers ==========

	public function ajax_toggle_language() {
		check_ajax_referer( 'mat_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'mat_languages';
		$id    = intval( $_POST['id'] );

		$current = $wpdb->get_var( $wpdb->prepare( "SELECT is_active FROM $table WHERE id = %d", $id ) );
		$new_status = $current ? 0 : 1;

		$wpdb->update( $table, array( 'is_active' => $new_status ), array( 'id' => $id ) );

		wp_send_json_success( array( 'is_active' => $new_status ) );
	}

	public function ajax_set_default_language() {
		check_ajax_referer( 'mat_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'mat_languages';
		$id    = intval( $_POST['id'] );

		// Remove default from all
		$wpdb->update( $table, array( 'is_default' => 0 ), array( 'is_default' => 1 ) );
		// Set new default
		$wpdb->update( $table, array( 'is_default' => 1, 'is_active' => 1 ), array( 'id' => $id ) );

		wp_send_json_success();
	}

	public function ajax_delete_language() {
		check_ajax_referer( 'mat_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'mat_languages';
		$id    = intval( $_POST['id'] );

		// Don't delete the default language
		$is_default = $wpdb->get_var( $wpdb->prepare( "SELECT is_default FROM $table WHERE id = %d", $id ) );
		if ( $is_default ) {
			wp_send_json_error( 'Cannot delete default language' );
		}

		$wpdb->delete( $table, array( 'id' => $id ) );
		wp_send_json_success();
	}

	public function ajax_add_language() {
		check_ajax_referer( 'mat_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'mat_languages';

		$code        = sanitize_text_field( $_POST['code'] );
		$name        = sanitize_text_field( $_POST['name'] );
		$native_name = sanitize_text_field( $_POST['native_name'] );
		$flag        = sanitize_text_field( $_POST['flag'] );

		if ( empty( $code ) || empty( $name ) ) {
			wp_send_json_error( 'Code and Name are required' );
		}

		// Check if already exists
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE code = %s", $code ) );
		if ( $exists ) {
			wp_send_json_error( 'Language already exists. Enable it from the list below.' );
		}

		$max_order = $wpdb->get_var( "SELECT MAX(sort_order) FROM $table" );

		$wpdb->insert( $table, array(
			'code'        => $code,
			'name'        => $name,
			'native_name' => $native_name,
			'flag'        => $flag,
			'is_active'   => 1,
			'is_default'  => 0,
			'sort_order'  => intval( $max_order ) + 1,
		) );

		if ( $wpdb->insert_id ) {
			wp_send_json_success( array( 
				'id'   => $wpdb->insert_id,
				'code' => $code,
				'name' => $name,
			) );
		} else {
			wp_send_json_error( 'Failed to add language: ' . $wpdb->last_error );
		}
	}

	public function ajax_reorder_languages() {
		check_ajax_referer( 'mat_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'mat_languages';
		$order = $_POST['order'];

		foreach ( $order as $position => $id ) {
			$wpdb->update( $table, array( 'sort_order' => $position + 1 ), array( 'id' => intval( $id ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: Repair database tables
	 */
	public function ajax_repair_database() {
		check_ajax_referer( 'mat_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		
		// Drop and recreate the languages table
		$table = $wpdb->prefix . 'mat_languages';
		$wpdb->query( "DROP TABLE IF EXISTS $table" );
		
		// Recreate all tables
		MAT_Database_Handler::create_tables();
		
		// Seed default language based on site locale
		MAT_Database_Handler::seed_default_language();
		
		// Get the new default language
		$default = MAT_Database_Handler::get_default_language();
		
		wp_send_json_success( array(
			'message' => __( 'Database repaired successfully!', 'multilingual-ai-translator' ),
			'default_language' => $default ? $default['name'] : 'Unknown',
		) );
	}
}
