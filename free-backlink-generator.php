<?php
/*
Plugin Name: Free Backlink Generator
Description: A plugin to generate backlinks for a given website URL.
Version: 1.0
Author: Saadulla Darwesh A.
Author URI: https://github.com/saadulla

*/

if (!defined('WPINC')) {
    die;
}

// Enqueue scripts and styles
function fbg_enqueue_scripts() {
    wp_enqueue_style('fbg-style', plugins_url('style.css', __FILE__));
    wp_enqueue_script('fbg-script', plugins_url('script.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('fbg-script', 'fbg_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fbg_nonce')
    ));

    if (is_admin()) {
        wp_enqueue_script('fbg-admin-script', plugins_url('admin.js', __FILE__), array('jquery'), null, true);
    }
}
add_action('wp_enqueue_scripts', 'fbg_enqueue_scripts');

// Form shortcode
function fbg_form_shortcode() {
    ob_start();
    ?>
    <div class="fbg-container">
        <form id="fbg-form">
            <input type="text" id="fbg-url" name="website_url" placeholder="Enter your website URL (e.g., example.com)" required>
            <button type="submit">Generate Backlinks</button>
        </form>
        <div id="fbg-results"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('free_backlink_generator', 'fbg_form_shortcode');

// Add rate limiting function
function fbg_check_rate_limit($ip_address) {
    $transient_key = 'fbg_rate_limit_' . md5($ip_address);
    $rate_limit = get_transient($transient_key);
    
    if ($rate_limit !== false) {
        return false;
    }
    
    set_transient($transient_key, 1, 60);
    return true;
}

// Handle form submission via AJAX
function fbg_generate_urls() {
    check_ajax_referer('fbg_nonce', 'nonce');
    
    // Rate limiting
    $ip_address = $_SERVER['REMOTE_ADDR'];
    if (!fbg_check_rate_limit($ip_address)) {
        wp_send_json_error('Rate limit exceeded. Please wait 1 minute.');
        return;
    }

    $website_url = sanitize_text_field($_POST['website_url']);
    
    // Strict URL validation
    if (!filter_var('https://' . $website_url, FILTER_VALIDATE_URL)) {
        wp_send_json_error('Invalid URL format');
        return;
    }

    $website_url = preg_replace('#^https?://#', '', $website_url);
    $templates = get_option('fbg_url_templates', array());
    $generated_urls = array();

    foreach ($templates as $template) {
        $generated_urls[] = esc_url(str_replace('{website}', $website_url, $template));
    }

    wp_send_json_success($generated_urls);
}
add_action('wp_ajax_fbg_generate_urls', 'fbg_generate_urls');
add_action('wp_ajax_nopriv_fbg_generate_urls', 'fbg_generate_urls');

// Admin menu
function fbg_admin_menu() {
    add_menu_page('Free Backlink Generator', 'Free Backlink Generator', 'manage_options', 'free-backlink-generator', 'fbg_admin_page');
}
add_action('admin_menu', 'fbg_admin_menu');

// Admin page
function fbg_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    ?>
    <div class="wrap">
        <h1>Free Backlink Generator</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('fbg_url_templates_group');
            do_settings_sections('free-backlink-generator');
            submit_button();
            ?>
        </form>
        <h2>Test URL Templates</h2>
        <form id="fbg-test-form">
            <input type="text" id="fbg-test-url" name="test_url" placeholder="Enter a URL to test (e.g., example.com)" required>
            <button type="submit">Test URLs</button>
        </form>
        <div id="fbg-test-results"></div>
    </div>
    <?php
}

// Register settings
function fbg_register_settings() {
    register_setting('fbg_url_templates_group', 'fbg_url_templates', 'fbg_sanitize_url_templates');
    add_settings_section('fbg_main_section', 'URL Templates', null, 'free-backlink-generator');
    add_settings_field('fbg_url_templates_field', 'URL Templates', 'fbg_url_templates_field_callback', 'free-backlink-generator', 'fbg_main_section');
}
add_action('admin_init', 'fbg_register_settings');

// URL templates field callback
function fbg_url_templates_field_callback() {
    $templates = get_option('fbg_url_templates', array());
    $value = implode("\n", $templates);
    
    echo '<div class="fbg-textarea-container" style="position: relative;">';
    echo '<div class="line-numbers" style="position: absolute; left: 0; top: 0; padding: 5px; color: #888; text-align: right; background: #f7f7f7; border-right: 1px solid #ddd; user-select: none; overflow-y: hidden;"></div>';
    echo '<textarea name="fbg_url_templates" rows="10" cols="110" style="padding-left: 35px;">' . esc_textarea($value) . '</textarea>';
    echo '</div>';
    echo '<p>Enter one URL template per line. Use <code>{website}</code> as a placeholder for the website URL.</p>';
    
    // Add JavaScript for line numbers
    ?>
    <script>
    jQuery(document).ready(function($) {
        function updateLineNumbers() {
            var textarea = $('textarea[name="fbg_url_templates"]');
            var lineNumbers = textarea.siblings('.line-numbers');
            var lines = textarea.val().split('\n').length;
            var numbers = [];
            for(var i = 1; i <= lines; i++) {
                numbers.push(i);
            }
            lineNumbers.html(numbers.join('<br>'));
            lineNumbers.height(textarea.height());
            
            // Sync scroll position
            lineNumbers.scrollTop(textarea.scrollTop());
        }
        
        // Update on load and change
        updateLineNumbers();
        $('textarea[name="fbg_url_templates"]').on('input scroll keyup', function() {
            updateLineNumbers();
        });
    });
    </script>
    <?php
}

// Sanitize URL templates
function fbg_sanitize_url_templates($input) {
    if (is_array($input)) {
        $input = implode("\n", $input);
    }

    $templates = explode("\n", $input);
    $templates = array_map('trim', $templates);
    $templates = array_filter($templates);

    return $templates;
}

// Test URLs function with proper security
function fbg_test_urls() {
    check_ajax_referer('fbg_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
        return;
    }

    $test_url = sanitize_text_field($_POST['test_url']);
    
    if (!filter_var('https://' . $test_url, FILTER_VALIDATE_URL)) {
        wp_send_json_error('Invalid URL format');
        return;
    }

    $templates = get_option('fbg_url_templates', array());
    $results = array();

    foreach ($templates as $template) {
        $generated_url = str_replace('{website}', $test_url, $template);
        $response = wp_remote_get($generated_url, array(
            'timeout' => 5,
            'sslverify' => true,
            'user-agent' => 'WordPress/' . get_bloginfo('version'),
            'headers' => array('Accept' => 'text/html')
        ));

        if (is_wp_error($response)) {
            $results[] = array(
                'url' => esc_url($generated_url),
                'status' => 'error',
                'message' => esc_html($response->get_error_message())
            );
        } else {
            $results[] = array(
                'url' => esc_url($generated_url),
                'status' => wp_remote_retrieve_response_code($response)
            );
        }
    }

    wp_send_json_success($results);
}
add_action('wp_ajax_fbg_test_urls', 'fbg_test_urls');

// Handle single URL test via AJAX
function fbg_test_single_url() {
    check_ajax_referer('fbg_nonce', 'nonce');

    $url = esc_url_raw($_POST['url']);
    $response = wp_remote_get($url, array('timeout' => 10, 'sslverify' => true));

    if (is_wp_error($response)) {
        wp_send_json_error();
    } else {
        wp_send_json_success();
    }
}
add_action('wp_ajax_fbg_test_single_url', 'fbg_test_single_url');