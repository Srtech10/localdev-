<?php
/*
Plugin Name: Page Content Editor, Gemini Improver & Previewer
Description: Robust plugin to view, edit, preview, and improve raw WordPress page content via the Gemini API. Now with an editable system prompt, a 90‑sec API timeout, a loading spinner, and extra checks to ensure that the improved content exactly matches the original structure.
Version: 1.7.0
Author: Your Name
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enable debug logging.
define( 'PCEP_DEBUG_MODE', true );

/**
 * Remove markdown code fences from Gemini output.
 */
function pcep_remove_code_fences( $content ) {
    if ( ! is_string( $content ) ) {
        $content = json_encode( $content );
    }
    // Remove any starting fence (e.g. ```json or ```html) and any trailing fence.
    $content = preg_replace( '/^\s*```(?:json|html)?\s*\n?/i', '', $content );
    $content = preg_replace( '/\n?\s*```+\s*$/', '', $content );
    return trim( $content );
}

/**
 * Recursively compare the structure of two arrays.
 */
function pcep_compare_structure( $orig, $new ) {
    if ( ! is_array( $orig ) || ! is_array( $new ) ) {
        return false;
    }
    foreach ( $orig as $key => $value ) {
        if ( ! array_key_exists( $key, $new ) ) {
            return false;
        }
        if ( is_array( $value ) ) {
            if ( ! pcep_compare_structure( $value, $new[ $key ] ) ) {
                return false;
            }
        }
    }
    return true;
}

/**
 * Log debug events.
 */
function pcep_log_event( $message ) {
    if ( ! PCEP_DEBUG_MODE ) {
        return;
    }
    $log = get_option( 'pcep_debug_log', '' );
    $timestamp = current_time( 'mysql' );
    $entry = "[$timestamp] $message";
    $log .= ( $log ? "\n" : '' ) . $entry;
    update_option( 'pcep_debug_log', $log );
}

/**
 * Get the debug log.
 */
function pcep_get_log() {
    return get_option( 'pcep_debug_log', '' );
}

/**
 * Clear the debug log.
 */
function pcep_clear_log() {
    update_option( 'pcep_debug_log', '' );
}

/**
 * Add our admin menu.
 */
add_action( 'admin_menu', 'pcep_add_admin_menu' );
function pcep_add_admin_menu() {
    add_menu_page(
        'Page Content Editor',
        'Page Content Editor',
        'manage_options',
        'page-content-editor-previewer',
        'pcep_admin_page',
        'dashicons-edit-page'
    );
}

/**
 * Main admin page.
 */
function pcep_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $page_id         = 0;
    $raw_content     = "";
    $new_content     = "";
    $user_prompt     = "Improve this content for better SEO, improved text and layout while preserving its structure.";
    // Editable system prompt, stored in options.
    $system_prompt   = get_option( 'pcep_gemini_system_prompt', "Return the improved content as valid JSON (if Beaver Builder data) or as valid HTML (if not), without any markdown formatting, code fences, or extra text. Do not change any keys." );
    $combined_prompt = "";
    $gemini_status   = null;
    $gemini_msg      = "";
    $apply_msg       = "";
    $is_beaver_page  = false;

    // Process Gemini settings form.
    if ( isset( $_POST['pcep_save_gemini_settings'] ) && check_admin_referer( 'pcep_gemini_settings_nonce', 'pcep_gemini_settings_nonce_field' ) ) {
        $api_key = sanitize_text_field( $_POST['pcep_gemini_api_key'] );
        update_option( 'pcep_gemini_api_key', $api_key );
        $system_prompt_input = wp_unslash( $_POST['pcep_gemini_system_prompt'] );
        update_option( 'pcep_gemini_system_prompt', sanitize_text_field( $system_prompt_input ) );
        pcep_log_event( "Gemini API key and system prompt updated." );
        $apply_msg = '<div class="notice notice-success is-dismissible"><p>Gemini API settings saved.</p></div>';
    }
    if ( isset( $_POST['pcep_test_gemini_connection'] ) && check_admin_referer( 'pcep_gemini_settings_nonce', 'pcep_gemini_settings_nonce_field' ) ) {
        $test = pcep_test_gemini_connection();
        if ( is_wp_error( $test ) ) {
            $gemini_status = false;
            $gemini_msg    = $test->get_error_message();
            pcep_log_event( "Gemini test connection failed: " . $gemini_msg );
            $apply_msg = '<div class="notice notice-error is-dismissible"><p>Gemini connection test failed: ' . esc_html( $gemini_msg ) . '</p></div>';
        } else {
            $gemini_status = true;
            $gemini_msg    = "Connection successful.";
            pcep_log_event( "Gemini test connection successful." );
            $apply_msg = '<div class="notice notice-success is-dismissible"><p>Gemini connection test successful.</p></div>';
        }
    }

    // Process page editor form.
    if ( isset( $_POST['pcep_page_id'] ) && check_admin_referer( 'pcep_view_nonce', 'pcep_nonce' ) ) {
        $page_id = intval( $_POST['pcep_page_id'] );
        $post = get_post( $page_id );
        if ( $post && $post->post_type === 'page' ) {
            // Check if this is a Beaver Builder page.
            if ( metadata_exists( 'post', $page_id, '_fl_builder_data' ) ) {
                $raw_content = get_post_meta( $page_id, '_fl_builder_data', true );
                $is_beaver_page = true;
            } else {
                $raw_content = $post->post_content;
            }
            $new_content = isset( $_POST['pcep_new_content'] ) ? wp_unslash( $_POST['pcep_new_content'] ) : $raw_content;
            if ( isset( $_POST['pcep_prompt'] ) ) {
                $user_prompt = sanitize_textarea_field( $_POST['pcep_prompt'] );
            }
            // Build the combined prompt.
            if ( $is_beaver_page ) {
                $raw_ref = is_string( $raw_content ) ? $raw_content : json_encode( $raw_content, JSON_PRETTY_PRINT );
                $combined_prompt = $user_prompt . "\n\n" . $system_prompt . "\n\nRaw Content Reference (JSON):\n" . pcep_remove_code_fences( $raw_ref );
            } else {
                $raw_ref = is_string( $raw_content ) ? $raw_content : json_encode( $raw_content, JSON_PRETTY_PRINT );
                $combined_prompt = $user_prompt . "\n\n" . $system_prompt . "\n\nRaw Content Reference (HTML):\n" . pcep_remove_code_fences( $raw_ref );
            }

            $clean_new_content = pcep_remove_code_fences( $new_content );

            // Apply changes.
            if ( isset( $_POST['pcep_apply'] ) ) {
                if ( $is_beaver_page ) {
                    $original_str = is_string( $raw_content ) ? $raw_content : json_encode( $raw_content, JSON_PRETTY_PRINT );
                    $original_arr = json_decode( $original_str, true );
                    $new_arr      = json_decode( $clean_new_content, true );
                    if ( json_last_error() !== JSON_ERROR_NONE ) {
                        pcep_log_event( "Original content is not valid JSON for page ID $page_id." );
                        $apply_msg = '<div class="notice notice-error is-dismissible"><p>Original content is not valid JSON.</p></div>';
                    } elseif ( ! pcep_compare_structure( $original_arr, $new_arr ) ) {
                        pcep_log_event( "Gemini returned structure does not match the original for page ID $page_id." );
                        $apply_msg = '<div class="notice notice-error is-dismissible"><p>Gemini returned content structure does not match the original. Please try again.</p></div>';
                    } else {
                        update_post_meta( $page_id, '_fl_builder_data', $clean_new_content );
                        $updated_post = array(
                            'ID'           => $page_id,
                            'post_content' => '<!-- Beaver Builder layout updated -->'
                        );
                        wp_update_post( $updated_post, true );
                        delete_post_meta( $page_id, '_pcep_preview_content' );
                        pcep_log_event( "Updated Beaver Builder content for page ID $page_id." );
                        $apply_msg = '<div class="notice notice-success is-dismissible"><p>Content applied and updated successfully!</p></div>';
                    }
                } else {
                    $updated_post = array(
                        'ID'           => $page_id,
                        'post_content' => $clean_new_content,
                    );
                    $result = wp_update_post( $updated_post, true );
                    if ( is_wp_error( $result ) ) {
                        pcep_log_event( "Failed to apply content for page ID $page_id: " . $result->get_error_message() );
                        $apply_msg = '<div class="notice notice-error is-dismissible"><p>Error applying content: ' . esc_html( $result->get_error_message() ) . '</p></div>';
                    } else {
                        clean_post_cache( $page_id );
                        $post = get_post( $page_id );
                        $raw_content = $post->post_content;
                        delete_post_meta( $page_id, '_pcep_preview_content' );
                        pcep_log_event( "Applied new content for page ID $page_id." );
                        $apply_msg = '<div class="notice notice-success is-dismissible"><p>Content applied and updated successfully!</p></div>';
                    }
                }
            }
            // Preview changes.
            elseif ( isset( $_POST['pcep_preview'] ) ) {
                update_post_meta( $page_id, '_pcep_preview_content', $clean_new_content );
                pcep_log_event( "Saved preview content for page ID $page_id." );
                $preview_url = add_query_arg( array( 'pcv_preview' => $page_id ), get_permalink( $page_id ) );
                echo '<script>window.open("' . esc_url( $preview_url ) . '", "_blank");</script>';
            }
            // Improve via Gemini.
            elseif ( isset( $_POST['pcep_improve'] ) ) {
                $api_key = get_option( 'pcep_gemini_api_key', '' );
                if ( empty( $api_key ) ) {
                    pcep_log_event( "Gemini API key not set. Cannot improve content." );
                    $apply_msg = '<div class="notice notice-warning is-dismissible"><p>Gemini API key is not set. Please set it in the Gemini API Settings.</p></div>';
                } else {
                    $api_endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-thinking-exp-01-21:generateContent";
                    $url = $api_endpoint . "?key=" . urlencode( $api_key );
                    pcep_log_event( "Raw Content: " . pcep_remove_code_fences( $raw_ref ) );
                    $payload = array(
                        'contents' => array(
                            array(
                                'role'  => 'user',
                                'parts' => array(
                                    array(
                                        'text' => $combined_prompt
                                    )
                                )
                            )
                        ),
                        'generationConfig' => array(
                            'temperature'      => 0.7,
                            'topK'             => 64,
                            'topP'             => 0.95,
                            'maxOutputTokens'  => 65536,
                            'responseMimeType' => 'text/plain'
                        ),
                    );
                    if ( PCEP_DEBUG_MODE ) {
                        pcep_log_event( "Gemini API Request URL: " . $url );
                        pcep_log_event( "Gemini API Payload: " . wp_json_encode( $payload ) );
                    }
                    $response = wp_remote_post( $url, array(
                        'headers' => array( 'Content-Type' => 'application/json' ),
                        'body'    => wp_json_encode( $payload ),
                        'timeout' => 90,
                    ) );
                    if ( is_wp_error( $response ) ) {
                        pcep_log_event( "Gemini API call failed: " . $response->get_error_message() );
                        $apply_msg = '<div class="notice notice-error is-dismissible"><p>Gemini API call failed: ' . esc_html( $response->get_error_message() ) . '</p></div>';
                    } else {
                        $response_code = wp_remote_retrieve_response_code( $response );
                        pcep_log_event( "Gemini API Response Code: " . $response_code );
                        $body = wp_remote_retrieve_body( $response );
                        pcep_log_event( "Gemini API Response Body: " . $body );
                        if ( $response_code == 200 ) {
                            $data = json_decode( $body, true );
                            if ( $data !== null && is_array( $data ) && isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
                                $improved = pcep_remove_code_fences( $data['candidates'][0]['content']['parts'][0]['text'] );
                                $new_content = $improved;
                                pcep_log_event( "Gemini API improvement successful for page ID $page_id." );
                                $apply_msg = '<div class="notice notice-success is-dismissible"><p>Content improved by Gemini!</p></div>';
                            } else {
                                pcep_log_event( "Gemini API response did not contain improved content or was not in expected format." );
                                $apply_msg = '<div class="notice notice-warning is-dismissible"><p>Gemini API response was empty or in an unexpected format. Please check the debug log.</p></div>';
                            }
                        } else {
                            pcep_log_event( "Gemini API request failed with HTTP code: " . $response_code . ". Response body: " . $body );
                            $apply_msg = '<div class="notice notice-error is-dismissible"><p>Gemini API request failed with HTTP code: ' . esc_html( $response_code ) . '. Check the debug log.</p></div>';
                        }
                    }
                }
            }
        } else {
            pcep_log_event( "Invalid page selected: $page_id." );
            $apply_msg = '<div class="notice notice-error is-dismissible"><p>Invalid page selected.</p></div>';
        }
    }

    // Ensure raw content is a string.
    if ( ! is_string( $raw_content ) ) {
        $raw_content = json_encode( $raw_content, JSON_PRETTY_PRINT );
    }

    $display_new_content = pcep_remove_code_fences( $new_content );
    $pages = get_pages();
    ?>
    <div class="wrap">
        <h1>Page Content Editor & Gemini Improver</h1>
        <?php echo $apply_msg; ?>

        <!-- Workflow Instructions -->
        <div style="margin-bottom:20px; padding:10px; background:#eef; border:1px solid #ccd;">
            <strong>Workflow:</strong>
            <ol style="margin-left:20px;">
                <li><em>Preview</em> your changes.</li>
                <li><em>Edit in Beaver Builder</em> to launch the builder interface.</li>
                <li><em>Apply and Publish</em> to update the live site.</li>
                <li><em>Improve via Gemini</em> to generate improved content.</li>
            </ol>
        </div>

        <!-- Gemini API Settings Section -->
        <div class="pcep-section">
            <h2>Gemini API Settings</h2>
            <form method="post" class="pcep-form">
                <?php wp_nonce_field( 'pcep_gemini_settings_nonce', 'pcep_gemini_settings_nonce_field' ); ?>
                <label for="pcep_gemini_api_key" class="pcep-label">Gemini API Key:</label>
                <input type="text" name="pcep_gemini_api_key" id="pcep_gemini_api_key" value="<?php echo esc_attr( get_option( 'pcep_gemini_api_key', '' ) ); ?>" style="width:100%;" />
                <p class="description">Enter your Gemini API key here.</p>
                <label for="pcep_gemini_system_prompt" class="pcep-label">Gemini System Prompt:</label>
                <textarea name="pcep_gemini_system_prompt" id="pcep_gemini_system_prompt" rows="3" class="pcep-textarea"><?php echo esc_textarea( get_option( 'pcep_gemini_system_prompt', 'Return the improved content as valid JSON (if Beaver Builder data) or as valid HTML (if not), without any markdown formatting, code fences, or extra text. Do not change any keys.' ) ); ?></textarea>
                <p class="description">This is prepended to your improvement prompt.</p>
                <input type="submit" name="pcep_save_gemini_settings" class="button button-primary pcep-button" value="Save Settings" />
                <input type="submit" name="pcep_test_gemini_connection" class="button button-secondary pcep-button" value="Test Connection" />
            </form>
            <?php if ( ! is_null( $gemini_status ) ) : ?>
                <p class="pcep-status">Gemini Connection Status:
                    <input type="checkbox" <?php echo $gemini_status ? 'checked' : ''; ?> disabled />
                    <?php echo esc_html( $gemini_msg ); ?>
                </p>
            <?php endif; ?>
        </div>

        <hr/>

        <!-- Page Editor Section -->
        <div class="pcep-section">
            <h2>Page Content Editor</h2>
            <form method="post" class="pcep-form">
                <?php wp_nonce_field( 'pcep_view_nonce', 'pcep_nonce' ); ?>
                <label for="pcep_page_select" class="pcep-label">Select a page to edit:</label>
                <select name="pcep_page_id" id="pcep_page_select" class="pcep-select" onchange="this.form.submit();">
                    <option value="0">--Select Page--</option>
                    <?php
                    foreach ( $pages as $p ) {
                        $selected = ( $page_id === $p->ID ) ? 'selected' : '';
                        echo '<option value="' . esc_attr( $p->ID ) . '" ' . $selected . '>' . esc_html( $p->post_title ) . '</option>';
                    }
                    ?>
                </select>
            </form>

            <?php if ( $page_id ) : ?>
                <form method="post" class="pcep-form" id="pcep-form">
                    <?php wp_nonce_field( 'pcep_view_nonce', 'pcep_nonce' ); ?>
                    <input type="hidden" name="pcep_page_id" value="<?php echo esc_attr( $page_id ); ?>">
                    <div style="display: flex; gap: 20px; margin-top:20px;">
                        <div style="flex: 1;">
                            <h3 class="pcep-heading">Raw Content</h3>
                            <textarea rows="15" class="pcep-textarea" readonly><?php echo esc_textarea( $raw_content ); ?></textarea>
                        </div>
                        <div style="flex: 1;">
                            <h3 class="pcep-heading">New Generated Content</h3>
                            <textarea name="pcep_new_content" rows="15" class="pcep-textarea"><?php echo esc_textarea( $display_new_content ); ?></textarea>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <label for="pcep_prompt" class="pcep-label">Improvement Prompt:</label><br>
                        <textarea name="pcep_prompt" id="pcep_prompt" rows="3" class="pcep-textarea"><?php echo esc_textarea( $user_prompt ); ?></textarea>
                        <p class="description">This prompt will be appended with the system prompt and a reference to the raw content.</p>
                    </div>
                    <div style="margin-top: 20px;">
                        <input type="submit" name="pcep_preview" class="button button-secondary pcep-button" value="Preview">
                        <a href="<?php echo esc_url( add_query_arg( array( 'fl_builder' => '', 'fl_builder_ui' => '' ), get_permalink( $page_id ) ) ); ?>" target="_blank" class="button pcep-button">Edit in Beaver Builder</a>
                        <input type="submit" name="pcep_apply" class="button button-primary pcep-button" value="Apply and Publish">
                        <input type="submit" name="pcep_improve" class="button pcep-button" value="Improve via Gemini">
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <hr/>

        <!-- Debug Log Section -->
        <div class="pcep-section">
            <h2>Debug Log</h2>
            <button type="button" class="button button-secondary pcep-button" onclick="document.querySelector('.pcep-log-area').textContent = ''; wp.ajax.post( 'pcep_clear_log', { _ajax_nonce: '<?php echo wp_create_nonce( 'pcep-clear-log-nonce' ); ?>' } );">Clear Log</button>
            <div class="pcep-log-area" style="background:#f1f1f1; padding:10px; border:1px solid #ccc; height: 300px; overflow-y: scroll; white-space: pre-wrap; font-family: monospace; font-size: 12px;">
                <?php echo esc_html( pcep_get_log() ); ?>
            </div>
        </div>
    </div>

    <!-- Spinner / Loading Overlay -->
    <div id="pcep-spinner" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background: rgba(255,255,255,0.8); z-index:9999; text-align:center;">
        <div style="position: relative; top:40%; font-size:20px; font-weight:bold;">Loading…</div>
    </div>

    <script>
    (function(){
        var forms = document.querySelectorAll('.pcep-form');
        var spinner = document.getElementById('pcep-spinner');
        Array.prototype.forEach.call(forms, function(form){
            form.addEventListener('submit', function(){
                spinner.style.display = 'block';
            });
        });
    })();
    </script>

    <style>
        .pcep-section { margin-bottom: 30px; padding: 15px; border: 1px solid #eee; background: #fff; }
        .pcep-form label.pcep-label { display: block; margin-bottom: 5px; font-weight: bold; }
        .pcep-form input[type="text"].pcep-input,
        .pcep-form textarea.pcep-textarea,
        .pcep-form select.pcep-select { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; box-sizing: border-box; }
        .pcep-form textarea.pcep-textarea { font-family: monospace; }
        .pcep-form input.pcep-button, .pcep-form button.pcep-button, .pcep-form a.button { margin-right: 10px; }
        .pcep-status { margin-top: 10px; font-style: italic; }
        .pcep-heading { font-size: 1.2em; margin-bottom: 10px; }
        .notice { margin-top: 15px; }
    </style>
    <?php
}

/**
 * AJAX action to clear the debug log.
 */
add_action( 'wp_ajax_pcep_clear_log', 'pcep_ajax_clear_log' );
function pcep_ajax_clear_log() {
    check_ajax_referer( 'pcep-clear-log-nonce', '_ajax_nonce' );
    pcep_clear_log();
    wp_send_json_success();
}

/**
 * Test Gemini connection.
 */
function pcep_test_gemini_connection() {
    return pcep_gemini_api_call( 'ping' );
}

/**
 * Call the Gemini API.
 */
function pcep_gemini_api_call( $content_text_prompt ) {
    $api_key = get_option( 'pcep_gemini_api_key', '' );
    if ( empty( $api_key ) ) {
        return new WP_Error( 'no_api_key', 'Gemini API key is not set.' );
    }
    $api_endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-thinking-exp-01-21:generateContent";
    $url = $api_endpoint . "?key=" . urlencode( $api_key );
    $payload = array(
        'contents' => array(
            array(
                'role'  => 'user',
                'parts' => array(
                    array(
                        'text' => $content_text_prompt
                    )
                )
            )
        ),
        'generationConfig' => array(
            'temperature'      => 0.7,
            'topK'             => 64,
            'topP'             => 0.95,
            'maxOutputTokens'  => 1024,
            'responseMimeType' => 'text/plain'
        )
    );
    if ( PCEP_DEBUG_MODE ) {
        pcep_log_event( "Gemini API Request URL: " . $url );
        pcep_log_event( "Gemini API Payload: " . wp_json_encode( $payload ) );
    }
    $response = wp_remote_post( $url, array(
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( $payload ),
        'timeout' => 90,
    ) );
    if ( is_wp_error( $response ) ) {
        return $response;
    }
    $response_code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    if ( PCEP_DEBUG_MODE ) {
        pcep_log_event( "Gemini API Response Code: " . $response_code );
        pcep_log_event( "Gemini API Response Body: " . $body );
    }
    if ( $response_code != 200 ) {
        return new WP_Error( 'bad_response', 'Response code: ' . $response_code . '. Response body: ' . $body );
    }
    return $response;
}

/**
 * Front-end preview filter.
 */
add_filter( 'the_content', 'pcep_preview_content_filter', 10, 1 );
function pcep_preview_content_filter( $content ) {
    if ( is_page() && isset( $_GET['pcv_preview'] ) && is_numeric( $_GET['pcv_preview'] ) ) {
        $preview_post_id = intval( $_GET['pcv_preview'] );
        $preview_content = get_post_meta( $preview_post_id, '_pcep_preview_content', true );
        if ( $preview_content ) {
            // For Beaver Builder pages: if FLBuilderDisplay exists, try to decode and render the layout.
            if ( metadata_exists( 'post', $preview_post_id, '_fl_builder_data' ) && class_exists( 'FLBuilderDisplay' ) ) {
                $data = json_decode( $preview_content, true );
                if ( is_array( $data ) ) {
                    return FLBuilderDisplay::render_layout( $data );
                }
            }
            return $preview_content;
        }
    }
    return $content;
}
?>
