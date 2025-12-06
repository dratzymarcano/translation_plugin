<?php
/**
 * Languages Management Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mat-admin-wrap">
	<div class="mat-admin-header">
		<div class="mat-header-content">
			<h1>
				<span class="dashicons dashicons-admin-site-alt3"></span>
				<?php esc_html_e( 'Languages', 'multilingual-ai-translator' ); ?>
			</h1>
		</div>
	</div>

	<div class="mat-languages-page">
		<!-- Add Language Form -->
		<div class="mat-card mat-add-language">
			<h2><?php esc_html_e( 'Add New Language', 'multilingual-ai-translator' ); ?></h2>
			<form id="mat-add-language-form" class="mat-form-row">
				<div class="mat-form-group">
					<label for="lang-code"><?php esc_html_e( 'Code', 'multilingual-ai-translator' ); ?></label>
					<input type="text" id="lang-code" name="code" placeholder="e.g., en" maxlength="5" required>
				</div>
				<div class="mat-form-group">
					<label for="lang-name"><?php esc_html_e( 'Name', 'multilingual-ai-translator' ); ?></label>
					<input type="text" id="lang-name" name="name" placeholder="e.g., English" required>
				</div>
				<div class="mat-form-group">
					<label for="lang-native"><?php esc_html_e( 'Native Name', 'multilingual-ai-translator' ); ?></label>
					<input type="text" id="lang-native" name="native_name" placeholder="e.g., English">
				</div>
				<div class="mat-form-group">
					<label for="lang-flag"><?php esc_html_e( 'Flag Code', 'multilingual-ai-translator' ); ?></label>
					<input type="text" id="lang-flag" name="flag_code" placeholder="e.g., gb" maxlength="5">
					<small><?php esc_html_e( 'ISO 3166-1 alpha-2 country code', 'multilingual-ai-translator' ); ?></small>
				</div>
				<div class="mat-form-group mat-form-submit">
					<button type="submit" class="mat-btn mat-btn-primary">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'Add Language', 'multilingual-ai-translator' ); ?>
					</button>
				</div>
			</form>
		</div>

		<!-- Languages List -->
		<div class="mat-card">
			<h2><?php esc_html_e( 'EU Languages', 'multilingual-ai-translator' ); ?></h2>
			<p class="mat-card-description"><?php esc_html_e( 'Drag and drop to reorder. Toggle to enable/disable.', 'multilingual-ai-translator' ); ?></p>
			
			<div class="mat-languages-list" id="mat-languages-sortable">
				<?php foreach ( $languages as $lang ) : ?>
					<div class="mat-language-item <?php echo $lang->is_active ? 'mat-active' : 'mat-inactive'; ?> <?php echo $lang->is_default ? 'mat-default' : ''; ?>" data-id="<?php echo esc_attr( $lang->id ); ?>">
						<div class="mat-lang-drag">
							<span class="dashicons dashicons-menu"></span>
						</div>
						<div class="mat-lang-flag">
							<img src="https://flagcdn.com/w40/<?php echo esc_attr( $lang->flag_code ); ?>.png" 
							     alt="<?php echo esc_attr( $lang->language_name ); ?>"
							     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
							<span class="mat-flag-fallback" style="display:none;"><?php echo esc_html( strtoupper( $lang->language_code ) ); ?></span>
						</div>
						<div class="mat-lang-info">
							<strong><?php echo esc_html( $lang->language_name ); ?></strong>
							<span class="mat-lang-native"><?php echo esc_html( $lang->native_name ); ?></span>
							<code><?php echo esc_html( $lang->language_code ); ?></code>
						</div>
						<div class="mat-lang-badges">
							<?php if ( $lang->is_default ) : ?>
								<span class="mat-badge mat-badge-default"><?php esc_html_e( 'Default', 'multilingual-ai-translator' ); ?></span>
							<?php endif; ?>
						</div>
						<div class="mat-lang-actions">
							<label class="mat-toggle">
								<input type="checkbox" class="mat-toggle-active" <?php checked( $lang->is_active, 1 ); ?> data-id="<?php echo esc_attr( $lang->id ); ?>">
								<span class="mat-toggle-slider"></span>
							</label>
							<?php if ( ! $lang->is_default ) : ?>
								<button class="mat-btn mat-btn-sm mat-btn-outline mat-set-default" data-id="<?php echo esc_attr( $lang->id ); ?>" title="<?php esc_attr_e( 'Set as Default', 'multilingual-ai-translator' ); ?>">
									<span class="dashicons dashicons-star-empty"></span>
								</button>
								<button class="mat-btn mat-btn-sm mat-btn-danger mat-delete-lang" data-id="<?php echo esc_attr( $lang->id ); ?>" title="<?php esc_attr_e( 'Delete', 'multilingual-ai-translator' ); ?>">
									<span class="dashicons dashicons-trash"></span>
								</button>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>
