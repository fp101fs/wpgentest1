<?php
defined('ABSPATH') || exit;

/**
 * Plugin Name: Simple Weather Dashboard
 * Description: Displays current weather and news headlines for New York City in WordPress admin dashboard
 * Version: 1.2.0
 * Author: PlugPress
 * Text Domain: simple-weather-dashboard
 * Domain Path: /languages
 */

class SimpleWeatherDashboard {
    private $prefix = 'swd_';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_swd_get_weather', array($this, 'ajax_get_weather'));
        add_action('wp_ajax_swd_get_news', array($this, 'ajax_get_news'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            esc_html__('Weather Dashboard', 'simple-weather-dashboard'),
            esc_html__('Weather', 'simple-weather-dashboard'),
            'manage_options',
            'weather-dashboard',
            array($this, 'admin_page'),
            'dashicons-cloud',
            30
        );
    }
    
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'simple-weather-dashboard'));
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Weather Dashboard', 'simple-weather-dashboard') . '</h1>';
        
        // Main grid container for widgets
        echo '<div class="swd-dashboard-grid">';
        
        // Weather Panel
        echo '<div id="weather-container" class="swd-dashboard-card">';
        echo '<h2>' . esc_html__('New York City Weather', 'simple-weather-dashboard') . '</h2>';
        echo '<div id="weather-data">' . esc_html__('Loading weather data...', 'simple-weather-dashboard') . '</div>';
        echo '</div>';
        
        // News Panel
        echo '<div id="news-container" class="swd-dashboard-card">';
        echo '<h2>' . esc_html__('New York City News Headlines', 'simple-weather-dashboard') . '</h2>';
        echo '<div id="news-data">' . esc_html__('Loading news headlines...', 'simple-weather-dashboard') . '</div>';
        echo '</div>';
        
        // Hello Everyone Panel - NEW
        echo '<div id="hello-everyone-container" class="swd-dashboard-card swd-hello-panel">';
        echo '<h2>' . esc_html__('Hello Everyone', 'simple-weather-dashboard') . '</h2>';
        echo '<p>' . esc_html__('This is a custom panel added to the dashboard. Welcome to your WordPress admin area!', 'simple-weather-dashboard') . '</p>';
        echo '<p>' . esc_html__('You can customize this section with any information or links you find useful.', 'simple-weather-dashboard') . '</p>';
        echo '</div>';
        
        echo '</div>'; // .swd-dashboard-grid
        echo '</div>'; // .wrap
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_weather-dashboard') {
            return;
        }
        
        // Enqueue admin styles
        wp_enqueue_style('swd-admin-styles', plugins_url('swd-admin-styles.css', __FILE__), array(), '1.2.0');

        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Load weather data
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "swd_get_weather",
                        nonce: "' . wp_create_nonce('swd_weather_nonce') . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            $("#weather-data").html(response.data);
                        } else {
                            $("#weather-data").html("<p class=\"swd-error-message\">" + ' . json_encode(esc_html__('Error loading weather data.', 'simple-weather-dashboard')) . ' + "</p>");
                        }
                    },
                    error: function() {
                        $("#weather-data").html("<p class=\"swd-error-message\">" + ' . json_encode(esc_html__('Failed to load weather data.', 'simple-weather-dashboard')) . ' + "</p>");
                    }
                });
                
                // Load news data
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "swd_get_news",
                        nonce: "' . wp_create_nonce('swd_news_nonce') . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            $("#news-data").html(response.data);
                        } else {
                            $("#news-data").html("<p class=\"swd-error-message\">" + ' . json_encode(esc_html__('Error loading news headlines.', 'simple-weather-dashboard')) . ' + "</p>");
                        }
                    },
                    error: function() {
                        $("#news-data").html("<p class=\"swd-error-message\">" + ' . json_encode(esc_html__('Failed to load news headlines.', 'simple-weather-dashboard')) . ' + "</p>");
                    }
                });
            });
        ');
    }
    
    public function ajax_get_weather() {
        // Sanitize nonce input from $_POST
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

        if (!wp_verify_nonce($nonce, 'swd_weather_nonce')) {
            wp_die(esc_html__('Security check failed', 'simple-weather-dashboard'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', 'simple-weather-dashboard'));
        }
        
        $weather_data = $this->fetch_weather();
        
        if ($weather_data) {
            wp_send_json_success($weather_data);
        } else {
            wp_send_json_error(esc_html__('Unable to fetch weather data', 'simple-weather-dashboard'));
        }
    }
    
    public function ajax_get_news() {
        // Sanitize nonce input from $_POST
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

        if (!wp_verify_nonce($nonce, 'swd_news_nonce')) {
            wp_die(esc_html__('Security check failed', 'simple-weather-dashboard'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', 'simple-weather-dashboard'));
        }
        
        $news_data = $this->fetch_news();
        
        if ($news_data) {
            wp_send_json_success($news_data);
        } else {
            wp_send_json_error(esc_html__('Unable to fetch news headlines', 'simple-weather-dashboard'));
        }
    }
    
    private function fetch_weather() {
        $api_url = 'https://api.open-meteo.com/v1/forecast?latitude=40.7128&longitude=-74.0060&current_weather=true&temperature_unit=fahrenheit';
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['current_weather'])) {
            return false;
        }
        
        $current = $data['current_weather'];
        $temp = round($current['temperature']);
        $windspeed = round($current['windspeed']);
        $weather_code = intval($current['weathercode']);
        
        $weather_desc = $this->get_weather_description($weather_code);
        $weather_icon_html = $this->get_weather_icon($weather_code);

        $html = '<div class="swd-weather-widget">';
        $html .= '<div class="swd-weather-icon">' . $weather_icon_html . '</div>';
        $html .= '<div class="swd-weather-temp">' . esc_html($temp) . '°F</div>';
        $html .= '<div class="swd-weather-condition">' . esc_html($weather_desc) . '</div>';
        $html .= '<div class="swd-weather-wind"><strong>' . esc_html__('Wind Speed:', 'simple-weather-dashboard') . '</strong> ' . esc_html($windspeed) . ' mph</div>';
        $html .= '<div class="swd-last-updated"><small>' . esc_html__('Last updated:', 'simple-weather-dashboard') . ' ' . esc_html(current_time('F j, Y g:i A')) . '</small></div>';
        $html .= '</div>';
        
        return $html;
    }
    
    private function fetch_news() {
        // Using NewsAPI.org free tier (requires API key for production)
        // For demo purposes, using a mock RSS feed from BBC News
        $rss_url = 'https://feeds.bbci.co.uk/news/world/us_and_canada/rss.xml';
        
        $response = wp_remote_get($rss_url, array(
            'timeout' => 15,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            return false;
        }
        
        // Parse RSS feed
        $xml = simplexml_load_string($body);
        
        if ($xml === false || !isset($xml->channel->item)) {
            return false;
        }
        
        $html = '<ul class="swd-news-list">';
        $count = 0;
        
        foreach ($xml->channel->item as $item) {
            if ($count >= 5) break; // Limit to 5 headlines
            
            $title = sanitize_text_field((string)$item->title);
            $link = esc_url((string)$item->link);
            $pub_date = sanitize_text_field((string)$item->pubDate);
            $description = sanitize_text_field(wp_trim_words((string)$item->description, 20, '...'));
            
            // Format publication date
            $formatted_date = '';
            if (!empty($pub_date)) {
                $timestamp = strtotime($pub_date);
                if ($timestamp !== false) {
                    $formatted_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
                }
            }
            
            $html .= '<li class="swd-news-item">';
            $html .= '<h4 class="swd-news-title"><a href="' . $link . '" target="_blank">' . esc_html($title) . '</a></h4>';
            if (!empty($description)) {
                $html .= '<p class="swd-news-description">' . esc_html($description) . '</p>';
            }
            if (!empty($formatted_date)) {
                $html .= '<small class="swd-news-date">' . esc_html($formatted_date) . '</small>';
            }
            $html .= '</li>';
            
            $count++;
        }
        
        if ($count === 0) {
            $html .= '<li class="swd-news-item"><p>' . esc_html__('No news headlines available at the moment.', 'simple-weather-dashboard') . '</p></li>';
        }
        
        $html .= '</ul>';
        $html .= '<p class="swd-news-list-footer"><small>' . esc_html__('News feed last updated:', 'simple-weather-dashboard') . ' ' . esc_html(current_time('F j, Y g:i A')) . '</small></p>';
        
        return $html;
    }
    
    private function get_weather_description($code) {
        $descriptions = array(
            0 => esc_html__('Clear sky', 'simple-weather-dashboard'),
            1 => esc_html__('Mainly clear', 'simple-weather-dashboard'),
            2 => esc_html__('Partly cloudy', 'simple-weather-dashboard'),
            3 => esc_html__('Overcast', 'simple-weather-dashboard'),
            45 => esc_html__('Fog', 'simple-weather-dashboard'),
            48 => esc_html__('Depositing rime fog', 'simple-weather-dashboard'),
            51 => esc_html__('Light drizzle', 'simple-weather-dashboard'),
            53 => esc_html__('Moderate drizzle', 'simple-weather-dashboard'),
            55 => esc_html__('Dense drizzle', 'simple-weather-dashboard'),
            61 => esc_html__('Slight rain', 'simple-weather-dashboard'),
            63 => esc_html__('Moderate rain', 'simple-weather-dashboard'),
            65 => esc_html__('Heavy rain', 'simple-weather-dashboard'),
            71 => esc_html__('Slight snow fall', 'simple-weather-dashboard'),
            73 => esc_html__('Moderate snow fall', 'simple-weather-dashboard'),
            75 => esc_html__('Heavy snow fall', 'simple-weather-dashboard'),
            95 => esc_html__('Thunderstorm', 'simple-weather-dashboard')
        );
        
        return isset($descriptions[$code]) ? $descriptions[$code] : esc_html__('Unknown', 'simple-weather-dashboard');
    }

    private function get_weather_icon($code) {
        // Enqueue Dashicons, though they are usually already loaded in admin.
        wp_enqueue_style('dashicons');

        switch ($code) {
            case 0: // Clear sky
                return '<span class="dashicons dashicons-sun"></span>';
            case 1: // Mainly clear
            case 2: // Partly cloudy
                return '<span class="dashicons dashicons-cloud-sun"></span>';
            case 3: // Overcast
                return '<span class="dashicons dashicons-cloud"></span>';
            case 45: // Fog
            case 48: // Depositing rime fog
                return '<span class="dashicons dashicons-visibility"></span>'; // Closest general icon for misty
            case 51: // Light drizzle
            case 53: // Moderate drizzle
            case 55: // Dense drizzle
            case 61: // Slight rain
            case 63: // Moderate rain
            case 65: // Heavy rain
                return '<span class="dashicons dashicons-cloud-rain"></span>';
            case 71: // Slight snow fall
            case 73: // Moderate snow fall
            case 75: // Heavy snow fall
                return '<span class="dashicons dashicons-cloud-snow"></span>';
            case 95: // Thunderstorm
                return '<span class="dashicons dashicons-cloud-star"></span>'; // Closest general icon for stormy
            default:
                return '<span class="dashicons dashicons-visibility"></span>'; // Default unknown
        }
    }
}

new SimpleWeatherDashboard();