<?php
/**
 * Switcher Settings Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle form submission FIRST (before loading settings)
$settings_saved = false;
if ( isset( $_POST['mat_save_switcher'] ) && isset( $_POST['mat_switcher_nonce'] ) && wp_verify_nonce( $_POST['mat_switcher_nonce'], 'mat_switcher_settings' ) ) {
	$new_settings = array(
		'position'    => sanitize_text_field( $_POST['switcher_position'] ),
		'style'       => sanitize_text_field( $_POST['switcher_style'] ),
		'show_flags'  => isset( $_POST['switcher_show_flags'] ) ? 1 : 0,
		'show_names'  => isset( $_POST['switcher_show_names'] ) ? 1 : 0,
	);
	
	update_option( 'mat_switcher_settings', $new_settings );
	$settings_saved = true;
}

// Get settings with proper option names
$settings = get_option( 'mat_switcher_settings', array() );
$switcher_type       = isset( $settings['style'] ) ? $settings['style'] : 'dropdown';
$switcher_show_flags = isset( $settings['show_flags'] ) ? $settings['show_flags'] : 1;
$switcher_show_names = isset( $settings['show_names'] ) ? $settings['show_names'] : 1;
$switcher_position   = isset( $settings['position'] ) ? $settings['position'] : 'floating';

// Get active languages - ensure it's available
if ( class_exists( 'MAT_Database_Handler' ) ) {
    $active_languages = MAT_Database_Handler::get_active_languages();
} else {
    $active_languages = array();
}

// Ensure active_languages is an array
if ( ! is_array( $active_languages ) ) {
    $active_languages = array();
}

// --- DEBUG / SAFETY WRAPPER ---
try {
?>
<div class="mat-admin-wrap">
	<div class="mat-admin-header">
		<div class="mat-header-content">
			<h1>
				<span class="dashicons dashicons-menu"></span>
				<?php esc_html_e( 'Language Switcher', 'multilingual-ai-translator' ); ?>
			</h1>
			<p class="mat-subtitle"><?php esc_html_e( 'Configure how visitors switch between languages', 'multilingual-ai-translator' ); ?></p>
		</div>
	</div>

	<?php if ( $settings_saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully!', 'multilingual-ai-translator' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="mat-switcher-page">
		<form method="post" action="">
			<?php wp_nonce_field( 'mat_switcher_settings', 'mat_switcher_nonce' ); ?>

			<div class="mat-two-columns">
				<!-- Settings Column -->
				<div class="mat-column">
					<div class="mat-card">
						<h2>
							<span class="dashicons dashicons-admin-appearance"></span>
							<?php esc_html_e( 'Switcher Settings', 'multilingual-ai-translator' ); ?>
						</h2>

						<div class="mat-form-group">
							<label for="switcher_position"><?php esc_html_e( 'Display Position', 'multilingual-ai-translator' ); ?></label>
							<select name="switcher_position" id="switcher_position" class="mat-select">
								<option value="none" <?php selected( $switcher_position, 'none' ); ?>><?php esc_html_e( '🚫 Disabled', 'multilingual-ai-translator' ); ?></option>
								<option value="floating" <?php selected( $switcher_position, 'floating' ); ?>><?php esc_html_e( '💬 Floating Button (Recommended)', 'multilingual-ai-translator' ); ?></option>
								<option value="menu" <?php selected( $switcher_position, 'menu' ); ?>><?php esc_html_e( '📋 Navigation Menu', 'multilingual-ai-translator' ); ?></option>
								<option value="shortcode" <?php selected( $switcher_position, 'shortcode' ); ?>><?php esc_html_e( '✏️ Shortcode Only', 'multilingual-ai-translator' ); ?></option>
							</select>
							<p class="mat-field-desc"><?php esc_html_e( 'Floating button appears in the corner of every page.', 'multilingual-ai-translator' ); ?></p>
						</div>

						<div class="mat-form-group">
							<label for="switcher_style"><?php esc_html_e( 'Switcher Style', 'multilingual-ai-translator' ); ?></label>
							<select name="switcher_style" id="switcher_style" class="mat-select">
								<option value="dropdown" <?php selected( $switcher_type, 'dropdown' ); ?>><?php esc_html_e( 'Dropdown Menu', 'multilingual-ai-translator' ); ?></option>
								<option value="inline" <?php selected( $switcher_type, 'inline' ); ?>><?php esc_html_e( 'Inline List', 'multilingual-ai-translator' ); ?></option>
								<option value="flags-only" <?php selected( $switcher_type, 'flags-only' ); ?>><?php esc_html_e( 'Flags Only', 'multilingual-ai-translator' ); ?></option>
							</select>
						</div>

						<div class="mat-form-group">
							<label><?php esc_html_e( 'Display Options', 'multilingual-ai-translator' ); ?></label>
							<div class="mat-checkbox-group">
								<label class="mat-checkbox">
									<input type="checkbox" name="switcher_show_flags" value="1" <?php checked( $switcher_show_flags, 1 ); ?>>
									<span><?php esc_html_e( 'Show Flags', 'multilingual-ai-translator' ); ?></span>
								</label>
								<label class="mat-checkbox">
									<input type="checkbox" name="switcher_show_names" value="1" <?php checked( $switcher_show_names, 1 ); ?>>
									<span><?php esc_html_e( 'Show Language Names', 'multilingual-ai-translator' ); ?></span>
								</label>
							</div>
						</div>

						<div class="mat-form-actions">
							<button type="submit" name="mat_save_switcher" class="mat-btn mat-btn-primary">
								<span class="dashicons dashicons-saved"></span>
								<?php esc_html_e( 'Save Settings', 'multilingual-ai-translator' ); ?>
							</button>
						</div>
					</div>

					<!-- Shortcode Info -->
					<div class="mat-card">
						<h2>
							<span class="dashicons dashicons-editor-code"></span>
							<?php esc_html_e( 'Manual Integration', 'multilingual-ai-translator' ); ?>
						</h2>
						<p><?php esc_html_e( 'Add the language switcher anywhere using these methods:', 'multilingual-ai-translator' ); ?></p>
						
						<div class="mat-code-block">
							<label><?php esc_html_e( 'Shortcode:', 'multilingual-ai-translator' ); ?></label>
							<code class="mat-copyable" onclick="navigator.clipboard.writeText('[mat_language_switcher]')">[mat_language_switcher]</code>
							<small><?php esc_html_e( 'Click to copy', 'multilingual-ai-translator' ); ?></small>
						</div>

						<div class="mat-code-block">
							<label><?php esc_html_e( 'With options:', 'multilingual-ai-translator' ); ?></label>
							<code class="mat-copyable" onclick="navigator.clipboard.writeText('[mat_language_switcher style=\"inline\" show_flags=\"yes\"]')">[mat_language_switcher style="inline" show_flags="yes"]</code>
						</div>

						<div class="mat-code-block">
							<label><?php esc_html_e( 'PHP (in templates):', 'multilingual-ai-translator' ); ?></label>
							<code class="mat-copyable" onclick="navigator.clipboard.writeText('<?php echo do_shortcode(\"[mat_language_switcher]\"); ?>')">&lt;?php echo do_shortcode("[mat_language_switcher]"); ?&gt;</code>
						</div>
					</div>
				</div>

				<!-- Preview Column -->
				<div class="mat-column">
					<div class="mat-card mat-preview-card">
						<h2>
							<span class="dashicons dashicons-visibility"></span>
							<?php esc_html_e( 'Preview', 'multilingual-ai-translator' ); ?>
						</h2>
						
						<?php if ( empty( $active_languages ) ) : ?>
							<div class="mat-preview-empty">
								<span class="dashicons dashicons-warning"></span>
								<h3><?php esc_html_e( 'No Active Languages', 'multilingual-ai-translator' ); ?></h3>
								<p><?php esc_html_e( 'You need at least 2 active languages for the switcher to appear.', 'multilingual-ai-translator' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=multilingual-ai-translator-languages' ) ); ?>" class="mat-btn mat-btn-primary">
									<?php esc_html_e( 'Add Languages', 'multilingual-ai-translator' ); ?>
								</a>
							</div>
						<?php elseif ( count( $active_languages ) < 2 ) : ?>
							<div class="mat-preview-empty">
								<span class="dashicons dashicons-info"></span>
								<h3><?php esc_html_e( 'Need More Languages', 'multilingual-ai-translator' ); ?></h3>
								<p><?php esc_html_e( 'Add at least one more language to enable the switcher.', 'multilingual-ai-translator' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=multilingual-ai-translator-languages' ) ); ?>" class="mat-btn mat-btn-outline">
									<?php esc_html_e( 'Manage Languages', 'multilingual-ai-translator' ); ?>
								</a>
							</div>
						<?php else : ?>
							<div class="mat-preview-container">
								<p class="mat-preview-label"><?php esc_html_e( 'Dropdown Style:', 'multilingual-ai-translator' ); ?></p>
								<div class="mat-preview-box">
									<?php 
									// Safe shortcode render for admin
									try {
										echo do_shortcode( '[mat_language_switcher style="dropdown"]' ); 
									} catch ( Exception $e ) {
										echo '<p class="mat-preview-error">' . esc_html__( 'Preview unavailable', 'multilingual-ai-translator' ) . '</p>';
									} catch ( Error $e ) {
										echo '<p class="mat-preview-error">' . esc_html__( 'Preview unavailable (Fatal)', 'multilingual-ai-translator' ) . '</p>';
									}
									?>
								</div>
								
								<p class="mat-preview-label"><?php esc_html_e( 'Inline Style:', 'multilingual-ai-translator' ); ?></p>
								<div class="mat-preview-box">
									<?php 
									try {
										echo do_shortcode( '[mat_language_switcher style="inline"]' ); 
									} catch ( Exception $e ) {
										echo '<p class="mat-preview-error">' . esc_html__( 'Preview unavailable', 'multilingual-ai-translator' ) . '</p>';
									} catch ( Error $e ) {
										echo '<p class="mat-preview-error">' . esc_html__( 'Preview unavailable (Fatal)', 'multilingual-ai-translator' ) . '</p>';
									}
									?>
								</div>
								
								<p class="mat-preview-label"><?php esc_html_e( 'Flags Only:', 'multilingual-ai-translator' ); ?></p>
								<div class="mat-preview-box">
									<?php 
									try {
										echo do_shortcode( '[mat_language_switcher style="flags-only"]' ); 
									} catch ( Exception $e ) {
										echo '<p class="mat-preview-error">' . esc_html__( 'Preview unavailable', 'multilingual-ai-translator' ) . '</p>';
									} catch ( Error $e ) {
										echo '<p class="mat-preview-error">' . esc_html__( 'Preview unavailable (Fatal)', 'multilingual-ai-translator' ) . '</p>';
									}
									?>
								</div>
							</div>
						<?php endif; ?>
					</div>

					<!-- Active Languages Summary -->
					<div class="mat-card">
						<h2>
							<span class="dashicons dashicons-admin-site-alt3"></span>
							<?php esc_html_e( 'Languages in Switcher', 'multilingual-ai-translator' ); ?>
						</h2>
						
						<?php if ( ! empty( $active_languages ) ) : ?>
							<div class="mat-mini-lang-list">
								<?php foreach ( $active_languages as $lang ) : 
									$flag = isset( $lang['flag'] ) ? $lang['flag'] : '';
									$name = isset( $lang['name'] ) ? $lang['name'] : '';
									$native = isset( $lang['native_name'] ) ? $lang['native_name'] : $name;
									$is_default = isset( $lang['is_default'] ) ? $lang['is_default'] : 0;
								?>
									<div class="mat-mini-lang <?php echo $is_default ? 'mat-default' : ''; ?>">
										<?php if ( $flag && strlen( $flag ) <= 3 && preg_match( '/^[a-zA-Z]+$/', $flag ) ) : ?>
											<img src="https://flagcdn.com/w20/<?php echo esc_attr( strtolower( $flag ) ); ?>.png" alt="" onerror="this.style.display='none'">
										<?php else : ?>
											<span class="mat-mini-emoji"><?php echo esc_html( $flag ); ?></span>
										<?php endif; ?>
										<span><?php echo esc_html( $native ); ?></span>
										<?php if ( $is_default ) : ?>
											<span class="mat-badge mat-badge-small"><?php esc_html_e( 'Default', 'multilingual-ai-translator' ); ?></span>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<p class="mat-text-muted"><?php esc_html_e( 'No languages configured yet.', 'multilingual-ai-translator' ); ?></p>
						<?php endif; ?>
						
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=multilingual-ai-translator-languages' ) ); ?>" class="mat-link">
							<?php esc_html_e( 'Manage languages →', 'multilingual-ai-translator' ); ?>
						</a>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<?php
} catch ( Throwable $e ) {
    echo '<div class="notice notice-error"><p>' . esc_html( 'Critical Error in Switcher Template: ' . $e->getMessage() ) . '</p></div>';
} catch ( Exception $e ) {
    echo '<div class="notice notice-error"><p>' . esc_html( 'Exception in Switcher Template: ' . $e->getMessage() ) . '</p></div>';
}
?>

<style>
.mat-switcher-page { max-width: 1000px; }
.mat-two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.mat-card { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.mat-card h2 { margin: 0 0 16px; font-size: 16px; display: flex; align-items: center; gap: 8px; }
.mat-card h2 .dashicons { color: #667eea; }

.mat-form-group { margin-bottom: 20px; }
.mat-form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; }
.mat-form-group select, .mat-select { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
.mat-form-group select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.15); }
.mat-field-desc { margin: 6px 0 0; font-size: 12px; color: #666; }

.mat-checkbox-group { display: flex; flex-direction: column; gap: 10px; }
.mat-checkbox { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.mat-checkbox input { width: 18px; height: 18px; accent-color: #667eea; }

.mat-form-actions { padding-top: 16px; border-top: 1px solid #eee; }
.mat-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.2s; text-decoration: none; }
.mat-btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
.mat-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
.mat-btn-outline { background: #fff; border: 1px solid #ddd; color: #666; }
.mat-btn-outline:hover { border-color: #667eea; color: #667eea; }

.mat-code-block { margin-bottom: 16px; }
.mat-code-block label { display: block; margin-bottom: 4px; font-size: 12px; color: #666; }
.mat-code-block code { display: block; padding: 10px 12px; background: #f5f5f5; border-radius: 4px; font-size: 13px; cursor: pointer; transition: background 0.2s; }
.mat-code-block code:hover { background: #eee; }
.mat-code-block small { font-size: 11px; color: #999; }

.mat-preview-card { background: #f9f9fb; }
.mat-preview-empty { text-align: center; padding: 30px; }
.mat-preview-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #ccc; margin-bottom: 12px; }
.mat-preview-empty h3 { margin: 0 0 8px; font-size: 16px; color: #666; }
.mat-preview-empty p { margin: 0 0 16px; color: #888; }

.mat-preview-container { padding: 16px; background: #fff; border-radius: 6px; border: 1px solid #e5e5e5; }
.mat-preview-label { margin: 0 0 8px; font-size: 12px; font-weight: 500; color: #666; }
.mat-preview-label:not(:first-child) { margin-top: 20px; }
.mat-preview-box { padding: 16px; background: #f9f9f9; border-radius: 4px; min-height: 40px; }

.mat-mini-lang-list { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.mat-mini-lang { display: flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f5f5f5; border-radius: 20px; font-size: 13px; }
.mat-mini-lang.mat-default { background: #e8f0fe; }
.mat-mini-lang img { width: 16px; height: 12px; border-radius: 2px; }
.mat-mini-emoji { font-size: 14px; }
.mat-badge-small { font-size: 10px; padding: 2px 6px; background: #667eea; color: #fff; border-radius: 10px; }

.mat-link { color: #667eea; text-decoration: none; font-size: 13px; }
.mat-link:hover { text-decoration: underline; }
.mat-text-muted { color: #888; font-size: 13px; }

.mat-admin-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 24px; margin: -20px -20px 24px; }
.mat-admin-header h1 { margin: 0; font-size: 24px; display: flex; align-items: center; gap: 10px; }
.mat-subtitle { margin: 8px 0 0; opacity: 0.9; }

@media (max-width: 900px) {
	.mat-two-columns { grid-template-columns: 1fr; }
}
</style>
