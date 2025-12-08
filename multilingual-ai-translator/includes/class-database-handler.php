<?php
/**
 * Database Handler - Creates and manages all plugin tables.
 *
 * @package MultiLingual_AI_Translator
 * @since 2.0.0
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
            code VARCHAR(10) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            native_name VARCHAR(100),
            flag VARCHAR(10),
            is_default TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta( $sql_languages );

        // Post translations table - stores translated content for posts/pages
        $table_translations = $prefix . 'mat_post_translations';
        $sql_translations = "CREATE TABLE $table_translations (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT NOT NULL,
            language_code VARCHAR(10) NOT NULL,
            translated_title VARCHAR(500),
            translated_content LONGTEXT,
            translated_excerpt TEXT,
            translated_slug VARCHAR(255),
            meta_title VARCHAR(255),
            meta_description TEXT,
            meta_keywords VARCHAR(500),
            og_title VARCHAR(255),
            og_description TEXT,
            translation_status ENUM('auto', 'edited', 'pending') DEFAULT 'pending',
            ai_model VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_translation (post_id, language_code),
            INDEX idx_post (post_id),
            INDEX idx_lang (language_code),
            INDEX idx_slug (translated_slug)
        ) $charset_collate;";
        dbDelta( $sql_translations );

        // String translations table - for themes, widgets, menus
        $table_strings = $prefix . 'mat_string_translations';
        $sql_strings = "CREATE TABLE $table_strings (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            string_key VARCHAR(255) NOT NULL,
            original_string TEXT NOT NULL,
            context VARCHAR(100),
            language_code VARCHAR(10) NOT NULL,
            translated_string TEXT,
            translation_status ENUM('auto', 'edited', 'pending') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_string (string_key(191), language_code),
            INDEX idx_context (context)
        ) $charset_collate;";
        dbDelta( $sql_strings );

        // Translation queue table
        $table_queue = $prefix . 'mat_translation_queue';
        $sql_queue = "CREATE TABLE $table_queue (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT NOT NULL,
            language_code VARCHAR(10) NOT NULL,
            priority INT DEFAULT 5,
            status ENUM('queued', 'processing', 'completed', 'failed') DEFAULT 'queued',
            error_message TEXT,
            attempts INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME,
            UNIQUE KEY unique_queue (post_id, language_code),
            INDEX idx_status (status)
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
            array( 'en', 'English', 'English', '🇬🇧', 1, 1, 1 ),
            array( 'de', 'German', 'Deutsch', '🇩🇪', 0, 1, 2 ),
            array( 'fr', 'French', 'Français', '🇫🇷', 0, 1, 3 ),
            array( 'es', 'Spanish', 'Español', '🇪🇸', 0, 1, 4 ),
            array( 'it', 'Italian', 'Italiano', '🇮🇹', 0, 1, 5 ),
            array( 'pt', 'Portuguese', 'Português', '🇵🇹', 0, 1, 6 ),
            array( 'nl', 'Dutch', 'Nederlands', '🇳🇱', 0, 1, 7 ),
            array( 'pl', 'Polish', 'Polski', '🇵🇱', 0, 1, 8 ),
            array( 'ro', 'Romanian', 'Română', '🇷🇴', 0, 1, 9 ),
            array( 'el', 'Greek', 'Ελληνικά', '🇬🇷', 0, 1, 10 ),
            array( 'sv', 'Swedish', 'Svenska', '🇸🇪', 0, 1, 11 ),
            array( 'hu', 'Hungarian', 'Magyar', '🇭🇺', 0, 1, 12 ),
            array( 'cs', 'Czech', 'Čeština', '🇨🇿', 0, 1, 13 ),
            array( 'da', 'Danish', 'Dansk', '🇩🇰', 0, 1, 14 ),
            array( 'fi', 'Finnish', 'Suomi', '🇫🇮', 0, 1, 15 ),
            array( 'sk', 'Slovak', 'Slovenčina', '🇸🇰', 0, 1, 16 ),
            array( 'bg', 'Bulgarian', 'Български', '🇧🇬', 0, 1, 17 ),
            array( 'hr', 'Croatian', 'Hrvatski', '🇭🇷', 0, 1, 18 ),
            array( 'lt', 'Lithuanian', 'Lietuvių', '🇱🇹', 0, 1, 19 ),
            array( 'lv', 'Latvian', 'Latviešu', '🇱🇻', 0, 1, 20 ),
            array( 'sl', 'Slovenian', 'Slovenščina', '🇸🇮', 0, 1, 21 ),
            array( 'et', 'Estonian', 'Eesti', '🇪🇪', 0, 1, 22 ),
            array( 'mt', 'Maltese', 'Malti', '🇲🇹', 0, 1, 23 ),
            array( 'ga', 'Irish', 'Gaeilge', '🇮🇪', 0, 1, 24 ),
        );

        foreach ( $eu_languages as $lang ) {
            $wpdb->insert(
                $table_name,
                array(
                    'code'        => $lang[0],
                    'name'        => $lang[1],
                    'native_name' => $lang[2],
                    'flag'        => $lang[3],
                    'is_default'  => $lang[4],
                    'is_active'   => $lang[5],
                    'sort_order'  => $lang[6],
                )
            );
        }
    }

    /**
     * Check and recreate tables if needed, verify structure.
     */
    public static function check_tables_exist() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_languages';
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
        
        if ( ! $table_exists ) {
            self::create_tables();
            self::seed_default_language();
            return;
        }
        
        // Verify table has correct columns
        $columns = $wpdb->get_results( "SHOW COLUMNS FROM $table_name" );
        $column_names = array_map( function( $col ) { return $col->Field; }, $columns );
        
        $required_columns = array( 'id', 'code', 'name', 'native_name', 'flag', 'is_default', 'is_active', 'sort_order' );
        $missing_columns = array_diff( $required_columns, $column_names );
        
        if ( ! empty( $missing_columns ) ) {
            // Try to update table structure first using dbDelta
            self::create_tables();
            
            // Check again
            $columns = $wpdb->get_results( "SHOW COLUMNS FROM $table_name" );
            $column_names = array_map( function( $col ) { return $col->Field; }, $columns );
            $missing_columns = array_diff( $required_columns, $column_names );
            
            if ( ! empty( $missing_columns ) ) {
                // Still missing? Then drop and recreate (destructive but necessary)
                $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
                self::create_tables();
                self::seed_default_language();
            }
            return;
        }
        
        // Check if there are any languages, if not seed default
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
        if ( $count == 0 ) {
            self::seed_default_language();
        }
    }

    /**
     * Seed default language based on WordPress site language.
     */
    public static function seed_default_language() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_languages';
        
        // Get WordPress locale
        $locale = get_locale();
        $lang_code = substr( $locale, 0, 2 ); // e.g., 'en' from 'en_US', 'de' from 'de_DE'
        
        // Language data mapping
        $language_data = array(
            'en' => array( 'English', 'English', 'gb' ),
            'de' => array( 'German', 'Deutsch', 'de' ),
            'fr' => array( 'French', 'Français', 'fr' ),
            'es' => array( 'Spanish', 'Español', 'es' ),
            'it' => array( 'Italian', 'Italiano', 'it' ),
            'pt' => array( 'Portuguese', 'Português', 'pt' ),
            'nl' => array( 'Dutch', 'Nederlands', 'nl' ),
            'pl' => array( 'Polish', 'Polski', 'pl' ),
            'ro' => array( 'Romanian', 'Română', 'ro' ),
            'el' => array( 'Greek', 'Ελληνικά', 'gr' ),
            'sv' => array( 'Swedish', 'Svenska', 'se' ),
            'hu' => array( 'Hungarian', 'Magyar', 'hu' ),
            'cs' => array( 'Czech', 'Čeština', 'cz' ),
            'da' => array( 'Danish', 'Dansk', 'dk' ),
            'fi' => array( 'Finnish', 'Suomi', 'fi' ),
            'sk' => array( 'Slovak', 'Slovenčina', 'sk' ),
            'bg' => array( 'Bulgarian', 'Български', 'bg' ),
            'hr' => array( 'Croatian', 'Hrvatski', 'hr' ),
            'lt' => array( 'Lithuanian', 'Lietuvių', 'lt' ),
            'lv' => array( 'Latvian', 'Latviešu', 'lv' ),
            'sl' => array( 'Slovenian', 'Slovenščina', 'si' ),
            'et' => array( 'Estonian', 'Eesti', 'ee' ),
            'mt' => array( 'Maltese', 'Malti', 'mt' ),
            'ga' => array( 'Irish', 'Gaeilge', 'ie' ),
            'ru' => array( 'Russian', 'Русский', 'ru' ),
            'uk' => array( 'Ukrainian', 'Українська', 'ua' ),
            'ja' => array( 'Japanese', '日本語', 'jp' ),
            'ko' => array( 'Korean', '한국어', 'kr' ),
            'zh' => array( 'Chinese', '中文', 'cn' ),
            'ar' => array( 'Arabic', 'العربية', 'sa' ),
            'tr' => array( 'Turkish', 'Türkçe', 'tr' ),
            'vi' => array( 'Vietnamese', 'Tiếng Việt', 'vn' ),
            'th' => array( 'Thai', 'ไทย', 'th' ),
            'hi' => array( 'Hindi', 'हिन्दी', 'in' ),
        );
        
        // Get language info or default to English
        if ( isset( $language_data[ $lang_code ] ) ) {
            $lang_info = $language_data[ $lang_code ];
        } else {
            $lang_code = 'en';
            $lang_info = $language_data['en'];
        }
        
        // Insert default language
        $wpdb->insert(
            $table_name,
            array(
                'code'        => $lang_code,
                'name'        => $lang_info[0],
                'native_name' => $lang_info[1],
                'flag'        => $lang_info[2],
                'is_default'  => 1,
                'is_active'   => 1,
                'sort_order'  => 1,
            )
        );
    }

    // =========================================================================
    // LANGUAGE METHODS
    // =========================================================================

    /**
     * Get all active languages.
     */
    public static function get_active_languages() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_languages';
        return $wpdb->get_results( 
            "SELECT * FROM $table_name WHERE is_active = 1 ORDER BY sort_order ASC",
            ARRAY_A
        );
    }

    /**
     * Get all languages.
     */
    public static function get_all_languages() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_languages';
        return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY sort_order ASC", ARRAY_A );
    }

    /**
     * Get default language.
     */
    public static function get_default_language() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_languages';
        return $wpdb->get_row( 
            "SELECT * FROM $table_name WHERE is_default = 1 LIMIT 1",
            ARRAY_A
        );
    }

    /**
     * Get language by code.
     */
    public static function get_language( $code ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_languages';
        return $wpdb->get_row( 
            $wpdb->prepare( "SELECT * FROM $table_name WHERE code = %s", $code ),
            ARRAY_A
        );
    }

    // =========================================================================
    // TRANSLATION METHODS
    // =========================================================================

    /**
     * Save post translation.
     */
    public static function save_post_translation( $post_id, $language_code, $data ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_post_translations';

        $record = array(
            'post_id'            => $post_id,
            'language_code'      => $language_code,
            'translated_title'   => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
            'translated_content' => isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '',
            'translated_excerpt' => isset( $data['excerpt'] ) ? sanitize_textarea_field( $data['excerpt'] ) : '',
            'translated_slug'    => isset( $data['slug'] ) ? sanitize_title( $data['slug'] ) : '',
            'meta_title'         => isset( $data['meta_title'] ) ? sanitize_text_field( $data['meta_title'] ) : '',
            'meta_description'   => isset( $data['meta_description'] ) ? sanitize_textarea_field( $data['meta_description'] ) : '',
            'meta_keywords'      => isset( $data['meta_keywords'] ) ? sanitize_text_field( $data['meta_keywords'] ) : '',
            'og_title'           => isset( $data['og_title'] ) ? sanitize_text_field( $data['og_title'] ) : '',
            'og_description'     => isset( $data['og_description'] ) ? sanitize_textarea_field( $data['og_description'] ) : '',
            'translation_status' => isset( $data['status'] ) ? $data['status'] : 'pending',
            'ai_model'           => isset( $data['ai_model'] ) ? sanitize_text_field( $data['ai_model'] ) : '',
            'updated_at'         => current_time( 'mysql' ),
        );

        // Check if exists
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table_name WHERE post_id = %d AND language_code = %s",
            $post_id, $language_code
        ) );

        if ( $existing ) {
            $wpdb->update( $table_name, $record, array( 'id' => $existing ) );
            return $existing;
        } else {
            $record['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_name, $record );
            return $wpdb->insert_id;
        }
    }

    /**
     * Get post translation.
     */
    public static function get_post_translation( $post_id, $language_code ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_post_translations';
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_id = %d AND language_code = %s",
            $post_id, $language_code
        ), ARRAY_A );
    }

    /**
     * Get all translations for a post.
     */
    public static function get_all_post_translations( $post_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_post_translations';
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_id = %d",
            $post_id
        ), ARRAY_A );
    }

    /**
     * Get post by translated slug.
     */
    public static function get_post_by_translated_slug( $slug, $language_code ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_post_translations';
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE translated_slug = %s AND language_code = %s",
            $slug, $language_code
        ), ARRAY_A );
    }

    /**
     * Delete post translations.
     */
    public static function delete_post_translations( $post_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_post_translations';
        return $wpdb->delete( $table_name, array( 'post_id' => $post_id ) );
    }

    // =========================================================================
    // QUEUE METHODS
    // =========================================================================

    /**
     * Add to translation queue.
     */
    public static function add_to_queue( $post_id, $language_code, $priority = 5 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_translation_queue';

        // Check if already in queue
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table_name WHERE post_id = %d AND language_code = %s AND status IN ('queued', 'processing')",
            $post_id, $language_code
        ) );

        if ( $existing ) {
            return $existing;
        }

        $wpdb->insert( $table_name, array(
            'post_id'       => $post_id,
            'language_code' => $language_code,
            'priority'      => $priority,
            'status'        => 'queued',
            'created_at'    => current_time( 'mysql' ),
        ) );

        return $wpdb->insert_id;
    }

    /**
     * Get next item from queue.
     */
    public static function get_next_queue_item() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_translation_queue';
        return $wpdb->get_row(
            "SELECT * FROM $table_name WHERE status = 'queued' ORDER BY priority ASC, created_at ASC LIMIT 1",
            ARRAY_A
        );
    }

    /**
     * Update queue item status.
     */
    public static function update_queue_status( $id, $status, $error = '' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_translation_queue';
        
        $data = array( 'status' => $status );
        if ( $status === 'completed' || $status === 'failed' ) {
            $data['processed_at'] = current_time( 'mysql' );
        }
        if ( $error ) {
            $data['error_message'] = $error;
        }
        if ( $status === 'failed' ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE $table_name SET attempts = attempts + 1 WHERE id = %d",
                $id
            ) );
        }

        return $wpdb->update( $table_name, $data, array( 'id' => $id ) );
    }

    /**
     * Get queue stats.
     */
    public static function get_queue_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_translation_queue';
        
        return array(
            'queued'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'queued'" ),
            'processing' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'processing'" ),
            'completed'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'completed'" ),
            'failed'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'failed'" ),
        );
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    /**
     * Get translation statistics.
     */
    public static function get_translation_stats() {
        global $wpdb;
        $trans_table = $wpdb->prefix . 'mat_post_translations';
        $lang_table = $wpdb->prefix . 'mat_languages';

        $stats = array(
            'total_translations' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $trans_table" ),
            'auto_translations'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $trans_table WHERE translation_status = 'auto'" ),
            'edited_translations' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $trans_table WHERE translation_status = 'edited'" ),
            'pending_translations' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $trans_table WHERE translation_status = 'pending'" ),
            'active_languages'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $lang_table WHERE is_active = 1" ),
        );

        // Get per-language counts
        $stats['by_language'] = $wpdb->get_results(
            "SELECT language_code, COUNT(*) as count FROM $trans_table GROUP BY language_code",
            ARRAY_A
        );

        return $stats;
    }
}
