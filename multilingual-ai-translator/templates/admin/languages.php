<?php
/**
 * Languages Management Template
 * With dropdown language selector that auto-populates fields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get existing language codes from database
$existing_codes = array();
foreach ( $languages as $lang ) {
	$existing_codes[] = isset( $lang['code'] ) ? $lang['code'] : ( isset( $lang->code ) ? $lang->code : '' );
}

// All available languages with full data
$all_languages = array(
	'en' => array( 'name' => 'English', 'native' => 'English', 'flag' => 'gb' ),
	'de' => array( 'name' => 'German', 'native' => 'Deutsch', 'flag' => 'de' ),
	'fr' => array( 'name' => 'French', 'native' => 'Français', 'flag' => 'fr' ),
	'es' => array( 'name' => 'Spanish', 'native' => 'Español', 'flag' => 'es' ),
	'it' => array( 'name' => 'Italian', 'native' => 'Italiano', 'flag' => 'it' ),
	'pt' => array( 'name' => 'Portuguese', 'native' => 'Português', 'flag' => 'pt' ),
	'nl' => array( 'name' => 'Dutch', 'native' => 'Nederlands', 'flag' => 'nl' ),
	'pl' => array( 'name' => 'Polish', 'native' => 'Polski', 'flag' => 'pl' ),
	'ro' => array( 'name' => 'Romanian', 'native' => 'Română', 'flag' => 'ro' ),
	'el' => array( 'name' => 'Greek', 'native' => 'Ελληνικά', 'flag' => 'gr' ),
	'sv' => array( 'name' => 'Swedish', 'native' => 'Svenska', 'flag' => 'se' ),
	'hu' => array( 'name' => 'Hungarian', 'native' => 'Magyar', 'flag' => 'hu' ),
	'cs' => array( 'name' => 'Czech', 'native' => 'Čeština', 'flag' => 'cz' ),
	'da' => array( 'name' => 'Danish', 'native' => 'Dansk', 'flag' => 'dk' ),
	'fi' => array( 'name' => 'Finnish', 'native' => 'Suomi', 'flag' => 'fi' ),
	'sk' => array( 'name' => 'Slovak', 'native' => 'Slovenčina', 'flag' => 'sk' ),
	'bg' => array( 'name' => 'Bulgarian', 'native' => 'Български', 'flag' => 'bg' ),
	'hr' => array( 'name' => 'Croatian', 'native' => 'Hrvatski', 'flag' => 'hr' ),
	'lt' => array( 'name' => 'Lithuanian', 'native' => 'Lietuvių', 'flag' => 'lt' ),
	'lv' => array( 'name' => 'Latvian', 'native' => 'Latviešu', 'flag' => 'lv' ),
	'sl' => array( 'name' => 'Slovenian', 'native' => 'Slovenščina', 'flag' => 'si' ),
	'et' => array( 'name' => 'Estonian', 'native' => 'Eesti', 'flag' => 'ee' ),
	'mt' => array( 'name' => 'Maltese', 'native' => 'Malti', 'flag' => 'mt' ),
	'ga' => array( 'name' => 'Irish', 'native' => 'Gaeilge', 'flag' => 'ie' ),
	'ru' => array( 'name' => 'Russian', 'native' => 'Русский', 'flag' => 'ru' ),
	'uk' => array( 'name' => 'Ukrainian', 'native' => 'Українська', 'flag' => 'ua' ),
	'ja' => array( 'name' => 'Japanese', 'native' => '日本語', 'flag' => 'jp' ),
	'ko' => array( 'name' => 'Korean', 'native' => '한국어', 'flag' => 'kr' ),
	'zh' => array( 'name' => 'Chinese', 'native' => '中文', 'flag' => 'cn' ),
	'ar' => array( 'name' => 'Arabic', 'native' => 'العربية', 'flag' => 'sa' ),
	'hi' => array( 'name' => 'Hindi', 'native' => 'हिन्दी', 'flag' => 'in' ),
	'tr' => array( 'name' => 'Turkish', 'native' => 'Türkçe', 'flag' => 'tr' ),
	'vi' => array( 'name' => 'Vietnamese', 'native' => 'Tiếng Việt', 'flag' => 'vn' ),
	'th' => array( 'name' => 'Thai', 'native' => 'ไทย', 'flag' => 'th' ),
	'no' => array( 'name' => 'Norwegian', 'native' => 'Norsk', 'flag' => 'no' ),
	'he' => array( 'name' => 'Hebrew', 'native' => 'עברית', 'flag' => 'il' ),
	'id' => array( 'name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => 'id' ),
	'ms' => array( 'name' => 'Malay', 'native' => 'Bahasa Melayu', 'flag' => 'my' ),
);

// Count active languages
$active_count = 0;
foreach ( $languages as $lang ) {
	$is_active = isset( $lang['is_active'] ) ? $lang['is_active'] : ( isset( $lang->is_active ) ? $lang->is_active : 0 );
	if ( $is_active ) {
		$active_count++;
	}
}
?>
<div class="mat-admin-wrap">
	<div class="mat-admin-header">
		<div class="mat-header-content">
			<h1>
				<span class="dashicons dashicons-admin-site-alt3"></span>
				<?php esc_html_e( 'Languages', 'multilingual-ai-translator' ); ?>
			</h1>
			<p class="mat-subtitle"><?php printf( esc_html__( '%d active languages', 'multilingual-ai-translator' ), $active_count ); ?></p>
		</div>
	</div>

	<div class="mat-languages-page">
		<!-- Add Language Form with Dropdown -->
		<div class="mat-card mat-add-language">
			<h2>
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'Add New Language', 'multilingual-ai-translator' ); ?>
			</h2>
			<p class="mat-card-description"><?php esc_html_e( 'Select a language from the dropdown to auto-fill all fields.', 'multilingual-ai-translator' ); ?></p>
			
			<form id="mat-add-language-form">
				<div class="mat-form-grid">
					<div class="mat-form-group mat-form-full">
						<label for="lang-select"><?php esc_html_e( 'Select Language', 'multilingual-ai-translator' ); ?></label>
						<select id="lang-select" class="mat-select-large">
							<option value=""><?php esc_html_e( '— Choose a language —', 'multilingual-ai-translator' ); ?></option>
							<?php foreach ( $all_languages as $code => $data ) : ?>
								<?php if ( ! in_array( $code, $existing_codes ) ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" 
									        data-name="<?php echo esc_attr( $data['name'] ); ?>"
									        data-native="<?php echo esc_attr( $data['native'] ); ?>"
									        data-flag="<?php echo esc_attr( $data['flag'] ); ?>">
										<?php echo esc_html( $data['name'] . ' (' . $data['native'] . ')' ); ?>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div class="mat-form-group">
						<label for="lang-code"><?php esc_html_e( 'Code', 'multilingual-ai-translator' ); ?></label>
						<input type="text" id="lang-code" name="code" placeholder="e.g., en" maxlength="5" required readonly>
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
						<div class="mat-input-with-preview">
							<input type="text" id="lang-flag" name="flag" placeholder="e.g., gb" maxlength="5">
							<span class="mat-flag-preview"></span>
						</div>
						<small><?php esc_html_e( 'ISO country code for flag', 'multilingual-ai-translator' ); ?></small>
					</div>
				</div>
				
				<div class="mat-form-actions">
					<button type="submit" class="mat-btn mat-btn-primary mat-btn-lg" id="mat-add-lang-btn" disabled>
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'Add Language', 'multilingual-ai-translator' ); ?>
					</button>
				</div>
			</form>
		</div>

		<!-- Languages List -->
		<div class="mat-card">
			<h2>
				<span class="dashicons dashicons-list-view"></span>
				<?php esc_html_e( 'Configured Languages', 'multilingual-ai-translator' ); ?>
			</h2>
			<p class="mat-card-description"><?php esc_html_e( 'Drag to reorder. Toggle to enable/disable. Set one as default.', 'multilingual-ai-translator' ); ?></p>
			
			<?php if ( empty( $languages ) ) : ?>
				<div class="mat-empty-state">
					<span class="dashicons dashicons-translation"></span>
					<h3><?php esc_html_e( 'No languages configured', 'multilingual-ai-translator' ); ?></h3>
					<p><?php esc_html_e( 'Add languages using the dropdown above to get started.', 'multilingual-ai-translator' ); ?></p>
				</div>
			<?php else : ?>
				<div class="mat-languages-list" id="mat-languages-sortable">
					<?php foreach ( $languages as $lang ) : 
						// Handle both array and object formats
						$id = isset( $lang['id'] ) ? $lang['id'] : ( isset( $lang->id ) ? $lang->id : 0 );
						$code = isset( $lang['code'] ) ? $lang['code'] : ( isset( $lang->code ) ? $lang->code : '' );
						$name = isset( $lang['name'] ) ? $lang['name'] : ( isset( $lang->name ) ? $lang->name : '' );
						$native = isset( $lang['native_name'] ) ? $lang['native_name'] : ( isset( $lang->native_name ) ? $lang->native_name : '' );
						$flag = isset( $lang['flag'] ) ? $lang['flag'] : ( isset( $lang->flag ) ? $lang->flag : '' );
						$is_active = isset( $lang['is_active'] ) ? $lang['is_active'] : ( isset( $lang->is_active ) ? $lang->is_active : 0 );
						$is_default = isset( $lang['is_default'] ) ? $lang['is_default'] : ( isset( $lang->is_default ) ? $lang->is_default : 0 );
					?>
						<div class="mat-language-item <?php echo $is_active ? 'mat-active' : 'mat-inactive'; ?> <?php echo $is_default ? 'mat-default' : ''; ?>" data-id="<?php echo esc_attr( $id ); ?>">
							<div class="mat-lang-drag">
								<span class="dashicons dashicons-menu"></span>
							</div>
							<div class="mat-lang-flag">
								<?php if ( $flag ) : ?>
									<img src="https://flagcdn.com/w40/<?php echo esc_attr( strtolower( $flag ) ); ?>.png" 
									     alt="<?php echo esc_attr( $name ); ?>"
									     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
								<?php endif; ?>
								<span class="mat-flag-fallback" <?php echo $flag ? 'style="display:none;"' : ''; ?>><?php echo esc_html( strtoupper( $code ) ); ?></span>
							</div>
							<div class="mat-lang-info">
								<strong><?php echo esc_html( $name ); ?></strong>
								<?php if ( $native && $native !== $name ) : ?>
									<span class="mat-lang-native"><?php echo esc_html( $native ); ?></span>
								<?php endif; ?>
								<code><?php echo esc_html( $code ); ?></code>
							</div>
							<div class="mat-lang-badges">
								<?php if ( $is_default ) : ?>
									<span class="mat-badge mat-badge-primary"><?php esc_html_e( 'Default', 'multilingual-ai-translator' ); ?></span>
								<?php endif; ?>
								<?php if ( $is_active ) : ?>
									<span class="mat-badge mat-badge-success"><?php esc_html_e( 'Active', 'multilingual-ai-translator' ); ?></span>
								<?php else : ?>
									<span class="mat-badge mat-badge-muted"><?php esc_html_e( 'Inactive', 'multilingual-ai-translator' ); ?></span>
								<?php endif; ?>
							</div>
							<div class="mat-lang-actions">
								<label class="mat-toggle" title="<?php esc_attr_e( 'Enable/Disable', 'multilingual-ai-translator' ); ?>">
									<input type="checkbox" class="mat-toggle-active" <?php checked( $is_active, 1 ); ?> data-id="<?php echo esc_attr( $id ); ?>">
									<span class="mat-toggle-slider"></span>
								</label>
								<?php if ( ! $is_default ) : ?>
									<button class="mat-btn mat-btn-sm mat-btn-outline mat-set-default" data-id="<?php echo esc_attr( $id ); ?>" title="<?php esc_attr_e( 'Set as Default', 'multilingual-ai-translator' ); ?>">
										<span class="dashicons dashicons-star-empty"></span>
									</button>
									<button class="mat-btn mat-btn-sm mat-btn-danger mat-delete-lang" data-id="<?php echo esc_attr( $id ); ?>" title="<?php esc_attr_e( 'Delete', 'multilingual-ai-translator' ); ?>">
										<span class="dashicons dashicons-trash"></span>
									</button>
								<?php else : ?>
									<span class="mat-default-star" title="<?php esc_attr_e( 'Default Language', 'multilingual-ai-translator' ); ?>">
										<span class="dashicons dashicons-star-filled"></span>
									</span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		
		<!-- Help Card -->
		<div class="mat-card mat-card-info">
			<h3><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'How Languages Work', 'multilingual-ai-translator' ); ?></h3>
			<ul>
				<li><strong><?php esc_html_e( 'Default Language:', 'multilingual-ai-translator' ); ?></strong> <?php esc_html_e( 'Your site\'s primary language. Content without translations shows in this language.', 'multilingual-ai-translator' ); ?></li>
				<li><strong><?php esc_html_e( 'Active Languages:', 'multilingual-ai-translator' ); ?></strong> <?php esc_html_e( 'Languages that appear in the frontend switcher and translation editor.', 'multilingual-ai-translator' ); ?></li>
				<li><strong><?php esc_html_e( 'SEO URLs:', 'multilingual-ai-translator' ); ?></strong> <?php esc_html_e( 'Each language gets clean URLs like /de/page-name/, /fr/page-name/', 'multilingual-ai-translator' ); ?></li>
			</ul>
		</div>
	</div>
</div>

<style>
.mat-languages-page { max-width: 900px; }
.mat-card { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.mat-card h2 { margin: 0 0 8px; font-size: 18px; display: flex; align-items: center; gap: 8px; }
.mat-card h2 .dashicons { color: #667eea; }
.mat-card-description { margin: 0 0 20px; color: #666; }
.mat-card-info { background: #f0f4ff; border: 1px solid #c7d2fe; }
.mat-card-info h3 { margin: 0 0 12px; font-size: 14px; display: flex; align-items: center; gap: 6px; }
.mat-card-info ul { margin: 0; padding-left: 20px; }
.mat-card-info li { margin-bottom: 8px; font-size: 13px; color: #555; }

.mat-form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px; }
.mat-form-full { grid-column: 1 / -1; }
.mat-form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; }
.mat-form-group input, .mat-form-group select { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
.mat-form-group input:focus, .mat-form-group select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.15); }
.mat-form-group input[readonly] { background: #f5f5f5; }
.mat-form-group small { display: block; margin-top: 4px; color: #888; font-size: 11px; }
.mat-select-large { padding: 12px !important; font-size: 15px !important; }

.mat-input-with-preview { display: flex; gap: 8px; align-items: center; }
.mat-input-with-preview input { flex: 1; }
.mat-flag-preview { width: 32px; height: 24px; border-radius: 3px; overflow: hidden; background: #f0f0f0; }
.mat-flag-preview img { width: 100%; height: 100%; object-fit: cover; }

.mat-form-actions { padding-top: 12px; border-top: 1px solid #eee; }
.mat-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.2s; }
.mat-btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
.mat-btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
.mat-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.mat-btn-lg { padding: 12px 24px; font-size: 14px; }
.mat-btn-sm { padding: 6px 10px; font-size: 12px; }
.mat-btn-outline { background: #fff; border: 1px solid #ddd; color: #666; }
.mat-btn-outline:hover { border-color: #667eea; color: #667eea; }
.mat-btn-danger { background: #fff; border: 1px solid #ddd; color: #666; }
.mat-btn-danger:hover { background: #dc3545; border-color: #dc3545; color: #fff; }

.mat-empty-state { text-align: center; padding: 40px 20px; color: #888; }
.mat-empty-state .dashicons { font-size: 48px; width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.3; }
.mat-empty-state h3 { margin: 0 0 8px; color: #666; }
.mat-empty-state p { margin: 0; }

.mat-languages-list { border: 1px solid #e5e5e5; border-radius: 6px; overflow: hidden; }
.mat-language-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #fff; border-bottom: 1px solid #e5e5e5; transition: background 0.2s; }
.mat-language-item:last-child { border-bottom: none; }
.mat-language-item:hover { background: #f9f9f9; }
.mat-language-item.mat-inactive { opacity: 0.6; }
.mat-language-item.mat-default { background: #f8f9ff; }

.mat-lang-drag { cursor: grab; color: #ccc; }
.mat-lang-drag:hover { color: #999; }
.mat-lang-flag { width: 32px; height: 24px; border-radius: 3px; overflow: hidden; background: #f0f0f0; display: flex; align-items: center; justify-content: center; }
.mat-lang-flag img { width: 100%; height: 100%; object-fit: cover; }
.mat-flag-fallback { font-size: 10px; font-weight: 600; color: #666; }

.mat-lang-info { flex: 1; }
.mat-lang-info strong { display: block; font-size: 14px; }
.mat-lang-native { color: #888; font-size: 12px; margin-right: 8px; }
.mat-lang-info code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 11px; color: #666; }

.mat-lang-badges { display: flex; gap: 6px; }
.mat-badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; }
.mat-badge-primary { background: #667eea; color: #fff; }
.mat-badge-success { background: #d4edda; color: #155724; }
.mat-badge-muted { background: #e9ecef; color: #6c757d; }

.mat-lang-actions { display: flex; align-items: center; gap: 8px; }
.mat-toggle { position: relative; width: 44px; height: 24px; }
.mat-toggle input { opacity: 0; width: 0; height: 0; }
.mat-toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; border-radius: 24px; transition: 0.3s; }
.mat-toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; }
.mat-toggle input:checked + .mat-toggle-slider { background: #667eea; }
.mat-toggle input:checked + .mat-toggle-slider:before { transform: translateX(20px); }

.mat-default-star { color: #ffc107; }
.mat-default-star .dashicons { font-size: 18px; }

.mat-admin-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 24px; margin: -20px -20px 24px; }
.mat-admin-header h1 { margin: 0; font-size: 24px; display: flex; align-items: center; gap: 10px; }
.mat-subtitle { margin: 8px 0 0; opacity: 0.9; }

@keyframes mat-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.mat-spin { animation: mat-spin 1s linear infinite; }

@media (max-width: 782px) {
	.mat-form-grid { grid-template-columns: 1fr; }
	.mat-language-item { flex-wrap: wrap; }
	.mat-lang-actions { width: 100%; justify-content: flex-end; margin-top: 8px; }
}
</style>

<script>
jQuery(document).ready(function($) {
	// Language select auto-populate
	$('#lang-select').on('change', function() {
		var $selected = $(this).find(':selected');
		var code = $(this).val();
		
		if (code) {
			$('#lang-code').val(code);
			$('#lang-name').val($selected.data('name'));
			$('#lang-native').val($selected.data('native'));
			$('#lang-flag').val($selected.data('flag'));
			$('#mat-add-lang-btn').prop('disabled', false);
			
			// Update flag preview
			var flag = $selected.data('flag');
			if (flag) {
				$('.mat-flag-preview').html('<img src="https://flagcdn.com/w40/' + flag.toLowerCase() + '.png" alt="">');
			}
		} else {
			$('#lang-code, #lang-name, #lang-native, #lang-flag').val('');
			$('#mat-add-lang-btn').prop('disabled', true);
			$('.mat-flag-preview').empty();
		}
	});
	
	// Flag code change - update preview
	$('#lang-flag').on('input', function() {
		var flag = $(this).val();
		if (flag && flag.length >= 2) {
			$('.mat-flag-preview').html('<img src="https://flagcdn.com/w40/' + flag.toLowerCase() + '.png" alt="" onerror="this.style.display=\'none\'">');
		} else {
			$('.mat-flag-preview').empty();
		}
	});
	
	// Add language form
	$('#mat-add-language-form').on('submit', function(e) {
		e.preventDefault();
		
		var $btn = $('#mat-add-lang-btn');
		var originalText = $btn.html();
		$btn.prop('disabled', true).html('<span class="dashicons dashicons-update mat-spin"></span> Adding...');
		
		$.post(matAdmin.ajaxUrl, {
			action: 'mat_add_language',
			nonce: matAdmin.nonce,
			code: $('#lang-code').val(),
			name: $('#lang-name').val(),
			native_name: $('#lang-native').val(),
			flag: $('#lang-flag').val()
		}, function(response) {
			if (response.success) {
				location.reload();
			} else {
				alert(response.data || 'Failed to add language');
				$btn.prop('disabled', false).html(originalText);
			}
		}).fail(function() {
			alert('Request failed. Please try again.');
			$btn.prop('disabled', false).html(originalText);
		});
	});
	
	// Toggle language active status
	$('.mat-toggle-active').on('change', function() {
		var id = $(this).data('id');
		var $item = $(this).closest('.mat-language-item');
		
		$.post(matAdmin.ajaxUrl, {
			action: 'mat_toggle_language',
			nonce: matAdmin.nonce,
			id: id
		}, function(response) {
			if (response.success) {
				$item.toggleClass('mat-active mat-inactive');
				// Update badge
				var $badge = $item.find('.mat-badge-success, .mat-badge-muted');
				if ($item.hasClass('mat-active')) {
					$badge.removeClass('mat-badge-muted').addClass('mat-badge-success').text('Active');
				} else {
					$badge.removeClass('mat-badge-success').addClass('mat-badge-muted').text('Inactive');
				}
			}
		});
	});
	
	// Set default language
	$('.mat-set-default').on('click', function() {
		var id = $(this).data('id');
		
		$.post(matAdmin.ajaxUrl, {
			action: 'mat_set_default_language',
			nonce: matAdmin.nonce,
			id: id
		}, function(response) {
			if (response.success) {
				location.reload();
			}
		});
	});
	
	// Delete language
	$('.mat-delete-lang').on('click', function() {
		if (!confirm('Are you sure you want to delete this language? This will NOT delete existing translations.')) {
			return;
		}
		
		var id = $(this).data('id');
		var $item = $(this).closest('.mat-language-item');
		
		$.post(matAdmin.ajaxUrl, {
			action: 'mat_delete_language',
			nonce: matAdmin.nonce,
			id: id
		}, function(response) {
			if (response.success) {
				$item.fadeOut(300, function() { $(this).remove(); });
			} else {
				alert(response.data || 'Failed to delete language');
			}
		});
	});
	
	// Sortable
	if ($('#mat-languages-sortable').length) {
		$('#mat-languages-sortable').sortable({
			handle: '.mat-lang-drag',
			update: function(event, ui) {
				var order = $(this).sortable('toArray', { attribute: 'data-id' });
				
				$.post(matAdmin.ajaxUrl, {
					action: 'mat_reorder_languages',
					nonce: matAdmin.nonce,
					order: order
				});
			}
		});
	}
});
</script>
