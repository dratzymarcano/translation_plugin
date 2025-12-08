<?php
/**
 * OpenRouter API Handler
 *
 * Handles all AI translation requests through OpenRouter API.
 * Supports multiple AI models including Claude, GPT-4, and Gemini.
 *
 * @package MultiLingual_AI_Translator
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MAT_OpenRouter_API {

    /**
     * API key
     *
     * @var string
     */
    private $api_key;

    /**
     * API endpoint
     *
     * @var string
     */
    private $api_endpoint = 'https://openrouter.ai/api/v1/chat/completions';

    /**
     * Default model
     *
     * @var string
     */
    private $default_model;

    /**
     * Available models
     *
     * @var array
     */
    private $available_models = array(
        'anthropic/claude-3.5-sonnet'   => 'Claude 3.5 Sonnet (Recommended)',
        'anthropic/claude-3-opus'       => 'Claude 3 Opus (Most Capable)',
        'anthropic/claude-3-sonnet'     => 'Claude 3 Sonnet',
        'anthropic/claude-3-haiku'      => 'Claude 3 Haiku (Fast)',
        'openai/gpt-4-turbo'            => 'GPT-4 Turbo',
        'openai/gpt-4o'                 => 'GPT-4o (Balanced)',
        'openai/gpt-4o-mini'            => 'GPT-4o Mini (Fast)',
        'google/gemini-pro-1.5'         => 'Gemini Pro 1.5',
        'google/gemini-flash-1.5'       => 'Gemini Flash 1.5 (Fast)',
        'meta-llama/llama-3.1-70b-instruct' => 'Llama 3.1 70B',
        'mistralai/mistral-large'       => 'Mistral Large',
    );

    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option( 'mat_api_settings', array() );
        // Check both possible API key locations for backwards compatibility
        $this->api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
        if ( empty( $this->api_key ) ) {
            $this->api_key = get_option( 'mat_openrouter_api_key', '' );
        }
        $this->default_model = isset( $settings['model'] ) ? $settings['model'] : '';
        if ( empty( $this->default_model ) ) {
            $this->default_model = get_option( 'mat_ai_model', 'anthropic/claude-3.5-sonnet' );
        }
    }

    /**
     * Check if API is configured
     *
     * @return bool
     */
    public function is_configured() {
        return ! empty( $this->api_key );
    }

    /**
     * Get available models
     *
     * @return array
     */
    public function get_available_models() {
        return $this->available_models;
    }

    /**
     * Translate content using OpenRouter API
     *
     * @param string $content     Content to translate
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @param array  $options     Additional options (context, tone, etc.)
     * @return array|WP_Error     Translation result or error
     */
    public function translate( $content, $source_lang, $target_lang, $options = array() ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'api_not_configured', __( 'OpenRouter API key is not configured.', 'multilingual-ai-translator' ) );
        }

        if ( empty( $content ) ) {
            return new WP_Error( 'empty_content', __( 'No content provided for translation.', 'multilingual-ai-translator' ) );
        }

        // Get language names
        $source_name = $this->get_language_name( $source_lang );
        $target_name = $this->get_language_name( $target_lang );

        // Build the prompt
        $system_prompt = $this->build_system_prompt( $options );
        $user_prompt = $this->build_translation_prompt( $content, $source_name, $target_name, $options );

        // Make API request
        $response = $this->make_request( $system_prompt, $user_prompt, $options );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return array(
            'success'     => true,
            'translation' => $response,
            'source_lang' => $source_lang,
            'target_lang' => $target_lang,
            'model'       => isset( $options['model'] ) ? $options['model'] : $this->default_model,
        );
    }

    /**
     * Batch translate multiple strings
     *
     * @param array  $strings     Array of strings to translate
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @param array  $options     Additional options
     * @return array              Array of translations or errors
     */
    public function batch_translate( $strings, $source_lang, $target_lang, $options = array() ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'api_not_configured', __( 'OpenRouter API key is not configured.', 'multilingual-ai-translator' ) );
        }

        if ( empty( $strings ) || ! is_array( $strings ) ) {
            return new WP_Error( 'invalid_input', __( 'Invalid strings array provided.', 'multilingual-ai-translator' ) );
        }

        $results = array();

        // For small batches, use single-call approach with numbered items
        if ( count( $strings ) <= 10 ) {
            $numbered_content = '';
            foreach ( $strings as $index => $string ) {
                $numbered_content .= "[" . ( $index + 1 ) . "] " . $string . "\n";
            }

            $options['preserve_numbering'] = true;
            $result = $this->translate( $numbered_content, $source_lang, $target_lang, $options );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            // Parse numbered results
            $translated_lines = explode( "\n", trim( $result['translation'] ) );
            foreach ( $translated_lines as $line ) {
                if ( preg_match( '/^\[(\d+)\]\s*(.+)$/', $line, $matches ) ) {
                    $index = (int) $matches[1] - 1;
                    if ( isset( $strings[ $index ] ) ) {
                        $results[ $index ] = $matches[2];
                    }
                }
            }

            // Fill any missing with original
            foreach ( $strings as $index => $string ) {
                if ( ! isset( $results[ $index ] ) ) {
                    $results[ $index ] = $string;
                }
            }

            return $results;
        }

        // For larger batches, process in chunks
        $chunks = array_chunk( $strings, 10, true );
        
        foreach ( $chunks as $chunk ) {
            $chunk_result = $this->batch_translate( array_values( $chunk ), $source_lang, $target_lang, $options );
            
            if ( is_wp_error( $chunk_result ) ) {
                // On error, preserve original strings
                foreach ( $chunk as $key => $value ) {
                    $results[ $key ] = $value;
                }
                continue;
            }

            $chunk_keys = array_keys( $chunk );
            foreach ( $chunk_result as $index => $translation ) {
                if ( isset( $chunk_keys[ $index ] ) ) {
                    $results[ $chunk_keys[ $index ] ] = $translation;
                }
            }

            // Rate limiting - small delay between chunks
            usleep( 250000 ); // 250ms
        }

        return $results;
    }

    /**
     * Translate SEO content with optimization
     *
     * @param array  $seo_data    SEO data (title, description, keywords)
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @return array|WP_Error     Translated SEO data or error
     */
    public function translate_seo_content( $seo_data, $source_lang, $target_lang ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'api_not_configured', __( 'OpenRouter API key is not configured.', 'multilingual-ai-translator' ) );
        }

        $source_name = $this->get_language_name( $source_lang );
        $target_name = $this->get_language_name( $target_lang );

        $system_prompt = "You are an expert SEO specialist and professional translator. Your task is to translate SEO metadata while:
1. Maintaining SEO effectiveness in the target language
2. Preserving keyword relevance and search intent
3. Adapting to local SEO best practices for {$target_name}-speaking markets
4. Keeping optimal character lengths (title: 50-60 chars, description: 120-160 chars)
5. Using natural, engaging language that encourages clicks

Return ONLY a JSON object with the translated fields, no additional text.";

        $content_json = wp_json_encode( array(
            'title'       => isset( $seo_data['title'] ) ? $seo_data['title'] : '',
            'description' => isset( $seo_data['description'] ) ? $seo_data['description'] : '',
            'keywords'    => isset( $seo_data['keywords'] ) ? $seo_data['keywords'] : '',
        ) );

        $user_prompt = "Translate this SEO metadata from {$source_name} to {$target_name}. Return a JSON object with the same keys.

Input:
{$content_json}

Output the translated JSON only:";

        $response = $this->make_request( $system_prompt, $user_prompt, array( 'temperature' => 0.3 ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Parse JSON response
        $translated = json_decode( $response, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            // Try to extract JSON from response (handle markdown code blocks)
            if ( preg_match( '/```(?:json)?\s*(\{.*?\})\s*```/s', $response, $matches ) ) {
                $translated = json_decode( $matches[1], true );
            } elseif ( preg_match( '/\{.*\}/s', $response, $matches ) ) {
                $translated = json_decode( $matches[0], true );
            }
        }

        if ( ! is_array( $translated ) ) {
            // Log the raw response for debugging
            error_log( 'MAT SEO Translation Parse Error. Raw response: ' . $response );
            return new WP_Error( 'parse_error', __( 'Failed to parse translated SEO content.', 'multilingual-ai-translator' ) );
        }

        return array(
            'success'     => true,
            'translation' => $translated,
            'source_lang' => $source_lang,
            'target_lang' => $target_lang,
        );
    }

    /**
     * Translate HTML content while preserving structure
     *
     * @param string $html        HTML content
     * @param string $source_lang Source language
     * @param string $target_lang Target language
     * @return array|WP_Error     Translation result
     */
    public function translate_html( $html, $source_lang, $target_lang ) {
        $options = array(
            'preserve_html' => true,
            'instructions'  => 'Preserve ALL HTML tags exactly as they appear. Only translate the text content between tags. Do not modify tag attributes, URLs, or code.',
        );

        return $this->translate( $html, $source_lang, $target_lang, $options );
    }

    /**
     * Build system prompt
     *
     * @param array $options Options
     * @return string
     */
    private function build_system_prompt( $options ) {
        $prompt = "You are a professional translator with expertise in multiple languages. Your translations should be:
1. Accurate and faithful to the original meaning
2. Natural and fluent in the target language
3. Culturally appropriate and localized
4. Consistent in terminology and style";

        if ( ! empty( $options['preserve_html'] ) ) {
            $prompt .= "\n\nIMPORTANT: The content contains HTML markup. Preserve ALL HTML tags exactly. Only translate the text content between tags. Never modify HTML attributes, URLs, class names, or any code.";
        }

        if ( ! empty( $options['preserve_numbering'] ) ) {
            $prompt .= "\n\nIMPORTANT: The content is numbered with [1], [2], etc. Keep the same numbering format in your response.";
        }

        if ( ! empty( $options['tone'] ) ) {
            $prompt .= "\n\nTone: " . sanitize_text_field( $options['tone'] );
        }

        if ( ! empty( $options['context'] ) ) {
            $prompt .= "\n\nContext: " . sanitize_text_field( $options['context'] );
        }

        return $prompt;
    }

    /**
     * Build translation prompt
     *
     * @param string $content     Content to translate
     * @param string $source_name Source language name
     * @param string $target_name Target language name
     * @param array  $options     Options
     * @return string
     */
    private function build_translation_prompt( $content, $source_name, $target_name, $options ) {
        $prompt = "Translate the following content from {$source_name} to {$target_name}.\n\n";

        if ( ! empty( $options['instructions'] ) ) {
            $prompt .= "Additional instructions: " . $options['instructions'] . "\n\n";
        }

        $prompt .= "Content to translate:\n\n{$content}\n\n";
        $prompt .= "Provide only the translation, no explanations or additional text.";

        return $prompt;
    }

    /**
     * Make API request to OpenRouter
     *
     * @param string $system_prompt System prompt
     * @param string $user_prompt   User prompt
     * @param array  $options       Options (model, temperature, etc.)
     * @return string|WP_Error      Response text or error
     */
    private function make_request( $system_prompt, $user_prompt, $options = array() ) {
        $model = isset( $options['model'] ) ? $options['model'] : $this->default_model;
        $temperature = isset( $options['temperature'] ) ? floatval( $options['temperature'] ) : 0.3;
        $max_tokens = isset( $options['max_tokens'] ) ? intval( $options['max_tokens'] ) : 4096;

        $body = array(
            'model'       => $model,
            'messages'    => array(
                array(
                    'role'    => 'system',
                    'content' => $system_prompt,
                ),
                array(
                    'role'    => 'user',
                    'content' => $user_prompt,
                ),
            ),
            'temperature' => $temperature,
            'max_tokens'  => $max_tokens,
        );

        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type'  => 'application/json',
            'HTTP-Referer'  => home_url(),
            'X-Title'       => get_bloginfo( 'name' ) . ' - MultiLingual AI Translator',
        );

        // Retry logic for transient errors
        $max_retries = 2;
        $attempt = 0;
        $response = null;

        while ( $attempt <= $max_retries ) {
            $response = wp_remote_post( $this->api_endpoint, array(
                'headers' => $headers,
                'body'    => wp_json_encode( $body ),
                'timeout' => 120,
            ) );

            if ( is_wp_error( $response ) ) {
                // Network error, retry immediately
                $attempt++;
                continue;
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            
            // If success or client error (4xx), don't retry
            if ( $response_code === 200 || ( $response_code >= 400 && $response_code < 500 && $response_code !== 429 ) ) {
                break;
            }

            // If rate limit (429) or server error (5xx), wait and retry
            if ( $response_code === 429 || $response_code >= 500 ) {
                $attempt++;
                if ( $attempt <= $max_retries ) {
                    sleep( 1 * $attempt ); // Exponential backoff: 1s, 2s
                    continue;
                }
            }
            
            break;
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $data = json_decode( $response_body, true );

        if ( $response_code !== 200 ) {
            $error_message = isset( $data['error']['message'] ) 
                ? $data['error']['message'] 
                : __( 'API request failed', 'multilingual-ai-translator' );
            
            return new WP_Error( 'api_error', $error_message, array( 'code' => $response_code ) );
        }

        if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid API response format.', 'multilingual-ai-translator' ) );
        }

        return trim( $data['choices'][0]['message']['content'] );
    }

    /**
     * Get language name from code
     *
     * @param string $code Language code
     * @return string
     */
    private function get_language_name( $code ) {
        // Try to get from database first
        if ( class_exists( 'MAT_Database_Handler' ) ) {
            $lang = MAT_Database_Handler::get_language( $code );
            if ( $lang && ! empty( $lang['name'] ) ) {
                return $lang['name'];
            }
        }

        // Fallback list
        $languages = array(
            'en' => 'English',
            'de' => 'German',
            'fr' => 'French',
            'es' => 'Spanish',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'fi' => 'Finnish',
            'no' => 'Norwegian',
            'cs' => 'Czech',
            'sk' => 'Slovak',
            'hu' => 'Hungarian',
            'ro' => 'Romanian',
            'bg' => 'Bulgarian',
            'el' => 'Greek',
            'hr' => 'Croatian',
            'sl' => 'Slovenian',
            'et' => 'Estonian',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian',
            'mt' => 'Maltese',
            'ga' => 'Irish',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
            'ru' => 'Russian',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'th' => 'Thai',
            'vi' => 'Vietnamese',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
        );

        return isset( $languages[ $code ] ) ? $languages[ $code ] : ucfirst( $code );
    }

    /**
     * Test API connection
     *
     * @return array|WP_Error Test result
     */
    public function test_connection() {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'api_not_configured', __( 'API key is not configured.', 'multilingual-ai-translator' ) );
        }

        $result = $this->translate( 'Hello, how are you?', 'en', 'de', array(
            'max_tokens' => 50,
        ) );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return array(
            'success' => true,
            'message' => __( 'API connection successful!', 'multilingual-ai-translator' ),
            'test_translation' => $result['translation'],
            'model' => $this->default_model,
        );
    }

    /**
     * Get API usage statistics
     *
     * @return array
     */
    public function get_usage_stats() {
        // This would require tracking in a custom table
        // For now, return placeholder
        return array(
            'requests_today' => 0,
            'tokens_used'    => 0,
            'last_request'   => null,
        );
    }
}
