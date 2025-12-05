<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MAT_Frontend_Handler {
    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        // Enqueue public styles
    }

    public function enqueue_scripts() {
        // Enqueue public scripts
    }
}
