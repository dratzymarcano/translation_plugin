<?php
/**
 * Switcher Settings Template - v3.0.0
 * Modern, Clean, Robust.
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

// 1. Handle Form Submission
$success_message = '';
if ( isset( $_POST['mat_save_switcher'] ) && check_admin_referer( 'mat_switcher_settings', 'mat_switcher_nonce' ) ) {
    $new_settings = array(
        'position'    => sanitize_text_field( $_POST['switcher_position'] ),
        'style'       => sanitize_text_field( $_POST['switcher_style'] ),
        'show_flags'  => isset( $_POST['switcher_show_flags'] ) ? 1 : 0,
        'show_names'  => isset( $_POST['switcher_show_names'] ) ? 1 : 0,
    );
    update_option( 'mat_switcher_settings', $new_settings );
    $success_message = __( 'Settings saved successfully!', 'multilingual-ai-translator' );
}

// 2. Retrieve Settings (Safe Defaults)
$settings = get_option( 'mat_switcher_settings', array() );
$position = isset( $settings['position'] ) ? $settings['position'] : 'floating';
$style    = isset( $settings['style'] ) ? $settings['style'] : 'dropdown';
$flags    = isset( $settings['show_flags'] ) ? $settings['show_flags'] : 1;
$names    = isset( $settings['show_names'] ) ? $settings['show_names'] : 1;

// 3. Render UI
?>
<div class="mat-v3-wrap">
    
    <!-- Header -->
    <div class="mat-v3-header">
        <div class="mat-v3-title">
            <h1><span class="dashicons dashicons-translation"></span> <?php esc_html_e( 'Language Switcher', 'multilingual-ai-translator' ); ?></h1>
            <p class="mat-v3-subtitle"><?php esc_html_e( 'Customize how your visitors switch languages.', 'multilingual-ai-translator' ); ?></p>
        </div>
        <div>
            <!-- Header Actions if needed -->
        </div>
    </div>

    <?php if ( $success_message ) : ?>
        <div class="notice notice-success is-dismissible" style="margin-left: 0; margin-bottom: 20px;">
            <p><?php echo esc_html( $success_message ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'mat_switcher_settings', 'mat_switcher_nonce' ); ?>
        
        <div class="mat-v3-grid">
            
            <!-- Main Settings -->
            <div class="mat-v3-column">
                
                <div class="mat-v3-card">
                    <div class="mat-v3-card-header">
                        <h2 class="mat-v3-card-title"><?php esc_html_e( 'Appearance', 'multilingual-ai-translator' ); ?></h2>
                    </div>

                    <div class="mat-v3-form-group">
                        <label class="mat-v3-label" for="switcher_position"><?php esc_html_e( 'Position', 'multilingual-ai-translator' ); ?></label>
                        <select name="switcher_position" id="switcher_position" class="mat-v3-select">
                            <option value="floating" <?php selected( $position, 'floating' ); ?>><?php esc_html_e( 'Floating Button (Bottom Right)', 'multilingual-ai-translator' ); ?></option>
                            <option value="shortcode" <?php selected( $position, 'shortcode' ); ?>><?php esc_html_e( 'Shortcode Only [mat_switcher]', 'multilingual-ai-translator' ); ?></option>
                            <option value="menu" <?php selected( $position, 'menu' ); ?>><?php esc_html_e( 'Main Menu (Auto-Inject)', 'multilingual-ai-translator' ); ?></option>
                        </select>
                        <p class="mat-v3-help"><?php esc_html_e( 'Choose where the switcher appears on your site.', 'multilingual-ai-translator' ); ?></p>
                    </div>

                    <div class="mat-v3-form-group">
                        <label class="mat-v3-label" for="switcher_style"><?php esc_html_e( 'Style', 'multilingual-ai-translator' ); ?></label>
                        <select name="switcher_style" id="switcher_style" class="mat-v3-select">
                            <option value="dropdown" <?php selected( $style, 'dropdown' ); ?>><?php esc_html_e( 'Dropdown Menu', 'multilingual-ai-translator' ); ?></option>
                            <option value="inline" <?php selected( $style, 'inline' ); ?>><?php esc_html_e( 'Inline List', 'multilingual-ai-translator' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="mat-v3-card">
                    <div class="mat-v3-card-header">
                        <h2 class="mat-v3-card-title"><?php esc_html_e( 'Elements', 'multilingual-ai-translator' ); ?></h2>
                    </div>

                    <div class="mat-v3-form-group">
                        <label class="mat-v3-toggle">
                            <input type="checkbox" name="switcher_show_flags" value="1" <?php checked( $flags, 1 ); ?>>
                            <span class="mat-v3-toggle-slider"></span>
                            <span class="mat-v3-label-text" style="margin-left: 10px;"><?php esc_html_e( 'Show Flags', 'multilingual-ai-translator' ); ?></span>
                        </label>
                    </div>

                    <div class="mat-v3-form-group">
                        <label class="mat-v3-toggle">
                            <input type="checkbox" name="switcher_show_names" value="1" <?php checked( $names, 1 ); ?>>
                            <span class="mat-v3-toggle-slider"></span>
                            <span class="mat-v3-label-text" style="margin-left: 10px;"><?php esc_html_e( 'Show Language Names', 'multilingual-ai-translator' ); ?></span>
                        </label>
                    </div>
                </div>

                <button type="submit" name="mat_save_switcher" class="mat-v3-btn mat-v3-btn-primary">
                    <span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Changes', 'multilingual-ai-translator' ); ?>
                </button>

            </div>

            <!-- Preview / Info -->
            <div class="mat-v3-column">
                <div class="mat-v3-card">
                    <div class="mat-v3-card-header">
                        <h2 class="mat-v3-card-title"><?php esc_html_e( 'Live Preview', 'multilingual-ai-translator' ); ?></h2>
                    </div>
                    <div class="mat-v3-preview-box">
                        <div class="mat-preview-switcher">
                            <?php if ( $flags ) : ?>
                                <img src="<?php echo esc_url( MAT_PLUGIN_URL . 'assets/flags/us.svg' ); ?>" class="mat-preview-flag" alt="US">
                            <?php endif; ?>
                            <?php if ( $names ) : ?>
                                <span>English</span>
                            <?php endif; ?>
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                    </div>
                    <p class="mat-v3-help" style="text-align: center;"><?php esc_html_e( 'This is a visual approximation.', 'multilingual-ai-translator' ); ?></p>
                </div>
            </div>

        </div>
    </form>
</div>
