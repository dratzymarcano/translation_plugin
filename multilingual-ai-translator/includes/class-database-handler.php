<?php
/**
 * Database Handler - Creates and manages all plugin tables.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MAT_Database_Handler {

	/**
	 * Run on activation.
	 */
	public static function activate() {
		self::create_tables();
		update_option( 'mat_db_version', MAT_VERSION );
	}

	/**
	 * Create all custom tables.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$prefix = $wpdb->prefix;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Languages table
		$table_languages = $prefix . 'mat_languages';
		$sql_languages = "CREATE TABLE $table_languages (
			id INT AUTO_INCREMENT PRIMARY KEY,
			language_code VARCHAR(10) NOT NULL,
			language_name VARCHAR(100) NOT NULL,
			native_name VARCHAR(100),
			flag_code VARCHAR(10),
			is_default TINYINT(1) DEFAULT 0,
			is_active TINYINT(1) DEFAULT 1,
			display_order INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP
		) $charset_collate;";
		dbDelta( $sql_languages );

		// Translations table
		$table_translations = $prefix . 'mat_translations';
		$sql_translations = "CREATE TABLE $table_translations (
			id BIGINT AUTO_INCREMENT PRIMARY KEY,
			object_id BIGINT NOT NULL,
			object_type VARCHAR(50) NOT NULL,
			language_code VARCHAR(10) NOT NULL,
			original_content LONGTEXT,
			translated_content LONGTEXT,
			translation_status VARCHAR(20) DEFAULT 'pending',
			translated_by VARCHAR(50),
			ai_model_used VARCHAR(100),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_object (object_id, object_type, language_code)
		) $charset_collate;";
		dbDelta( $sql_translations );

		// SEO Metadata table (per-page, per-language keywords)
		$table_seo = $prefix . 'mat_seo_meta';
		$sql_seo = "CREATE TABLE $table_seo (
			id BIGINT AUTO_INCREMENT PRIMARY KEY,
			object_id BIGINT NOT NULL,
			object_type VARCHAR(50) NOT NULL,
			language_code VARCHAR(10) NOT NULL,
			meta_title VARCHAR(255),
			meta_description TEXT,
			meta_keywords VARCHAR(500),
			translated_slug VARCHAR(255),
			og_title VARCHAR(255),
			og_description TEXT,
			canonical_url VARCHAR(500),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY unique_seo (object_id, object_type, language_code)
		) $charset_collate;";
		dbDelta( $sql_seo );

		// Translation queue table
		$table_queue = $prefix . 'mat_translation_queue';
		$sql_queue = "CREATE TABLE $table_queue (
			id BIGINT AUTO_INCREMENT PRIMARY KEY,
			object_id BIGINT NOT NULL,
			object_type VARCHAR(50) NOT NULL,
			target_language VARCHAR(10) NOT NULL,
			priority INT DEFAULT 5,
			status VARCHAR(20) DEFAULT 'queued',
			error_message TEXT,
			attempts INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			processed_at DATETIME
		) $charset_collate;";
		dbDelta( $sql_queue );
	}

	/**
	 * Seed EU languages on first activation.
	 */
	public static function seed_eu_languages() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mat_languages';

		// Check if languages already exist
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
		if ( $count > 0 ) {
			return;
		}

		$eu_languages = array(
			array( 'en', 'English', 'English', 'gb', 1, 1, 1 ),
			array( 'de', 'German', 'Deutsch', 'de', 0, 1, 2 ),
			array( 'fr', 'French', 'Français', 'fr', 0, 1, 3 ),
			array( 'es', 'Spanish', 'Español', 'es', 0, 1, 4 ),
			array( 'it', 'Italian', 'Italiano', 'it', 0, 1, 5 ),
			array( 'pt', 'Portuguese', 'Português', 'pt', 0, 1, 6 ),
			array( 'nl', 'Dutch', 'Nederlands', 'nl', 0, 1, 7 ),
			array( 'pl', 'Polish', 'Polski', 'pl', 0, 1, 8 ),
			array( 'ro', 'Romanian', 'Română', 'ro', 0, 1, 9 ),
			array( 'el', 'Greek', 'Ελληνικά', 'gr', 0, 1, 10 ),
			array( 'sv', 'Swedish', 'Svenska', 'se', 0, 1, 11 ),
			array( 'hu', 'Hungarian', 'Magyar', 'hu', 0, 1, 12 ),
			array( 'cs', 'Czech', 'Čeština', 'cz', 0, 1, 13 ),
			array( 'da', 'Danish', 'Dansk', 'dk', 0, 1, 14 ),
			array( 'fi', 'Finnish', 'Suomi', 'fi', 0, 1, 15 ),
			array( 'sk', 'Slovak', 'Slovenčina', 'sk', 0, 1, 16 ),
			array( 'bg', 'Bulgarian', 'Български', 'bg', 0, 1, 17 ),
			array( 'hr', 'Croatian', 'Hrvatski', 'hr', 0, 1, 18 ),
			array( 'lt', 'Lithuanian', 'Lietuvių', 'lt', 0, 1, 19 ),
			array( 'lv', 'Latvian', 'Latviešu', 'lv', 0, 1, 20 ),
			array( 'sl', 'Slovenian', 'Slovenščina', 'si', 0, 1, 21 ),
			array( 'et', 'Estonian', 'Eesti', 'ee', 0, 1, 22 ),
			array( 'mt', 'Maltese', 'Malti', 'mt', 0, 1, 23 ),
			array( 'ga', 'Irish', 'Gaeilge', 'ie', 0, 1, 24 ),
		);

		foreach ( $eu_languages as $lang ) {
			$wpdb->insert(
				$table_name,
				array(
					'language_code' => $lang[0],
					'language_name' => $lang[1],
					'native_name'   => $lang[2],
					'flag_code'     => $lang[3],
					'is_default'    => $lang[4],
					'is_active'     => $lang[5],
					'display_order' => $lang[6],
				)
			);
		}
	}

	/**
	 * Check and recreate tables if needed.
	 */
	public static function check_tables_exist() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mat_languages';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			self::create_tables();
			self::seed_eu_languages();
		}
	}

	/**
	 * Get all active languages.
	 */
	public static function get_active_languages() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mat_languages';
		return $wpdb->get_results( "SELECT * FROM $table_name WHERE is_active = 1 ORDER BY display_order ASC" );
	}

	/**
	 * Get all languages.
	 */
	public static function get_all_languages() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mat_languages';
		return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY display_order ASC" );
	}

	/**
	 * Get default language.
	 */
	public static function get_default_language() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mat_languages';
		return $wpdb->get_row( "SELECT * FROM $table_name WHERE is_default = 1 LIMIT 1" );
	}

	/**
	 * Save SEO meta for a specific post/page and language.
	 */
	public static function save_seo_meta( $object_id, $object_type, $language_code, $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mat_seo_meta';

		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM $table_name WHERE object_id = %d AND object_type = %s AND language_code = %s",
			$object_id, $object_type, $language_code
		) );

		$record = array(
			'object_id'        => $object_id,
			'object_type'      => $object_type,
			'language_code'    => $language_code,
			'meta_title'       => sanitize_text_field( $data['meta_title'] ?? '' ),
			'meta_description' => sanitize_textarea_field( $data['meta_description'] ?? '' ),
			'meta_keywords'    => sanitize_text_field( $data['meta_keywords'] ?? '' ),
			'translated_slug'  => sanitize_title( $data['translated_slug'] ?? '' ),
			'og_title'         => sanitize_text_field( $data['og_title'] ?? '' ),
			'og_description'   => sanitize_textarea_field( $data['og_description'] ?? '' ),
		);

		if ( $existing ) {
			$wpdb->update( $table_name, $record, array( 'id' => $existing->id ) );
		} else {
			$wpdb->insert( $table_name, $record );
		}
	}

	/**
	 * Get SEO meta for a specific post/page and language.
	 */
	public static function get_seo_meta( $object_id, $object_type, $language_code ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mat_seo_meta';
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE object_id = %d AND object_type = %s AND language_code = %s",
			$object_id, $object_type, $language_code
		) );
	}

	/**
	 * Get all SEO meta for a specific post/page.
	 */
	public static function get_all_seo_meta_for_post( $object_id, $object_type = 'post' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mat_seo_meta';
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE object_id = %d AND object_type = %s",
			$object_id, $object_type
		) );
	}
}
