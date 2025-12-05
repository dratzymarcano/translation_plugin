<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <?php settings_errors(); ?>

    <div class="nav-tab-wrapper">
        <a href="?page=multilingual-ai-translator&tab=general" class="nav-tab <?php echo ( ! isset( $_GET['tab'] ) || $_GET['tab'] === 'general' ) ? 'nav-tab-active' : ''; ?>">General Settings</a>
        <a href="?page=multilingual-ai-translator&tab=api" class="nav-tab <?php echo ( isset( $_GET['tab'] ) && $_GET['tab'] === 'api' ) ? 'nav-tab-active' : ''; ?>">API Settings</a>
        <a href="?page=multilingual-ai-translator&tab=translation" class="nav-tab <?php echo ( isset( $_GET['tab'] ) && $_GET['tab'] === 'translation' ) ? 'nav-tab-active' : ''; ?>">Translation Settings</a>
        <a href="?page=multilingual-ai-translator&tab=seo" class="nav-tab <?php echo ( isset( $_GET['tab'] ) && $_GET['tab'] === 'seo' ) ? 'nav-tab-active' : ''; ?>">SEO Settings</a>
    </div>

    <form method="post" action="options.php">
        <?php
            $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
            
            if ( $active_tab === 'api' ) {
                settings_fields( 'mat_api_settings' );
                do_settings_sections( 'mat_api_settings' );
            } elseif ( $active_tab === 'translation' ) {
                settings_fields( 'mat_translation_settings' );
                do_settings_sections( 'mat_translation_settings' );
            } elseif ( $active_tab === 'seo' ) {
                settings_fields( 'mat_seo_settings' );
                do_settings_sections( 'mat_seo_settings' );
            } else {
                settings_fields( 'mat_general_settings' );
                do_settings_sections( 'mat_general_settings' );
            }
            
            submit_button();
        ?>
    </form>
</div>
