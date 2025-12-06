<?php
/**
 * Admin Dashboard Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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
					<span class="mat-stat-number"><?php echo $default_lang ? esc_html( strtoupper( $default_lang->language_code ) ) : '—'; ?></span>
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
				$active_langs = array_filter( $languages, function( $l ) { return $l->is_active; } );
				foreach ( $active_langs as $lang ) :
					$flag_url = MAT_PLUGIN_URL . 'assets/flags/' . esc_attr( $lang->flag_code ) . '.svg';
				?>
					<div class="mat-lang-flag-item" title="<?php echo esc_attr( $lang->language_name ); ?>">
						<img src="https://flagcdn.com/w40/<?php echo esc_attr( $lang->flag_code ); ?>.png" 
						     alt="<?php echo esc_attr( $lang->language_name ); ?>"
						     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
						<span class="mat-flag-fallback" style="display:none;"><?php echo esc_html( strtoupper( $lang->language_code ) ); ?></span>
						<?php if ( $lang->is_default ) : ?>
							<span class="mat-default-badge">★</span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
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
					<p><?php esc_html_e( 'Choose which EU languages you want to support.', 'multilingual-ai-translator' ); ?></p>
				</li>
				<li class="<?php echo $switcher_active ? 'mat-step-done' : ''; ?>">
					<strong><?php esc_html_e( 'Add Switcher', 'multilingual-ai-translator' ); ?></strong>
					<p><?php esc_html_e( 'Add the language switcher to your menu or widget area.', 'multilingual-ai-translator' ); ?></p>
				</li>
				<li>
					<strong><?php esc_html_e( 'Set SEO Keywords', 'multilingual-ai-translator' ); ?></strong>
					<p><?php esc_html_e( 'Add per-page keywords for each language in your posts/pages.', 'multilingual-ai-translator' ); ?></p>
				</li>
			</ol>
		</div>
	</div>
</div>
