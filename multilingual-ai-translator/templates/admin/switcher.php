<?php
/**
 * Switcher Settings Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$switcher_type       = get_option( 'mat_switcher_type', 'dropdown' );
$switcher_show_flags = get_option( 'mat_switcher_show_flags', '1' );
$switcher_show_names = get_option( 'mat_switcher_show_names', '1' );
$switcher_show_native = get_option( 'mat_switcher_show_native', '0' );
$switcher_position   = get_option( 'mat_switcher_position', 'none' );
?>
<div class="mat-admin-wrap">
	<div class="mat-admin-header">
		<div class="mat-header-content">
			<h1>
				<span class="dashicons dashicons-menu"></span>
				<?php esc_html_e( 'Language Switcher', 'multilingual-ai-translator' ); ?>
			</h1>
		</div>
	</div>

	<div class="mat-switcher-page">
		<form method="post" action="options.php">
			<?php settings_fields( 'mat_switcher_settings' ); ?>

			<div class="mat-two-columns">
				<!-- Settings Column -->
				<div class="mat-column">
					<div class="mat-card">
						<h2><?php esc_html_e( 'Switcher Settings', 'multilingual-ai-translator' ); ?></h2>

						<div class="mat-form-group">
							<label for="mat_switcher_position"><?php esc_html_e( 'Position', 'multilingual-ai-translator' ); ?></label>
							<select name="mat_switcher_position" id="mat_switcher_position" class="mat-select">
								<option value="none" <?php selected( $switcher_position, 'none' ); ?>><?php esc_html_e( 'Disabled', 'multilingual-ai-translator' ); ?></option>
								<option value="menu" <?php selected( $switcher_position, 'menu' ); ?>><?php esc_html_e( 'Navigation Menu', 'multilingual-ai-translator' ); ?></option>
								<option value="widget" <?php selected( $switcher_position, 'widget' ); ?>><?php esc_html_e( 'Widget Area (use shortcode)', 'multilingual-ai-translator' ); ?></option>
								<option value="floating" <?php selected( $switcher_position, 'floating' ); ?>><?php esc_html_e( 'Floating Button', 'multilingual-ai-translator' ); ?></option>
							</select>
						</div>

						<div class="mat-form-group">
							<label for="mat_switcher_type"><?php esc_html_e( 'Style', 'multilingual-ai-translator' ); ?></label>
							<select name="mat_switcher_type" id="mat_switcher_type" class="mat-select">
								<option value="dropdown" <?php selected( $switcher_type, 'dropdown' ); ?>><?php esc_html_e( 'Dropdown', 'multilingual-ai-translator' ); ?></option>
								<option value="inline" <?php selected( $switcher_type, 'inline' ); ?>><?php esc_html_e( 'Inline List', 'multilingual-ai-translator' ); ?></option>
								<option value="flags-only" <?php selected( $switcher_type, 'flags-only' ); ?>><?php esc_html_e( 'Flags Only', 'multilingual-ai-translator' ); ?></option>
							</select>
						</div>

						<div class="mat-form-group">
							<label><?php esc_html_e( 'Display Options', 'multilingual-ai-translator' ); ?></label>
							<div class="mat-checkbox-group">
								<label class="mat-checkbox">
									<input type="checkbox" name="mat_switcher_show_flags" value="1" <?php checked( $switcher_show_flags, '1' ); ?>>
									<span><?php esc_html_e( 'Show Flags', 'multilingual-ai-translator' ); ?></span>
								</label>
								<label class="mat-checkbox">
									<input type="checkbox" name="mat_switcher_show_names" value="1" <?php checked( $switcher_show_names, '1' ); ?>>
									<span><?php esc_html_e( 'Show Language Names', 'multilingual-ai-translator' ); ?></span>
								</label>
								<label class="mat-checkbox">
									<input type="checkbox" name="mat_switcher_show_native" value="1" <?php checked( $switcher_show_native, '1' ); ?>>
									<span><?php esc_html_e( 'Use Native Names', 'multilingual-ai-translator' ); ?></span>
								</label>
							</div>
						</div>

						<?php submit_button( __( 'Save Settings', 'multilingual-ai-translator' ), 'mat-btn mat-btn-primary' ); ?>
					</div>

					<!-- Shortcode Info -->
					<div class="mat-card">
						<h2><?php esc_html_e( 'Manual Integration', 'multilingual-ai-translator' ); ?></h2>
						<p><?php esc_html_e( 'Use these methods to add the switcher manually:', 'multilingual-ai-translator' ); ?></p>
						
						<div class="mat-code-block">
							<label><?php esc_html_e( 'Shortcode:', 'multilingual-ai-translator' ); ?></label>
							<code>[mat_language_switcher]</code>
						</div>

						<div class="mat-code-block">
							<label><?php esc_html_e( 'PHP Function:', 'multilingual-ai-translator' ); ?></label>
							<code>&lt;?php mat_language_switcher(); ?&gt;</code>
						</div>
					</div>
				</div>

				<!-- Preview Column -->
				<div class="mat-column">
					<div class="mat-card mat-preview-card">
						<h2><?php esc_html_e( 'Live Preview', 'multilingual-ai-translator' ); ?></h2>
						<div class="mat-preview-container">
							<div class="mat-preview-box" id="mat-switcher-preview">
								<?php
								if ( class_exists( 'MAT_Language_Switcher' ) ) {
									MAT_Language_Switcher::render_switcher( true );
								}
								?>
							</div>
						</div>
						<p class="mat-preview-note"><?php esc_html_e( 'This is how the switcher will appear on your site.', 'multilingual-ai-translator' ); ?></p>
					</div>

					<!-- Active Languages for Switcher -->
					<div class="mat-card">
						<h2><?php esc_html_e( 'Languages in Switcher', 'multilingual-ai-translator' ); ?></h2>
						<p><?php esc_html_e( 'Only active languages will appear in the switcher:', 'multilingual-ai-translator' ); ?></p>
						<div class="mat-mini-lang-list">
							<?php foreach ( $languages as $lang ) : ?>
								<div class="mat-mini-lang">
									<img src="https://flagcdn.com/w20/<?php echo esc_attr( $lang->flag_code ); ?>.png" alt="">
									<span><?php echo esc_html( $lang->language_name ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=multilingual-ai-translator-languages' ) ); ?>" class="mat-link">
							<?php esc_html_e( 'Manage languages →', 'multilingual-ai-translator' ); ?>
						</a>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
