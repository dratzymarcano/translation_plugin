<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MAT_Admin_Settings {
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_settings() {
        // General Settings
        register_setting( 'mat_general_settings', 'mat_default_language' );
        add_settings_section( 'mat_general_section', 'General Configuration', null, 'mat_general_settings' );
        add_settings_field( 'mat_default_language', 'Default Language Code (e.g., en, es)', array( $this, 'render_default_language_field' ), 'mat_general_settings', 'mat_general_section' );

        // API Settings
        register_setting( 'mat_api_settings', 'mat_openrouter_api_key' );
        register_setting( 'mat_api_settings', 'mat_ai_model' );
        
        add_settings_section( 'mat_api_section', 'OpenRouter API Configuration', null, 'mat_api_settings' );
        
        add_settings_field( 'mat_openrouter_api_key', 'OpenRouter API Key', array( $this, 'render_api_key_field' ), 'mat_api_settings', 'mat_api_section' );
        add_settings_field( 'mat_ai_model', 'AI Model', array( $this, 'render_model_field' ), 'mat_api_settings', 'mat_api_section' );
    }

    public function render_default_language_field() {
        $value = get_option( 'mat_default_language', 'en' );
        echo '<input type="text" name="mat_default_language" value="' . esc_attr( $value ) . '" class="regular-text" />';
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
}
