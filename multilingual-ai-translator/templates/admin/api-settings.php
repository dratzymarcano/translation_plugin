<?php
/**
 * API Settings Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$api_key        = get_option( 'mat_openrouter_api_key', '' );
$ai_model       = get_option( 'mat_ai_model', 'anthropic/claude-3-sonnet' );
$auto_translate = get_option( 'mat_auto_translate', '0' );
?>
<div class="mat-admin-wrap">
	<div class="mat-admin-header">
		<div class="mat-header-content">
			<h1>
				<span class="dashicons dashicons-admin-network"></span>
				<?php esc_html_e( 'API Settings', 'multilingual-ai-translator' ); ?>
			</h1>
		</div>
	</div>

	<div class="mat-api-page">
		<form method="post" action="options.php">
			<?php settings_fields( 'mat_api_settings' ); ?>

			<div class="mat-two-columns">
				<div class="mat-column">
					<div class="mat-card">
						<h2><?php esc_html_e( 'OpenRouter Configuration', 'multilingual-ai-translator' ); ?></h2>
						<p class="mat-card-description">
							<?php esc_html_e( 'Connect to OpenRouter to enable AI-powered translations.', 'multilingual-ai-translator' ); ?>
							<a href="https://openrouter.ai/keys" target="_blank" rel="noopener"><?php esc_html_e( 'Get your API key →', 'multilingual-ai-translator' ); ?></a>
						</p>

						<div class="mat-form-group">
							<label for="mat_openrouter_api_key"><?php esc_html_e( 'API Key', 'multilingual-ai-translator' ); ?></label>
							<div class="mat-input-with-icon">
								<input type="password" 
								       name="mat_openrouter_api_key" 
								       id="mat_openrouter_api_key" 
								       value="<?php echo esc_attr( $api_key ); ?>" 
								       class="mat-input mat-input-lg"
								       placeholder="sk-or-..."
								       autocomplete="off">
								<button type="button" class="mat-btn mat-btn-icon" id="mat-toggle-api-key" title="<?php esc_attr_e( 'Toggle visibility', 'multilingual-ai-translator' ); ?>">
									<span class="dashicons dashicons-visibility"></span>
								</button>
							</div>
							<small><?php esc_html_e( 'Your API key is stored securely in the database.', 'multilingual-ai-translator' ); ?></small>
						</div>

						<div class="mat-form-group">
							<label for="mat_ai_model"><?php esc_html_e( 'AI Model', 'multilingual-ai-translator' ); ?></label>
							<select name="mat_ai_model" id="mat_ai_model" class="mat-select">
								<optgroup label="<?php esc_attr_e( 'Recommended', 'multilingual-ai-translator' ); ?>">
									<option value="anthropic/claude-3-sonnet" <?php selected( $ai_model, 'anthropic/claude-3-sonnet' ); ?>>Claude 3 Sonnet</option>
									<option value="anthropic/claude-3-haiku" <?php selected( $ai_model, 'anthropic/claude-3-haiku' ); ?>>Claude 3 Haiku (Fast)</option>
								</optgroup>
								<optgroup label="<?php esc_attr_e( 'OpenAI', 'multilingual-ai-translator' ); ?>">
									<option value="openai/gpt-4-turbo" <?php selected( $ai_model, 'openai/gpt-4-turbo' ); ?>>GPT-4 Turbo</option>
									<option value="openai/gpt-3.5-turbo" <?php selected( $ai_model, 'openai/gpt-3.5-turbo' ); ?>>GPT-3.5 Turbo (Budget)</option>
								</optgroup>
								<optgroup label="<?php esc_attr_e( 'Google', 'multilingual-ai-translator' ); ?>">
									<option value="google/gemini-pro" <?php selected( $ai_model, 'google/gemini-pro' ); ?>>Gemini Pro</option>
								</optgroup>
							</select>
						</div>

						<div class="mat-form-group">
							<label class="mat-checkbox">
								<input type="checkbox" name="mat_auto_translate" value="1" <?php checked( $auto_translate, '1' ); ?>>
								<span><?php esc_html_e( 'Auto-translate new content', 'multilingual-ai-translator' ); ?></span>
							</label>
							<small><?php esc_html_e( 'Automatically queue new posts/pages for translation.', 'multilingual-ai-translator' ); ?></small>
						</div>

						<?php submit_button( __( 'Save Settings', 'multilingual-ai-translator' ), 'mat-btn mat-btn-primary' ); ?>
					</div>
				</div>

				<div class="mat-column">
					<div class="mat-card mat-info-card">
						<h2><?php esc_html_e( 'About OpenRouter', 'multilingual-ai-translator' ); ?></h2>
						<p><?php esc_html_e( 'OpenRouter provides unified access to multiple AI models through a single API. Choose the model that best fits your needs:', 'multilingual-ai-translator' ); ?></p>
						
						<div class="mat-model-info">
							<div class="mat-model-item">
								<strong>Claude 3 Sonnet</strong>
								<p><?php esc_html_e( 'Best balance of speed and quality. Excellent for translations.', 'multilingual-ai-translator' ); ?></p>
							</div>
							<div class="mat-model-item">
								<strong>Claude 3 Haiku</strong>
								<p><?php esc_html_e( 'Fastest option. Great for bulk translations.', 'multilingual-ai-translator' ); ?></p>
							</div>
							<div class="mat-model-item">
								<strong>GPT-4 Turbo</strong>
								<p><?php esc_html_e( 'High quality with large context window.', 'multilingual-ai-translator' ); ?></p>
							</div>
						</div>
					</div>

					<div class="mat-card <?php echo ! empty( $api_key ) ? 'mat-status-success' : 'mat-status-warning'; ?>">
						<h2><?php esc_html_e( 'Connection Status', 'multilingual-ai-translator' ); ?></h2>
						<?php if ( ! empty( $api_key ) ) : ?>
							<div class="mat-status-indicator mat-status-ok">
								<span class="dashicons dashicons-yes-alt"></span>
								<?php esc_html_e( 'API Key Configured', 'multilingual-ai-translator' ); ?>
							</div>
						<?php else : ?>
							<div class="mat-status-indicator mat-status-warn">
								<span class="dashicons dashicons-warning"></span>
								<?php esc_html_e( 'No API Key Set', 'multilingual-ai-translator' ); ?>
							</div>
							<p><?php esc_html_e( 'Add your API key to enable AI translations.', 'multilingual-ai-translator' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
