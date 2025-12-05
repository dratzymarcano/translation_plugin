<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MAT_Database_Handler {

	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$prefix = $wpdb->prefix;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

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
			date_format VARCHAR(50),
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
			translation_status ENUM('pending', 'auto', 'reviewed', 'manual') DEFAULT 'pending',
			translated_by VARCHAR(50),
			ai_model_used VARCHAR(100),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_object (object_id, object_type, language_code)
		) $charset_collate;";
		dbDelta( $sql_translations );

		// SEO Metadata table
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
			updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY unique_seo (object_id, object_type, language_code)
		) $charset_collate;";
		dbDelta( $sql_seo );

		// Translation strings table
		$table_strings = $prefix . 'mat_strings';
		$sql_strings = "CREATE TABLE $table_strings (
			id BIGINT AUTO_INCREMENT PRIMARY KEY,
			string_key VARCHAR(255) NOT NULL,
			original_string TEXT NOT NULL,
			context VARCHAR(255),
			domain VARCHAR(100),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY unique_string (string_key, domain)
		) $charset_collate;";
		dbDelta( $sql_strings );

		// Translation queue table
		$table_queue = $prefix . 'mat_translation_queue';
		$sql_queue = "CREATE TABLE $table_queue (
			id BIGINT AUTO_INCREMENT PRIMARY KEY,
			object_id BIGINT NOT NULL,
			object_type VARCHAR(50) NOT NULL,
			target_language VARCHAR(10) NOT NULL,
			priority INT DEFAULT 5,
			status ENUM('queued', 'processing', 'completed', 'failed') DEFAULT 'queued',
			error_message TEXT,
			attempts INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			processed_at DATETIME
		) $charset_collate;";
		dbDelta( $sql_queue );
	}
}
