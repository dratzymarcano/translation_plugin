<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MAT_Admin_Menu {
    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            'MultiLingual AI Translator', 
            'AI Translator', 
            'manage_options', 
            $this->plugin_name, 
            array( $this, 'display_plugin_setup_page' ),
            'dashicons-translation',
            30
        );
    }

    public function display_plugin_setup_page() {
        // Include template
    }
}
