<?php
/**
 * Admin Dashboard Template
 * Fixed to use correct database column names
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle both object and array formats for languages
$get_lang_prop = function( $lang, $key ) {
	if ( is_array( $lang ) ) {
		return isset( $lang[ $key ] ) ? $lang[ $key ] : '';
	}
	return isset( $lang->$key ) ? $lang->$key : '';
};
?>
<div class="mat-admin-wrap">
	<div class="mat-admin-header">
		<div class="mat-header-content">
			<h1>
				<span class="dashicons dashicons-translation"></span>
				<?php esc_html_e( 'MultiLingual AI Translator Pro', 'multilingual-ai-translator' ); ?>
			</h1>
			<span class="mat-version">v<?php echo esc_html( MAT_VERSION ); ?></span>
		</div>
	</div>

	<div class="mat-dashboard-grid">
		<!-- Quick Stats -->
		<div class="mat-card mat-stats-card">
			<h2><?php esc_html_e( 'Overview', 'multilingual-ai-translator' ); ?></h2>
			<div class="mat-stats-grid">
				<div class="mat-stat-item">
					<span class="mat-stat-number"><?php echo esc_html( $active_count ); ?></span>
					<span class="mat-stat-label"><?php esc_html_e( 'Active Languages', 'multilingual-ai-translator' ); ?></span>
				</div>
				<div class="mat-stat-item">
					<span class="mat-stat-number">
						<?php 
						if ( $default_lang ) {
							$code = $get_lang_prop( $default_lang, 'code' );
							echo esc_html( strtoupper( $code ) );
						} else {
							echo '—';
						}
						?>
					</span>
					<span class="mat-stat-label"><?php esc_html_e( 'Default Language', 'multilingual-ai-translator' ); ?></span>
				</div>
				<div class="mat-stat-item">
					<span class="mat-stat-number <?php echo $api_key_set ? 'mat-status-ok' : 'mat-status-warn'; ?>">
						<?php echo $api_key_set ? '✓' : '✗'; ?>
					</span>
					<span class="mat-stat-label"><?php esc_html_e( 'API Connected', 'multilingual-ai-translator' ); ?></span>
				</div>
				<div class="mat-stat-item">
					<span class="mat-stat-number <?php echo $switcher_active ? 'mat-status-ok' : 'mat-status-warn'; ?>">
						<?php echo $switcher_active ? '✓' : '✗'; ?>
					</span>
					<span class="mat-stat-label"><?php esc_html_e( 'Switcher Active', 'multilingual-ai-translator' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Quick Actions -->
		<div class="mat-card mat-actions-card">
			<h2><?php esc_html_e( 'Quick Actions', 'multilingual-ai-translator' ); ?></h2>
			<div class="mat-actions-list">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=multilingual-ai-translator-languages' ) ); ?>" class="mat-action-btn">
					<span class="dashicons dashicons-admin-site-alt3"></span>
					<?php esc_html_e( 'Manage Languages', 'multilingual-ai-translator' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=multilingual-ai-translator-switcher' ) ); ?>" class="mat-action-btn">
					<span class="dashicons dashicons-menu"></span>
					<?php esc_html_e( 'Configure Switcher', 'multilingual-ai-translator' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=multilingual-ai-translator-api' ) ); ?>" class="mat-action-btn">
					<span class="dashicons dashicons-admin-network"></span>
					<?php esc_html_e( 'API Settings', 'multilingual-ai-translator' ); ?>
				</a>
			</div>
		</div>

		<!-- Active Languages Preview -->
		<div class="mat-card mat-languages-preview">
			<h2><?php esc_html_e( 'Active Languages', 'multilingual-ai-translator' ); ?></h2>
			<div class="mat-lang-flags">
				<?php
				$active_langs = array_filter( $languages, function( $l ) use ( $get_lang_prop ) { 
					return $get_lang_prop( $l, 'is_active' ); 
				} );
				
				if ( empty( $active_langs ) ) :
				?>
					<p class="mat-text-muted"><?php esc_html_e( 'No languages configured yet.', 'multilingual-ai-translator' ); ?></p>
				<?php else : ?>
					<?php foreach ( $active_langs as $lang ) :
						$code = $get_lang_prop( $lang, 'code' );
						$name = $get_lang_prop( $lang, 'name' );
						$flag = $get_lang_prop( $lang, 'flag' );
						$is_default = $get_lang_prop( $lang, 'is_default' );
					?>
						<div class="mat-lang-flag-item" title="<?php echo esc_attr( $name ); ?>">
							<?php if ( $flag && strlen( $flag ) <= 5 ) : ?>
								<img src="https://flagcdn.com/w40/<?php echo esc_attr( strtolower( $flag ) ); ?>.png" 
								     alt="<?php echo esc_attr( $name ); ?>"
								     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
								<span class="mat-flag-fallback" style="display:none;"><?php echo esc_html( strtoupper( $code ) ); ?></span>
							<?php else : ?>
								<span class="mat-flag-emoji"><?php echo esc_html( $flag ); ?></span>
							<?php endif; ?>
							<?php if ( $is_default ) : ?>
								<span class="mat-default-badge">★</span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=multilingual-ai-translator-languages' ) ); ?>" class="mat-link">
				<?php esc_html_e( 'Manage all languages →', 'multilingual-ai-translator' ); ?>
			</a>
		</div>

		<!-- Getting Started -->
		<div class="mat-card mat-getting-started">
			<h2><?php esc_html_e( 'Getting Started', 'multilingual-ai-translator' ); ?></h2>
			<ol class="mat-steps">
				<li class="<?php echo $api_key_set ? 'mat-step-done' : ''; ?>">
					<strong><?php esc_html_e( 'Configure API', 'multilingual-ai-translator' ); ?></strong>
					<p><?php esc_html_e( 'Add your OpenRouter API key to enable AI translations.', 'multilingual-ai-translator' ); ?></p>
				</li>
				<li class="<?php echo $active_count > 1 ? 'mat-step-done' : ''; ?>">
					<strong><?php esc_html_e( 'Enable Languages', 'multilingual-ai-translator' ); ?></strong>
					<p><?php esc_html_e( 'Choose which languages you want to support.', 'multilingual-ai-translator' ); ?></p>
				</li>
				<li class="<?php echo $switcher_active ? 'mat-step-done' : ''; ?>">
					<strong><?php esc_html_e( 'Add Switcher', 'multilingual-ai-translator' ); ?></strong>
					<p><?php esc_html_e( 'Enable the floating language switcher or add shortcode.', 'multilingual-ai-translator' ); ?></p>
				</li>
				<li>
					<strong><?php esc_html_e( 'Translate Content', 'multilingual-ai-translator' ); ?></strong>
					<p><?php esc_html_e( 'Edit posts/pages and use the Translations metabox to add translations.', 'multilingual-ai-translator' ); ?></p>
				</li>
			</ol>
		</div>

		<!-- Shortcode Reference -->
		<div class="mat-card">
			<h2><?php esc_html_e( 'Shortcode Reference', 'multilingual-ai-translator' ); ?></h2>
			<div class="mat-code-list">
				<div class="mat-code-item">
					<code>[mat_language_switcher]</code>
					<span><?php esc_html_e( 'Display language switcher', 'multilingual-ai-translator' ); ?></span>
				</div>
				<div class="mat-code-item">
					<code>[mat_language_switcher style="inline"]</code>
					<span><?php esc_html_e( 'Inline style switcher', 'multilingual-ai-translator' ); ?></span>
				</div>
				<div class="mat-code-item">
					<code>[mat_language_switcher style="flags-only"]</code>
					<span><?php esc_html_e( 'Flags only switcher', 'multilingual-ai-translator' ); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.mat-admin-wrap { max-width: 1200px; }
.mat-admin-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 24px; margin: -20px -20px 24px; display: flex; align-items: center; justify-content: space-between; }
.mat-admin-header h1 { margin: 0; font-size: 24px; display: flex; align-items: center; gap: 10px; }
.mat-version { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 12px; }

.mat-dashboard-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
.mat-card { background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.mat-card h2 { margin: 0 0 16px; font-size: 16px; color: #333; }

.mat-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.mat-stat-item { text-align: center; padding: 16px; background: #f9f9fb; border-radius: 8px; }
.mat-stat-number { display: block; font-size: 28px; font-weight: 600; color: #667eea; }
.mat-stat-label { font-size: 12px; color: #666; margin-top: 4px; display: block; }
.mat-status-ok { color: #10b981; }
.mat-status-warn { color: #f59e0b; }

.mat-actions-list { display: flex; flex-direction: column; gap: 8px; }
.mat-action-btn { display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #f9f9fb; border-radius: 6px; text-decoration: none; color: #333; transition: all 0.2s; }
.mat-action-btn:hover { background: #667eea; color: #fff; }
.mat-action-btn .dashicons { font-size: 18px; width: 18px; height: 18px; }

.mat-lang-flags { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.mat-lang-flag-item { position: relative; width: 40px; height: 30px; border-radius: 4px; overflow: hidden; background: #f0f0f0; display: flex; align-items: center; justify-content: center; }
.mat-lang-flag-item img { width: 100%; height: 100%; object-fit: cover; }
.mat-flag-fallback { font-size: 10px; font-weight: 600; color: #666; }
.mat-flag-emoji { font-size: 20px; }
.mat-default-badge { position: absolute; top: -4px; right: -4px; background: #ffc107; color: #000; font-size: 10px; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; }

.mat-link { color: #667eea; text-decoration: none; font-size: 13px; }
.mat-link:hover { text-decoration: underline; }
.mat-text-muted { color: #888; font-size: 13px; }

.mat-steps { margin: 0; padding-left: 20px; }
.mat-steps li { margin-bottom: 16px; padding-left: 8px; }
.mat-steps li strong { display: block; margin-bottom: 4px; }
.mat-steps li p { margin: 0; color: #666; font-size: 13px; }
.mat-step-done { color: #10b981; }
.mat-step-done::marker { content: "✓ "; }

.mat-code-list { display: flex; flex-direction: column; gap: 8px; }
.mat-code-item { display: flex; align-items: center; gap: 12px; }
.mat-code-item code { background: #f5f5f5; padding: 6px 10px; border-radius: 4px; font-size: 12px; }
.mat-code-item span { color: #666; font-size: 13px; }

@media (max-width: 900px) {
	.mat-dashboard-grid { grid-template-columns: 1fr; }
	.mat-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
