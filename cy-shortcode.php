<?php
/**
 * Plugin Name: Current Year Shortcode
 * Description: Creates a [thisyear] shortcode that outputs the current year in plaintext.
 * Version: 1.0.0
 * Author: plugin.new
 * Author URI: https://plugin.new
 * Text Domain: cy-shortcode
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

// Define plugin constants
if ( ! defined( 'CY_SHORTCODE_PLUGIN_DIR' ) ) {
    define( 'CY_SHORTCODE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Include the main plugin class
require_once CY_SHORTCODE_PLUGIN_DIR . 'includes/class-cy-shortcode-main.php';

/**
 * Initializes the Current Year Shortcode plugin.
 */
function cy_shortcode_init() {
    new CY_Shortcode_Main();
}
add_action( 'plugins_loaded', 'cy_shortcode_init' );