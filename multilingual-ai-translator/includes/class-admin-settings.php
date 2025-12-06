<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MAT_Admin_Settings {
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_language_actions' ) );
    }

    public function register_settings() {
        // --- General Settings ---
        register_setting( 'mat_general_settings', 'mat_default_language' );
        register_setting( 'mat_general_settings', 'mat_switcher_type' );
        register_setting( 'mat_general_settings', 'mat_show_flags' );

        add_settings_section( 'mat_general_section', 'General Configuration', null, 'mat_general_settings' );
        add_settings_field( 'mat_default_language', 'Default Language Code', array( $this, 'render_default_language_field' ), 'mat_general_settings', 'mat_general_section' );
        
        // Language Switcher Section
        add_settings_section( 'mat_switcher_section', 'Language Switcher Settings', null, 'mat_general_settings' );
        add_settings_field( 'mat_switcher_type', 'Switcher Type', array( $this, 'render_switcher_type_field' ), 'mat_general_settings', 'mat_switcher_section' );
        add_settings_field( 'mat_show_flags', 'Show Flags', array( $this, 'render_show_flags_field' ), 'mat_general_settings', 'mat_switcher_section' );

        // Active Languages Management (Custom Render)
        add_settings_section( 'mat_languages_section', 'Active Languages', array( $this, 'render_languages_section' ), 'mat_general_settings' );

        // --- API Settings ---
        register_setting( 'mat_api_settings', 'mat_openrouter_api_key' );
        register_setting( 'mat_api_settings', 'mat_ai_model' );
        
        add_settings_section( 'mat_api_section', 'OpenRouter API Configuration', null, 'mat_api_settings' );
        add_settings_field( 'mat_openrouter_api_key', 'OpenRouter API Key', array( $this, 'render_api_key_field' ), 'mat_api_settings', 'mat_api_section' );
        add_settings_field( 'mat_ai_model', 'AI Model', array( $this, 'render_model_field' ), 'mat_api_settings', 'mat_api_section' );

        // --- Translation Settings ---
        register_setting( 'mat_translation_settings', 'mat_auto_translate' );
        add_settings_section( 'mat_translation_section', 'Translation Preferences', null, 'mat_translation_settings' );
        add_settings_field( 'mat_auto_translate', 'Auto-Translate New Content', array( $this, 'render_auto_translate_field' ), 'mat_translation_settings', 'mat_translation_section' );

        // --- SEO Settings ---
        register_setting( 'mat_seo_settings', 'mat_enable_hreflang' );
        add_settings_section( 'mat_seo_section', 'SEO Configuration', null, 'mat_seo_settings' );
        add_settings_field( 'mat_enable_hreflang', 'Enable Hreflang Tags', array( $this, 'render_hreflang_field' ), 'mat_seo_settings', 'mat_seo_section' );
    }

    // --- Render Callbacks ---

    public function render_default_language_field() {
        $value = get_option( 'mat_default_language', 'en' );
        echo '<input type="text" name="mat_default_language" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">e.g., en, es, fr</p>';
    }

    public function render_switcher_type_field() {
        $value = get_option( 'mat_switcher_type', 'dropdown' );
        echo '<select name="mat_switcher_type">';
        echo '<option value="dropdown" ' . selected( $value, 'dropdown', false ) . '>Dropdown</option>';
        echo '<option value="flags" ' . selected( $value, 'flags', false ) . '>Flags Only</option>';
        echo '<option value="list" ' . selected( $value, 'list', false ) . '>List of Names</option>';
        echo '</select>';
    }

    public function render_show_flags_field() {
        $value = get_option( 'mat_show_flags', '1' );
        echo '<input type="checkbox" name="mat_show_flags" value="1" ' . checked( $value, '1', false ) . ' /> Display flags in switcher';
    }

    public function render_languages_section() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mat_languages';
        $languages = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY display_order ASC" );

        echo '<table class="widefat fixed striped" style="margin-bottom: 20px;">';
        echo '<thead><tr><th>Code</th><th>Name</th><th>Active</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        if ( $languages ) {
            foreach ( $languages as $lang ) {
                echo '<tr>';
                echo '<td>' . esc_html( $lang->language_code ) . '</td>';
                echo '<td>' . esc_html( $lang->language_name ) . '</td>';
                echo '<td>' . ( $lang->is_active ? 'Yes' : 'No' ) . '</td>';
                echo '<td><a href="' . wp_nonce_url( admin_url( 'admin.php?page=multilingual-ai-translator&action=delete_lang&id=' . $lang->id ), 'delete_lang_' . $lang->id ) . '" class="button button-small delete-lang" onclick="return confirm(\'Are you sure?\')">Delete</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4">No languages added yet.</td></tr>';
        }
        echo '</tbody></table>';

        // Add New Language Form
        echo '<h4>Add New Language</h4>';
        echo '<div style="display: flex; gap: 10px; align-items: center;">';
        echo '<input type="text" name="new_lang_code" placeholder="Code (e.g., fr)" style="max-width: 100px;">';
        echo '<input type="text" name="new_lang_name" placeholder="Name (e.g., French)">';
        echo '<input type="submit" name="add_language" class="button button-secondary" value="Add Language">';
        echo '</div>';
        echo '<p class="description">Adding a language will create a record in the database.</p>';
    }

    public function handle_language_actions() {
        if ( isset( $_POST['add_language'] ) && isset( $_POST['new_lang_code'] ) && ! empty( $_POST['new_lang_code'] ) ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            // Verify nonce (using the settings page nonce usually, but here we are inside the form)
            check_admin_referer( 'mat_general_settings-options' );

            global $wpdb;
            $table_name = $wpdb->prefix . 'mat_languages';
            
            $code = sanitize_text_field( $_POST['new_lang_code'] );
            $name = sanitize_text_field( $_POST['new_lang_name'] );
            
            $wpdb->insert( 
                $table_name, 
                array( 
                    'language_code' => $code, 
                    'language_name' => $name,
                    'is_active' => 1 
                ) 
            );
        }

        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_lang' && isset( $_GET['id'] ) ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            $id = intval( $_GET['id'] );
            check_admin_referer( 'delete_lang_' . $id );

            global $wpdb;
            $table_name = $wpdb->prefix . 'mat_languages';
            $wpdb->delete( $table_name, array( 'id' => $id ) );
            
            wp_redirect( admin_url( 'admin.php?page=multilingual-ai-translator&tab=general' ) );
            exit;
        }
    }

    public function render_api_key_field() {
        $value = get_option( 'mat_openrouter_api_key' );
        echo '<input type="password" name="mat_openrouter_api_key" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">Enter your OpenRouter API key here.</p>';
    }

    public function render_model_field() {
        $value = get_option( 'mat_ai_model', 'anthropic/claude-3-sonnet' );
        $models = array(
            'anthropic/claude-3-sonnet' => 'Claude 3 Sonnet',
            'openai/gpt-4-turbo' => 'GPT-4 Turbo',
            'google/gemini-pro' => 'Gemini Pro',
        );
        
        echo '<select name="mat_ai_model">';
        foreach ( $models as $key => $label ) {
            $selected = selected( $value, $key, false );
            echo '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
    }

    public function render_auto_translate_field() {
        $value = get_option( 'mat_auto_translate', '0' );
        echo '<input type="checkbox" name="mat_auto_translate" value="1" ' . checked( $value, '1', false ) . ' /> Automatically translate new content';
    }

    public function render_hreflang_field() {
        $value = get_option( 'mat_enable_hreflang', '1' );
        echo '<input type="checkbox" name="mat_enable_hreflang" value="1" ' . checked( $value, '1', false ) . ' /> Add hreflang tags to site header';
    }
}
