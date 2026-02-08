<?php
defined('ABSPATH') || exit;

/**
 * Main class for the Current Year Shortcode plugin.
 * Handles the registration and rendering of the [thisyear] shortcode.
 */
class CY_Shortcode_Main {

    /**
     * Constructor.
     * Registers the shortcode on the 'init' hook.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_shortcode' ) );
    }

    /**
     * Registers the [thisyear] shortcode with its callback function.
     */
    public function register_shortcode() {
        add_shortcode( 'thisyear', array( $this, 'render_thisyear_shortcode' ) );
    }

    /**
     * Shortcode callback function to output the current year.
     *
     * @param array $atts Shortcode attributes (not used here).
     * @return string The current year, escaped for safe output.
     */
    public function render_thisyear_shortcode( $atts ) {
        // The output is a simple number, but esc_html() is used for robust escaping practice.
        return esc_html( date( 'Y' ) );
    }
}
