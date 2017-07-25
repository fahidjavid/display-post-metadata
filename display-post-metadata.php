<?php
/**
 * Plugin Name: Display Post Metadata
 * Plugin URI: https://fahidjavid.com
 * Description: Allows you to display posts and pages metadata information and custom fields.
 * Version: 1.2.0
 * Author: Fahid Javid
 * Author URI: https://fahidjavid.com
 * License: GPL2+
 * Text Domain: display-post-metadata
 * Domain Path: /languages/
 */

/**
 * Views
 *
 * @since 1.0.0
 */
require_once plugin_dir_path( __FILE__ ) . 'include/views.php';
require_once plugin_dir_path( __FILE__ ) . 'include/display-metadata-shortcode.php';


/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function display_post_metadata_load_textdomain() {
    load_plugin_textdomain( 'display-post-metadata', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'display_post_metadata_load_textdomain' );


/**
 * Load plugin styles.
 *
 * @since 1.0.0
 */
function display_post_metadata_styles()
{
    // Register the style.css file
    wp_register_style( 'metadata-style', plugins_url( '/css/style.css', __FILE__ ), array(), '1.0', 'all' );

    // Enqueue the style.css file
    wp_enqueue_style( 'metadata-style' );
}
add_action( 'wp_enqueue_scripts', 'display_post_metadata_styles' );


new Display_Metadata_Shortcode();