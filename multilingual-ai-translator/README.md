# MultiLingual AI Translator Pro

**Version:** 2.08  
**Requires WordPress:** 6.0+  
**Requires PHP:** 7.4+  
**License:** GPL-2.0+

Professional AI-powered WordPress translation plugin using OpenRouter API. Combines the best features of TranslatePress and Polylang:

- **TranslatePress-style:** Automatic AI translations via OpenRouter API
- **Polylang-style:** Editable translations with per-language SEO metadata

## 🌟 Features

### AI Translation Engine
- **OpenRouter API Integration** - Access to Claude, GPT-4, Gemini, and more
- **Automatic Translation** - One-click translation of title, content, and excerpt
- **Batch Translation** - Translate multiple fields at once
- **SEO-Optimized Translations** - AI understands context and SEO requirements

### SEO-Friendly URLs
- **Clean URL Structure:** `yoursite.com/de/article-slug/`
- **Translated Slugs:** Each language can have its own URL slug
- **Proper Redirects:** Language detection and routing
- **WordPress Rewrite Rules:** Native integration

### Per-Language SEO Metadata
- **Meta Title** - Custom title tag per language
- **Meta Description** - Unique descriptions for each language
- **Meta Keywords** - Language-specific keywords
- **Translated Slugs** - SEO-friendly URLs in each language
- **Hreflang Tags** - Automatic hreflang output for search engines
- **Open Graph Tags** - Proper OG tags per language

### Translation Editor (Polylang-style)
- **Tabbed Interface** - Edit all languages from one metabox
- **Character Counters** - SEO-optimized length indicators
- **Auto-Translate Button** - One-click AI translation per language
- **Translation Status** - Track translated vs pending content
- **Save Without Reload** - AJAX-powered saving

### Language Switcher
- **Multiple Styles:** Dropdown, horizontal, vertical, minimal
- **Flag Support:** Show country flags with language names
- **Flexible Placement:**
  - Shortcode: `[mat_language_switcher]`
  - Menu Integration: Automatic menu item
  - Floating Widget: Bottom-right floating button
  - Footer Widget: Horizontal footer bar

### Supported Languages (24 EU Languages + More)
- English, German, French, Spanish, Italian, Portuguese
- Dutch, Polish, Swedish, Danish, Finnish, Norwegian
- Czech, Slovak, Hungarian, Romanian, Bulgarian, Greek
- Croatian, Slovenian, Estonian, Latvian, Lithuanian, Maltese, Irish
- Plus: Japanese, Korean, Chinese, Russian, Arabic, Hindi, Thai, Vietnamese, Turkish, Ukrainian

## 📦 Installation

1. Upload the `multilingual-ai-translator` folder to `/wp-content/plugins/`
2. Activate through the 'Plugins' menu
3. Go to **MultiLingual AI Translator** → **Settings**
4. Enter your OpenRouter API key
5. Configure default language and enabled languages
6. Start translating!

## ⚙️ Configuration

### API Settings
1. Get an API key from [OpenRouter.ai](https://openrouter.ai/)
2. Go to **MultiLingual AI Translator** → **API Settings**
3. Enter your API key
4. Select default AI model

### Language Configuration
1. Go to **MultiLingual AI Translator** → **Languages**
2. Enable/disable languages
3. Set default language
4. Reorder language priority

### URL Settings
- SEO-friendly URLs enabled by default
- URL structure: `/{language-code}/{post-slug}/`
- Flush permalinks after changing settings

## 🔧 Usage

### Translating Content

1. Edit any post or page
2. Find the **Translations** metabox
3. Click on a language tab
4. Either:
   - Click **Auto-Translate** for AI translation
   - Manually enter translations
5. Fill in SEO fields (title, description, keywords, slug)
6. Click **Save Translation**

### Adding Language Switcher

**Shortcode:**
```
[mat_language_switcher style="dropdown" show_flags="true" show_names="true"]
```

**In Theme:**
```php
<?php echo do_shortcode('[mat_language_switcher]'); ?>
```

**Floating Widget:**
Enable in Settings → Switcher Position → Bottom Right

### Programmatic Access

```php
// Get translation for current post
$translation = MAT_Database_Handler::get_translation( $post_id, 'de', 'content' );

// Get all translations for a post
$translations = MAT_Database_Handler::get_post_translations( $post_id );

// Get SEO meta
$seo = MAT_Database_Handler::get_seo_meta( $post_id, 'de' );

// Translate via API
$api = new MAT_OpenRouter_API();
$result = $api->translate( 'Hello World', 'en', 'de' );
```

## 🗄️ Database Structure

The plugin creates these custom tables:

- `{prefix}_mat_languages` - Language configuration
- `{prefix}_mat_translations` - Translated content storage
- `{prefix}_mat_seo_meta` - Per-language SEO metadata
- `{prefix}_mat_translation_queue` - Translation queue for batch processing

## 📁 File Structure

```
multilingual-ai-translator/
├── admin/
│   ├── css/
│   │   ├── admin.css
│   │   └── translation-editor.css
│   ├── js/
│   │   └── admin.js
│   └── class-admin-menu.php
├── includes/
│   ├── class-database-handler.php
│   ├── class-frontend-handler.php
│   ├── class-language-switcher.php
│   ├── class-openrouter-api.php
│   ├── class-plugin-core.php
│   ├── class-translation-editor.php
│   └── class-url-handler.php
├── templates/
│   └── admin/
│       ├── settings-page.php
│       ├── languages-page.php
│       ├── api-settings.php
│       └── ...
├── languages/
├── multilingual-ai-translator.php
└── README.md
```

## 🔄 Changelog

### 2.02 (Current)
- Complete rewrite of translation system
- Added TranslatePress/Polylang-style workflow
- SEO-friendly URL structure (/{lang}/{slug}/)
- Per-language SEO metadata (title, description, keywords, slug)
- New Translation Editor metabox with tabbed interface
- Frontend content filtering with proper hreflang tags
- Redesigned language switcher with multiple styles
- Auto-translate button with AJAX integration
- Proper URL rewriting with WordPress rewrite API

### 2.01
- Professional UI/UX redesign
- Improved admin interface
- Better language management

### 2.0.0
- Complete rebuild with modern architecture
- OpenRouter API integration
- Multiple AI model support

## 🤝 Support

For issues or feature requests, please visit the GitHub repository.

## 📄 License

GPL-2.0+ - see LICENSE file for details.
